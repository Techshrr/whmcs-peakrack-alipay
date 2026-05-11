<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../alipay/lib.php';

$gatewayModuleName = 'alipay';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (empty($gatewayParams['type'])) {
    die('Module Not Activated');
}

function whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, $message)
{
    if ($isReturn) {
        if ($invoiceId > 0) {
            header('Location: ' . rtrim($gatewayParams['systemurl'], '/') . '/viewinvoice.php?id=' . (int) $invoiceId);
            exit;
        }

        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }

    echo $message;
    exit;
}

function whmcs_alipay_callback_transaction_exists($transactionId)
{
    if ($transactionId === '' || !class_exists('\WHMCS\Database\Capsule')) {
        return false;
    }

    return \WHMCS\Database\Capsule::table('tblaccounts')
        ->where('transid', $transactionId)
        ->exists();
}

function whmcs_alipay_callback_invoice_balance($invoiceId)
{
    if (!function_exists('localAPI')) {
        return null;
    }

    $invoice = localAPI('GetInvoice', ['invoiceid' => (int) $invoiceId]);
    if (!is_array($invoice) || ($invoice['result'] ?? '') !== 'success') {
        return null;
    }

    if (isset($invoice['balance'])) {
        return whmcs_alipay_format_amount($invoice['balance']);
    }

    if (isset($invoice['total'])) {
        return whmcs_alipay_format_amount($invoice['total']);
    }

    return null;
}

$isReturn = isset($_GET['return']);
$returnExpectedAmount = isset($_GET['expected_amount']) ? whmcs_alipay_format_amount($_GET['expected_amount']) : null;
$returnToken = (string) ($_GET['return_token'] ?? '');
$requestParams = $_POST ?: $_GET;
unset($requestParams['return']);
unset($requestParams['expected_amount'], $requestParams['return_token']);

$safeLogData = $requestParams;
$safeLogData['callback_mode'] = $isReturn ? 'return' : 'notify';
if ($returnExpectedAmount !== null) {
    $safeLogData['return_expected_amount'] = $returnExpectedAmount;
}
$invoiceId = 0;

if (empty($requestParams)) {
    logTransaction($gatewayModuleName, [], 'Empty Callback');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, 0, 'failure');
}

if (empty($requestParams['app_id']) || (string) $requestParams['app_id'] !== (string) $gatewayParams['appId']) {
    logTransaction($gatewayModuleName, $safeLogData, 'Invalid App ID');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, 0, 'failure');
}

if (!empty($gatewayParams['sellerId']) && (empty($requestParams['seller_id']) || (string) $requestParams['seller_id'] !== (string) $gatewayParams['sellerId'])) {
    logTransaction($gatewayModuleName, $safeLogData, 'Invalid Seller ID');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, 0, 'failure');
}

if (!whmcs_alipay_verify($requestParams, $gatewayParams['alipayPublicKey'])) {
    logTransaction($gatewayModuleName, $safeLogData, 'Signature Verification Failed');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, 0, 'failure');
}

$outTradeNo = (string) ($requestParams['out_trade_no'] ?? '');
$invoiceId = whmcs_alipay_invoice_id_from_out_trade_no($outTradeNo, $gatewayParams['orderPrefix'] ?? 'WHMCS_');
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['paymentmethod']);

$transactionId = (string) ($requestParams['trade_no'] ?? '');
$paymentAmount = whmcs_alipay_format_amount($requestParams['total_amount'] ?? $requestParams['total_fee'] ?? 0);
$tradeStatus = (string) ($requestParams['trade_status'] ?? '');
$isPaidNotification = in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
$isSignedReturnPayment = false;

if (!$isPaidNotification && $isReturn && $tradeStatus === '' && $transactionId !== '' && (float) $paymentAmount > 0) {
    if ($returnExpectedAmount !== null && whmcs_alipay_return_token_is_valid($invoiceId, $returnExpectedAmount, $returnToken, $gatewayParams['merchantPrivateKey'] ?? '')) {
        $isSignedReturnPayment = true;
        $safeLogData['return_payment_fallback'] = 'accepted';
    } else {
        $safeLogData['return_payment_fallback'] = 'missing_or_invalid_token';
    }
}

if (!$isPaidNotification && !$isSignedReturnPayment) {
    logTransaction($gatewayModuleName, $safeLogData, 'Ignored Status: ' . ($tradeStatus ?: 'return'));
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'success');
}

if ($transactionId === '') {
    logTransaction($gatewayModuleName, $safeLogData, 'Missing Transaction ID');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'failure');
}

if ((float) $paymentAmount <= 0) {
    logTransaction($gatewayModuleName, $safeLogData, 'Invalid Payment Amount');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'failure');
}

if (($gatewayParams['verifyAmount'] ?? '') === 'on') {
    $passback = whmcs_alipay_parse_passback_params($requestParams['passback_params'] ?? '');
    $expectedAmount = isset($passback['expected_amount'])
        ? whmcs_alipay_format_amount($passback['expected_amount'])
        : null;
    if ($expectedAmount === null && $isSignedReturnPayment && $returnExpectedAmount !== null) {
        $expectedAmount = $returnExpectedAmount;
    }

    if ($expectedAmount !== null && !whmcs_alipay_amounts_match($expectedAmount, $paymentAmount)) {
        $safeLogData['expected_gateway_amount'] = $expectedAmount;
        logTransaction($gatewayModuleName, $safeLogData, 'Amount Mismatch');
        whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'failure');
    }

    if ($expectedAmount === null) {
        $invoiceBalance = whmcs_alipay_callback_invoice_balance($invoiceId);
        if ($invoiceBalance !== null && !whmcs_alipay_amounts_match($invoiceBalance, $paymentAmount)) {
            $safeLogData['expected_invoice_balance'] = $invoiceBalance;
            logTransaction($gatewayModuleName, $safeLogData, 'Amount Mismatch');
            whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'failure');
        }
    }
}

if (whmcs_alipay_callback_transaction_exists($transactionId)) {
    logTransaction($gatewayModuleName, $safeLogData, 'Duplicate Transaction');
    whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'success');
}

checkCbTransID($transactionId);

logTransaction($gatewayModuleName, $safeLogData, $isSignedReturnPayment ? 'Successful Return' : 'Successful');
addInvoicePayment(
    $invoiceId,
    $transactionId,
    0,
    0.00,
    $gatewayParams['paymentmethod']
);

whmcs_alipay_callback_finish($isReturn, $gatewayParams, $invoiceId, 'success');

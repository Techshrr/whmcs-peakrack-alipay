<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/alipay/lib.php';

function alipay_MetaData()
{
    return [
        'DisplayName' => 'Alipay (支付宝)',
        'APIVersion' => '1.1',
    ];
}

function alipay_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Alipay (支付宝)',
        ],
        'appId' => [
            'FriendlyName' => 'App ID',
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => '支付宝开放平台应用 App ID / Alipay Open Platform application ID.',
        ],
        'merchantPrivateKey' => [
            'FriendlyName' => '应用私钥 / Application Private Key',
            'Type' => 'textarea',
            'Rows' => '10',
            'Cols' => '80',
            'Default' => '',
            'Description' => '普通公钥模式的应用私钥，支持 PEM 头尾或密钥正文。请勿填写应用公钥 / Use the application private key, not the application public key.',
        ],
        'alipayPublicKey' => [
            'FriendlyName' => '支付宝公钥 / Alipay Public Key',
            'Type' => 'textarea',
            'Rows' => '8',
            'Cols' => '80',
            'Default' => '',
            'Description' => '支付宝开放平台提供的支付宝公钥，不是应用公钥 / Use the Alipay public key from the Open Platform.',
        ],
        'sellerId' => [
            'FriendlyName' => 'Seller ID / PID',
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => '可选。填写 2088 开头的收款账号 PID 后，回调会校验 seller_id / Optional payee PID validation.',
        ],
        'orderPrefix' => [
            'FriendlyName' => '订单号前缀 / Order Prefix',
            'Type' => 'text',
            'Size' => '20',
            'Default' => 'WHMCS_',
            'Description' => '只允许字母、数字和下划线。多个站点共用同一支付宝应用时请设置不同前缀 / Letters, numbers, and underscores only.',
        ],
        'productCode' => [
            'FriendlyName' => 'Product Code',
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'FAST_INSTANT_TRADE_PAY',
            'Description' => '电脑网站支付通常固定为 FAST_INSTANT_TRADE_PAY / Usually fixed for PC website payment.',
        ],
        'timeoutExpress' => [
            'FriendlyName' => '支付超时 / Payment Timeout',
            'Type' => 'text',
            'Size' => '8',
            'Default' => '30m',
            'Description' => '支付宝订单过期时间，例如 30m、2h、1d / Alipay order timeout.',
        ],
        'sandbox' => [
            'FriendlyName' => '沙箱模式 / Sandbox Mode',
            'Type' => 'yesno',
            'Description' => '勾选后使用支付宝沙箱网关。沙箱必须使用沙箱 App ID 和沙箱公钥 / Use sandbox credentials when enabled.',
        ],
        'verifyAmount' => [
            'FriendlyName' => '校验金额 / Verify Amount',
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => '建议开启。回调入账前校验支付宝返回的 CNY 金额是否等于发起支付时的 CNY 金额 / Recommended.',
        ],
    ];
}

function alipay_link($params)
{
    if (!extension_loaded('openssl')) {
        return whmcs_alipay_alert('danger', whmcs_alipay_lang('openssl_missing', $params));
    }

    foreach (['appId', 'merchantPrivateKey', 'alipayPublicKey'] as $requiredField) {
        if (empty($params[$requiredField])) {
            return whmcs_alipay_alert(
                'warning',
                whmcs_alipay_lang('missing_config', $params, ['field' => $requiredField])
            );
        }
    }

    $invoiceId = (int) $params['invoiceid'];
    $amount = whmcs_alipay_format_amount($params['amount']);

    if ((float) $amount < 0.01) {
        return whmcs_alipay_alert('warning', whmcs_alipay_lang('min_amount', $params));
    }

    $currency = strtoupper((string) ($params['currency'] ?? ''));
    if ($currency !== '' && $currency !== 'CNY') {
        return whmcs_alipay_alert(
            'warning',
            whmcs_alipay_lang('currency_error', $params, ['currency' => $currency])
        );
    }

    $callbackUrl = rtrim($params['systemurl'], '/') . '/modules/gateways/callback/alipay.php';
    $returnToken = whmcs_alipay_return_token($invoiceId, $amount, $params['merchantPrivateKey']);
    $returnUrl = $callbackUrl . '?return=1&expected_amount=' . rawurlencode($amount) . '&return_token=' . rawurlencode($returnToken);
    $outTradeNo = whmcs_alipay_out_trade_no($invoiceId, $params['orderPrefix'] ?? 'WHMCS_');
    $productCode = trim((string) ($params['productCode'] ?? 'FAST_INSTANT_TRADE_PAY')) ?: 'FAST_INSTANT_TRADE_PAY';
    $invoiceLabel = whmcs_alipay_clean_display_text(
        $params['companyname'] . ' - Invoice #' . ($params['invoicenum'] ?: $invoiceId)
    );
    $itemSummary = whmcs_alipay_invoice_item_summary($invoiceId);
    $firstItem = whmcs_alipay_clean_display_text($itemSummary['first_item'], $invoiceLabel);
    $itemCount = (int) $itemSummary['item_count'];
    $subject = whmcs_alipay_truncate($firstItem, 256);
    $bodySuffix = $itemCount > 1 ? ' - ' . $itemCount . ' items' : '';
    $body = whmcs_alipay_truncate($invoiceLabel . $bodySuffix, 128);

    $bizContent = [
        'out_trade_no' => $outTradeNo,
        'product_code' => $productCode,
        'total_amount' => $amount,
        'subject' => $subject,
        'body' => $body,
        'passback_params' => rawurlencode(http_build_query(
            [
                'invoiceid' => $invoiceId,
                'expected_amount' => $amount,
                'expected_currency' => 'CNY',
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        )),
    ];

    $timeoutExpress = trim((string) ($params['timeoutExpress'] ?? ''));
    if ($timeoutExpress !== '') {
        $bizContent['timeout_express'] = $timeoutExpress;
    }

    $requestParams = [
        'app_id' => trim((string) $params['appId']),
        'method' => 'alipay.trade.page.pay',
        'format' => 'JSON',
        'charset' => 'utf-8',
        'sign_type' => 'RSA2',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'notify_url' => $callbackUrl,
        'return_url' => $returnUrl,
        'biz_content' => json_encode($bizContent, JSON_UNESCAPED_SLASHES),
    ];

    try {
        $requestParams['sign'] = whmcs_alipay_sign($requestParams, $params['merchantPrivateKey']);
    } catch (Throwable $e) {
        return whmcs_alipay_alert(
            'danger',
            whmcs_alipay_lang('signing_failed', $params, ['message' => $e->getMessage()])
        );
    }

    $gatewayUrl = whmcs_alipay_gateway_url(!empty($params['sandbox']) && $params['sandbox'] === 'on');
    $iconUrl = rtrim($params['systemurl'], '/') . '/modules/gateways/alipay/logo-icon.png';
    $buttonLabel = whmcs_alipay_lang('pay_button', $params);
    $buttonStyles = '<style>
.prk-alipay-payment-form {
    margin: 0;
    width: 100%;
}
.prk-alipay-payment-button.btn {
    align-items: center !important;
    box-sizing: border-box !important;
    display: flex !important;
    gap: 0 !important;
    height: 44px !important;
    justify-content: center !important;
    line-height: 1 !important;
    padding: 0 14px !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
    width: 100% !important;
}
.prk-alipay-payment-button__content {
    align-items: center !important;
    display: inline-flex !important;
    gap: 8px !important;
    height: 20px !important;
    justify-content: center !important;
    line-height: 20px !important;
    margin: 0 auto !important;
}
.prk-alipay-payment-button__icon {
    display: block !important;
    flex: 0 0 18px !important;
    height: 18px !important;
    line-height: 0 !important;
    margin: 0 !important;
    object-fit: contain !important;
    padding: 0 !important;
    width: 18px !important;
}
.prk-alipay-payment-button__label {
    display: block !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    line-height: 18px !important;
    margin: 0 !important;
    padding: 0 !important;
}
</style>';

    return $buttonStyles
        . '<form class="prk-alipay-payment-form" method="post" accept-charset="UTF-8" action="' . htmlspecialchars($gatewayUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n"
        . whmcs_alipay_render_hidden_inputs($requestParams)
        . '<button type="submit" class="btn btn-primary prk-alipay-payment-button">'
        . '<span class="prk-alipay-payment-button__content">'
        . '<img class="prk-alipay-payment-button__icon" src="' . htmlspecialchars($iconUrl, ENT_QUOTES, 'UTF-8') . '" alt="">'
        . '<span class="prk-alipay-payment-button__label">' . htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8') . '</span>'
        . '</span>'
        . '</button>' . "\n"
        . '</form>';
}

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

function whmcs_alipay_admin_normalize_language($language): string
{
    return in_array((string) $language, ['zh', 'en'], true) ? (string) $language : '';
}

function whmcs_alipay_admin_language(): string
{
    $cookieName = 'prk_alipay_admin_lang';
    $requestLanguage = whmcs_alipay_admin_normalize_language($_GET['prk_alipay_admin_lang'] ?? '');
    if ($requestLanguage !== '') {
        $_COOKIE[$cookieName] = $requestLanguage;
        if (!headers_sent()) {
            setcookie($cookieName, $requestLanguage, time() + 31536000, '', '', false, true);
        }

        return $requestLanguage;
    }

    $cookieLanguage = whmcs_alipay_admin_normalize_language($_COOKIE[$cookieName] ?? '');
    if ($cookieLanguage !== '') {
        return $cookieLanguage;
    }

    try {
        if (class_exists('\WHMCS\Database\Capsule')) {
            $row = \WHMCS\Database\Capsule::table('tblpaymentgateways')
                ->where('gateway', 'alipay')
                ->whereIn('setting', ['adminLanguage', 'adminlanguage', 'AdminLanguage'])
                ->first(['value']);
            $storedLanguage = whmcs_alipay_admin_normalize_language($row->value ?? '');
            if ($storedLanguage !== '') {
                return $storedLanguage;
            }
        }
    } catch (Throwable $e) {
    }

    return 'zh';
}

function whmcs_alipay_admin_text(string $language, string $key): string
{
    $texts = [
        'zh' => [
            'admin_title' => 'Alipay 支付网关配置',
            'admin_subtitle' => '用于支付宝开放平台电脑网站支付。点击右上角语言按钮可立即切换后台配置显示语言。',
            'version_badge' => '版本 1.1.3',
            'language_zh' => '中文',
            'language_en' => 'English',
            'credentials_title' => '开放平台凭据',
            'credentials_desc' => '填写支付宝开放平台应用信息。应用私钥和支付宝公钥必须来自同一个应用和同一种密钥模式。',
            'order_title' => '订单与显示',
            'order_desc' => '控制支付宝订单号、产品码和支付页面超时时间。订单号前缀只影响支付商户订单号，不影响 WHMCS 发票入账。',
            'security_title' => '环境与校验',
            'security_desc' => '沙箱仅用于测试；正式收款请关闭沙箱并使用正式应用凭据。金额校验建议保持开启。',
            'help_title' => '上线检查',
            'help_desc' => '回调地址为 modules/gateways/callback/alipay.php；WHMCS 多货币订单会按 WHMCS 已转换后的 CNY 金额发起支付；支付宝开放平台授权回调地址不是此支付通知地址。',
            'app_id' => 'App ID',
            'app_id_desc' => '支付宝开放平台应用 App ID。',
            'private_key' => '应用私钥',
            'private_key_desc' => '普通公钥模式的应用私钥，支持 PEM 头尾或密钥正文。请勿填写应用公钥。',
            'public_key' => '支付宝公钥',
            'public_key_desc' => '支付宝开放平台提供的支付宝公钥，不是应用公钥。',
            'seller_id' => 'Seller ID / PID',
            'seller_id_desc' => '可选。填写 2088 开头的收款账号 PID 后，回调会校验 seller_id。',
            'order_prefix' => '订单号前缀',
            'order_prefix_desc' => '只允许字母、数字和下划线。单站点可以使用较短前缀，例如 PR_。',
            'product_code' => 'Product Code',
            'product_code_desc' => '电脑网站支付通常固定为 FAST_INSTANT_TRADE_PAY。',
            'timeout' => '支付超时',
            'timeout_desc' => '支付宝订单过期时间，例如 30m、2h、1d。',
            'sandbox' => '沙箱模式',
            'sandbox_desc' => '勾选后使用支付宝沙箱网关。沙箱必须使用沙箱 App ID 和沙箱公钥。',
            'verify_amount' => '校验金额',
            'verify_amount_desc' => '建议开启。回调入账前校验支付宝返回的 CNY 金额是否等于发起支付时的 CNY 金额。',
        ],
        'en' => [
            'admin_title' => 'Alipay Gateway Configuration',
            'admin_subtitle' => 'Configure Alipay Open Platform PC website payment. Use the language buttons in the top-right corner to switch this admin page immediately.',
            'version_badge' => 'Version 1.1.3',
            'language_zh' => '中文',
            'language_en' => 'English',
            'credentials_title' => 'Open Platform Credentials',
            'credentials_desc' => 'Enter the Alipay Open Platform application credentials. The application private key and Alipay public key must belong to the same app and key mode.',
            'order_title' => 'Order and Display',
            'order_desc' => 'Controls the Alipay trade number, product code, and payment timeout. The prefix only affects the payment trade number, not WHMCS invoice application.',
            'security_title' => 'Environment and Verification',
            'security_desc' => 'Sandbox is for testing only. Use production credentials with sandbox disabled for live payments. Amount verification should remain enabled.',
            'help_title' => 'Go-Live Checklist',
            'help_desc' => 'The payment notification endpoint is modules/gateways/callback/alipay.php. Multi-currency WHMCS invoices are paid using the already-converted CNY amount. The Alipay Open Platform auth callback is not this payment notification URL.',
            'app_id' => 'App ID',
            'app_id_desc' => 'Alipay Open Platform application ID.',
            'private_key' => 'Application Private Key',
            'private_key_desc' => 'Application private key in normal public-key mode. PEM headers or key body are accepted. Do not enter the application public key.',
            'public_key' => 'Alipay Public Key',
            'public_key_desc' => 'Alipay public key from the Open Platform, not the application public key.',
            'seller_id' => 'Seller ID / PID',
            'seller_id_desc' => 'Optional. When a 2088 payee PID is set, callback seller_id will be validated.',
            'order_prefix' => 'Order Prefix',
            'order_prefix_desc' => 'Letters, numbers, and underscores only. For a single site, use a short prefix such as PR_.',
            'product_code' => 'Product Code',
            'product_code_desc' => 'Usually fixed to FAST_INSTANT_TRADE_PAY for PC website payment.',
            'timeout' => 'Payment Timeout',
            'timeout_desc' => 'Alipay order expiration time, for example 30m, 2h, or 1d.',
            'sandbox' => 'Sandbox Mode',
            'sandbox_desc' => 'Use the Alipay sandbox gateway when enabled. Sandbox requires sandbox App ID and sandbox public key.',
            'verify_amount' => 'Verify Amount',
            'verify_amount_desc' => 'Recommended. Before applying payment, verify the returned CNY amount equals the original CNY payment amount.',
        ],
    ];

    return $texts[$language][$key] ?? $texts['zh'][$key] ?? $key;
}

function whmcs_alipay_admin_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function whmcs_alipay_admin_system(string $html): array
{
    return [
        'FriendlyName' => '',
        'Type' => 'System',
        'Value' => $html,
    ];
}

function whmcs_alipay_admin_language_url(string $language): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $queryString = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    $query = [];
    if ($queryString !== '') {
        parse_str($queryString, $query);
    }
    $query['prk_alipay_admin_lang'] = $language;

    return ($path !== '' ? $path : '') . '?' . http_build_query($query);
}

function whmcs_alipay_admin_intro(string $language): array
{
    $title = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, 'admin_title'));
    $subtitle = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, 'admin_subtitle'));
    $badge = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, 'version_badge'));
    $zhUrl = whmcs_alipay_admin_e(whmcs_alipay_admin_language_url('zh'));
    $enUrl = whmcs_alipay_admin_e(whmcs_alipay_admin_language_url('en'));
    $zhLabel = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, 'language_zh'));
    $enLabel = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, 'language_en'));

    return whmcs_alipay_admin_system('<style>
.prk-gw-admin{box-sizing:border-box;border:1px solid #d8e0ea;border-radius:6px;background:#fff;margin:8px 0 12px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.prk-gw-admin__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 16px;border-bottom:1px solid #e7edf3;background:#fbfcfe}
.prk-gw-admin__title{margin:0 0 4px;font-size:16px;font-weight:700;color:#111827}
.prk-gw-admin__desc{margin:0;color:#6b7280;font-size:12px;line-height:1.5}
.prk-gw-admin__actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end}
.prk-gw-admin__badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 9px;background:#eff8ff;color:#175cd3;border:1px solid #b2ddff;font-size:12px;font-weight:700;white-space:nowrap}
.prk-gw-lang{display:inline-flex;border:1px solid #cfd8e3;border-radius:6px;background:#fff;overflow:hidden}
.prk-gw-lang a{display:inline-flex;align-items:center;padding:6px 9px;color:#475569;text-decoration:none;font-size:12px;font-weight:700}
.prk-gw-lang a.active{background:#2563eb;color:#fff}
.prk-gw-section{box-sizing:border-box;border:1px solid #e7edf3;border-radius:6px;background:#fbfcfe;margin:8px 0;padding:12px 14px}
.prk-gw-section h4{margin:0 0 4px;font-size:14px;font-weight:700;color:#111827}
.prk-gw-section p{margin:0;color:#6b7280;font-size:12px;line-height:1.5}
@media (max-width:700px){.prk-gw-admin__head{display:block}.prk-gw-admin__badge{margin-top:10px}}
</style><div class="prk-gw-admin"><div class="prk-gw-admin__head"><div><h3 class="prk-gw-admin__title">' . $title . '</h3><p class="prk-gw-admin__desc">' . $subtitle . '</p></div><div class="prk-gw-admin__actions"><span class="prk-gw-admin__badge">' . $badge . '</span><div class="prk-gw-lang"><a class="' . ($language === 'zh' ? 'active' : '') . '" href="' . $zhUrl . '">' . $zhLabel . '</a><a class="' . ($language === 'en' ? 'active' : '') . '" href="' . $enUrl . '">' . $enLabel . '</a></div></div></div></div>');
}

function whmcs_alipay_admin_section(string $language, string $titleKey, string $descKey): array
{
    $title = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, $titleKey));
    $desc = whmcs_alipay_admin_e(whmcs_alipay_admin_text($language, $descKey));

    return whmcs_alipay_admin_system('<div class="prk-gw-section"><h4>' . $title . '</h4><p>' . $desc . '</p></div>');
}

function alipay_config()
{
    $language = whmcs_alipay_admin_language();
    $t = static fn(string $key): string => whmcs_alipay_admin_text($language, $key);

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Alipay (支付宝)',
        ],
        'adminUiIntro' => whmcs_alipay_admin_intro($language),
        'credentialsSection' => whmcs_alipay_admin_section($language, 'credentials_title', 'credentials_desc'),
        'appId' => [
            'FriendlyName' => $t('app_id'),
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => $t('app_id_desc'),
        ],
        'merchantPrivateKey' => [
            'FriendlyName' => $t('private_key'),
            'Type' => 'textarea',
            'Rows' => '10',
            'Cols' => '80',
            'Default' => '',
            'Description' => $t('private_key_desc'),
        ],
        'alipayPublicKey' => [
            'FriendlyName' => $t('public_key'),
            'Type' => 'textarea',
            'Rows' => '8',
            'Cols' => '80',
            'Default' => '',
            'Description' => $t('public_key_desc'),
        ],
        'sellerId' => [
            'FriendlyName' => $t('seller_id'),
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => $t('seller_id_desc'),
        ],
        'orderSection' => whmcs_alipay_admin_section($language, 'order_title', 'order_desc'),
        'orderPrefix' => [
            'FriendlyName' => $t('order_prefix'),
            'Type' => 'text',
            'Size' => '20',
            'Default' => 'WHMCS_',
            'Description' => $t('order_prefix_desc'),
        ],
        'productCode' => [
            'FriendlyName' => $t('product_code'),
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'FAST_INSTANT_TRADE_PAY',
            'Description' => $t('product_code_desc'),
        ],
        'timeoutExpress' => [
            'FriendlyName' => $t('timeout'),
            'Type' => 'text',
            'Size' => '8',
            'Default' => '30m',
            'Description' => $t('timeout_desc'),
        ],
        'securitySection' => whmcs_alipay_admin_section($language, 'security_title', 'security_desc'),
        'sandbox' => [
            'FriendlyName' => $t('sandbox'),
            'Type' => 'yesno',
            'Description' => $t('sandbox_desc'),
        ],
        'verifyAmount' => [
            'FriendlyName' => $t('verify_amount'),
            'Type' => 'yesno',
            'Default' => 'on',
            'Description' => $t('verify_amount_desc'),
        ],
        'helpSection' => whmcs_alipay_admin_section($language, 'help_title', 'help_desc'),
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

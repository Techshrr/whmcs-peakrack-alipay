<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function whmcs_alipay_gateway_url($sandbox)
{
    return $sandbox
        ? 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'
        : 'https://openapi.alipay.com/gateway.do';
}

function whmcs_alipay_is_chinese_language(array $params = [])
{
    $language = '';

    if (!empty($params['clientdetails']['language'])) {
        $language = (string) $params['clientdetails']['language'];
    } elseif (!empty($params['language'])) {
        $language = (string) $params['language'];
    } elseif (class_exists('\WHMCS\Session') && method_exists('\WHMCS\Session', 'get')) {
        $language = (string) (\WHMCS\Session::get('Language') ?: \WHMCS\Session::get('language'));
    } elseif (!empty($_SESSION['Language'])) {
        $language = (string) $_SESSION['Language'];
    } elseif (!empty($_SESSION['language'])) {
        $language = (string) $_SESSION['language'];
    }

    $language = strtolower($language);

    return strpos($language, 'chinese') !== false
        || strpos($language, 'zh') === 0
        || strpos($language, 'cn') !== false;
}

function whmcs_alipay_lang($key, array $params = [], array $replace = [])
{
    $messages = [
        'en' => [
            'openssl_missing' => 'Alipay requires the PHP OpenSSL extension. Please enable OpenSSL for the WHMCS PHP runtime.',
            'missing_config' => 'Alipay is not fully configured. Missing: :field.',
            'min_amount' => 'Alipay requires a minimum payment amount of 0.01 CNY.',
            'currency_error' => 'Alipay domestic website payment expects CNY. Set this gateway\'s "Convert To For Processing" option to CNY before using it for :currency invoices.',
            'signing_failed' => 'Alipay request signing failed: :message',
            'pay_button' => 'Pay with Alipay',
        ],
        'zh' => [
            'openssl_missing' => '支付宝支付需要 PHP OpenSSL 扩展。请为 WHMCS 使用的 PHP 环境启用 OpenSSL。',
            'missing_config' => '支付宝支付尚未完整配置，缺少：:field。',
            'min_amount' => '支付宝最低支付金额为 0.01 元人民币。',
            'currency_error' => '支付宝电脑网站支付应使用 CNY。请先在此支付网关中把 “Convert To For Processing” 设置为 CNY，再用于 :currency 发票。',
            'signing_failed' => '支付宝请求签名失败：:message',
            'pay_button' => '使用支付宝支付',
        ],
    ];

    $locale = whmcs_alipay_is_chinese_language($params) ? 'zh' : 'en';
    $message = $messages[$locale][$key] ?? $messages['en'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $message = str_replace(':' . $name, (string) $value, $message);
    }

    return $message;
}

function whmcs_alipay_clean_key($key)
{
    $key = trim((string) $key);
    $key = str_replace(["\r\n", "\r", "\n"], "\n", $key);
    $key = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $key);

    return trim($key);
}

function whmcs_alipay_pem_candidates($key, $type)
{
    $key = whmcs_alipay_clean_key($key);
    if ($key === '') {
        return [];
    }

    if (strpos($key, '-----BEGIN') !== false) {
        return [$key];
    }

    $body = preg_replace('/\s+/', '', $key);
    if ($body === '') {
        return [];
    }

    if ($type === 'private') {
        return [
            "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END RSA PRIVATE KEY-----",
            "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----",
        ];
    }

    return [
        "-----BEGIN PUBLIC KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PUBLIC KEY-----",
    ];
}

function whmcs_alipay_build_sign_content(array $params, $excludeSignType)
{
    $filtered = [];

    foreach ($params as $key => $value) {
        if ($key === 'sign') {
            continue;
        }
        if ($excludeSignType && $key === 'sign_type') {
            continue;
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        if ($value === null || $value === '') {
            continue;
        }
        $filtered[$key] = (string) $value;
    }

    ksort($filtered, SORT_STRING);

    $parts = [];
    foreach ($filtered as $key => $value) {
        $parts[] = $key . '=' . $value;
    }

    return implode('&', $parts);
}

function whmcs_alipay_sign(array $params, $privateKey)
{
    $content = whmcs_alipay_build_sign_content($params, false);

    foreach (whmcs_alipay_pem_candidates($privateKey, 'private') as $pem) {
        $key = openssl_pkey_get_private($pem);
        if (!$key) {
            continue;
        }

        $signature = '';
        $signed = openssl_sign($content, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($signed) {
            return base64_encode($signature);
        }
    }

    throw new RuntimeException('Unable to sign Alipay request. Check the application private key format.');
}

function whmcs_alipay_verify(array $params, $publicKey)
{
    if (empty($params['sign'])) {
        return false;
    }

    $signature = base64_decode((string) $params['sign'], true);
    if ($signature === false) {
        return false;
    }

    foreach (whmcs_alipay_pem_candidates($publicKey, 'public') as $pem) {
        $key = openssl_pkey_get_public($pem);
        if (!$key) {
            continue;
        }

        $v1Content = whmcs_alipay_build_sign_content($params, true);
        $v1 = openssl_verify($v1Content, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($v1 === 1) {
            return true;
        }

        $v2Content = whmcs_alipay_build_sign_content($params, false);
        $v2 = openssl_verify($v2Content, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($v2 === 1) {
            return true;
        }
    }

    return false;
}

function whmcs_alipay_return_token($invoiceId, $amount, $privateKey)
{
    $secret = hash('sha256', whmcs_alipay_clean_key($privateKey));
    $payload = (int) $invoiceId . '|' . whmcs_alipay_format_amount($amount);

    return hash_hmac('sha256', $payload, $secret);
}

function whmcs_alipay_return_token_is_valid($invoiceId, $amount, $token, $privateKey)
{
    $expected = whmcs_alipay_return_token($invoiceId, $amount, $privateKey);

    return is_string($token) && hash_equals($expected, $token);
}

function whmcs_alipay_format_amount($amount)
{
    return number_format((float) $amount, 2, '.', '');
}

function whmcs_alipay_amounts_match($expected, $actual)
{
    return abs((float) $expected - (float) $actual) < 0.01;
}

function whmcs_alipay_clean_display_text($value, $fallback = '')
{
    $value = (string) $value;

    if ($value !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8,GB18030,GBK,GB2312,ISO-8859-1');
        if (is_string($converted)) {
            $value = $converted;
        }
    }

    $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);

    if (preg_match('//u', $value)) {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
    } else {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
    }

    $value = trim((string) $value);

    return $value !== '' ? $value : (string) $fallback;
}

function whmcs_alipay_invoice_item_summary($invoiceId)
{
    $invoiceId = (int) $invoiceId;
    $summary = [
        'first_item' => '',
        'item_count' => 0,
    ];

    if ($invoiceId <= 0) {
        return $summary;
    }

    try {
        if (class_exists('\WHMCS\Database\Capsule')) {
            $items = \WHMCS\Database\Capsule::table('tblinvoiceitems')
                ->where('invoiceid', $invoiceId)
                ->orderBy('id', 'asc')
                ->get(['description']);

            foreach ($items as $item) {
                $summary['item_count']++;
                if ($summary['first_item'] === '') {
                    $summary['first_item'] = whmcs_alipay_clean_display_text($item->description ?? '');
                }
            }

            return $summary;
        }
    } catch (Throwable $e) {
        $summary = [
            'first_item' => '',
            'item_count' => 0,
        ];
    }

    try {
        if (function_exists('localAPI')) {
            $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
            if (is_array($invoice) && ($invoice['result'] ?? '') === 'success') {
                $items = $invoice['items']['item'] ?? [];
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $summary['item_count']++;
                        if ($summary['first_item'] === '') {
                            $summary['first_item'] = whmcs_alipay_clean_display_text($item['description'] ?? '');
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        return [
            'first_item' => '',
            'item_count' => 0,
        ];
    }

    return $summary;
}

function whmcs_alipay_parse_passback_params($passbackParams)
{
    $passbackParams = trim((string) $passbackParams);
    if ($passbackParams === '') {
        return [];
    }

    $decoded = rawurldecode($passbackParams);
    $parsed = [];
    parse_str($decoded, $parsed);

    return is_array($parsed) ? $parsed : [];
}

function whmcs_alipay_sanitize_prefix($prefix)
{
    $prefix = preg_replace('/[^A-Za-z0-9_]/', '', (string) $prefix);
    if ($prefix === '') {
        $prefix = 'WHMCS_';
    }

    return substr($prefix, 0, 32);
}

function whmcs_alipay_out_trade_no($invoiceId, $prefix)
{
    return whmcs_alipay_sanitize_prefix($prefix) . (int) $invoiceId;
}

function whmcs_alipay_invoice_id_from_out_trade_no($outTradeNo, $prefix)
{
    $outTradeNo = (string) $outTradeNo;
    $prefix = whmcs_alipay_sanitize_prefix($prefix);

    if (strpos($outTradeNo, $prefix) === 0) {
        $candidate = substr($outTradeNo, strlen($prefix));
        if (preg_match('/^\d+$/', $candidate)) {
            return (int) $candidate;
        }
    }

    if (preg_match('/(\d+)$/', $outTradeNo, $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

function whmcs_alipay_truncate($value, $length)
{
    $value = trim((string) $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $length, 'UTF-8');
    }

    return substr($value, 0, $length);
}

function whmcs_alipay_render_hidden_inputs(array $params)
{
    $html = '';
    foreach ($params as $key => $value) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    return $html;
}

function whmcs_alipay_alert($type, $message)
{
    return '<div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</div>';
}

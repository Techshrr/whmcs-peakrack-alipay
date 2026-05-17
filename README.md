# WHMCS PeakRack Alipay Gateway

WHMCS Alipay payment gateway for `alipay.trade.page.pay`, with RSA2 signing, asynchronous callback handling, and WHMCS currency conversion to CNY.

中文说明见 [README.zh-CN.md](README.zh-CN.md).

## Features

- Alipay PC website payment via `alipay.trade.page.pay`
- RSA2 signing and notification signature verification
- WHMCS invoice payment callback integration
- CNY gateway amount verification for converted invoices
- Supports WHMCS `Convert To For Processing = CNY`
- Sectioned gateway configuration UI with a Chinese/English admin language selector
- Chinese/English customer-facing button and error messages
- Gateway logo metadata and invoice payment button icon

## Requirements

- WHMCS 9.x self-hosted installation
- PHP OpenSSL extension enabled
- Alipay Open Platform application using public-key mode
- Alipay PC website payment product enabled

This module uses Alipay public-key mode. Certificate mode is not implemented.

## Installation

The repository keeps documentation at the root and deployable files inside the `whmcs-peakrack-alipay` package directory:

```text
whmcs-peakrack-alipay/
  modules/
    gateways/
```

Upload or copy this directory to your WHMCS root:

```text
whmcs-peakrack-alipay/modules
```

Expected files after upload:

```text
modules/gateways/alipay.php
modules/gateways/alipay/lib.php
modules/gateways/alipay/logo.png
modules/gateways/alipay/logo-icon.png
modules/gateways/alipay/whmcs.json
modules/gateways/callback/alipay.php
```

Then enable `Alipay (支付宝)` in WHMCS payment gateways.

## Configuration

Fill in the gateway settings:

- `Admin Language`, save after selecting `zh` or `en`
- `App ID`
- `Application Private Key`
- `Alipay Public Key`
- `Seller ID / PID`, optional but recommended
- `Order Prefix`
- `Product Code`, usually `FAST_INSTANT_TRADE_PAY`
- `Payment Timeout`, for example `30m`

For USD stores or multi-currency WHMCS installs, set the gateway common setting:

```text
Convert To For Processing = CNY
```

WHMCS will convert the invoice amount to CNY before redirecting the customer to Alipay. The callback verifies the CNY amount from Alipay and then applies payment to the original WHMCS invoice.

## Callback URL

The module passes the asynchronous callback URL dynamically:

```text
https://your-whmcs.example/modules/gateways/callback/alipay.php
```

The WHMCS site must be publicly reachable by Alipay over HTTPS.

## Display Text

The Alipay checkout page uses:

- Product name: first WHMCS invoice item description when available
- Product description: invoice-level text such as `Company - Invoice #123`

This display text does not control payment allocation or provisioning. WHMCS provisioning remains tied to the invoice ID and successful invoice payment.

## Notes

Default WHMCS `standard_cart` payment method radio lists do not automatically render gateway logos. The included `logo.png` is available for WHMCS gateway metadata, and `logo-icon.png` is used inside the invoice payment button.

## Release Notes

### 1.1.0

- Added signed return handling so successful browser returns can refresh WHMCS invoice state more reliably.
- Improved UTF-8 handling for Alipay display text.
- Marked package metadata as MIT for open-source release.

### 1.1.1

- Renamed the release package to `whmcs-peakrack-alipay`.
- Normalized the deployable files under `whmcs-peakrack-alipay/modules` for consistent WHMCS gateway releases.

### 1.1.2

- Added a sectioned WHMCS gateway configuration UI.
- Added a saved Chinese/English admin language selector for gateway configuration labels.
- Kept payment request, callback, and invoice application logic unchanged.

Detailed upgrade notes: [UPGRADE.md](UPGRADE.md).

## Disclaimer

This is an independent WHMCS payment gateway module. It is not affiliated with, endorsed by, or sponsored by WHMCS or Alipay. WHMCS and Alipay trademarks belong to their respective owners.

## License

MIT License. See [LICENSE](LICENSE).

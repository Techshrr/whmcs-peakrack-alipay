# Upgrade Notes

## 1.1.3

- Repository layout only: gateway files now live at repository root as `alipay.php`, `alipay/`, and `callback/alipay.php`.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy `alipay.php` and `alipay/` to `modules/gateways/`, and copy `callback/alipay.php` to `modules/gateways/callback/alipay.php`.
- Replaced the saved admin-language dropdown with Risk-style language buttons that switch immediately and persist in the admin browser.
- Package metadata version bumped to `1.1.3`.

## 1.1.2

- Added a sectioned native WHMCS gateway configuration UI.
- Added a saved `Admin Language` selector for Chinese or English configuration labels.
- No database migration is required. WHMCS will store the new `adminLanguage` gateway setting after you save the gateway configuration once.
- Existing live credentials, callbacks, and invoice payment behavior are unchanged.
- Package metadata version bumped to `1.1.2`.

## 1.1.1

- Repository layout only: deployable files now live under `whmcs-peakrack-alipay/modules`.
- Existing WHMCS installs do not need database changes for this release.
- When updating manually, copy the new `whmcs-peakrack-alipay/modules` directory contents over your WHMCS root.
- Package metadata version bumped to `1.1.1`.

## 1.1.0

- Added signed return handling so successful browser returns can refresh WHMCS invoice state more reliably.
- Improved UTF-8 handling for Alipay display text.
- Marked package metadata as MIT for open-source release.

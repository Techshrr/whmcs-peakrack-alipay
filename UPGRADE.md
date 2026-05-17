# Upgrade Notes

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

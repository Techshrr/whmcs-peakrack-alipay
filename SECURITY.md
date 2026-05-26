# Security Policy

## Reporting a vulnerability

Please do not open public GitHub issues for security vulnerabilities.

Report Alipay signing, callback verification, amount-validation, or invoice-crediting issues to:

security@peakrack.com

Please include:

- Affected gateway version, WHMCS version, and PHP version
- Whether the issue affects payment creation, browser return, or notify callback handling
- Description of the issue and reproduction steps
- Potential impact on invoice payment state or callback validation
- Suggested mitigation, if available

## Supported versions

| Version | Supported |
|---|---|
| 1.x | Yes |
| < 1.0 | No |

## Sensitive data

Do not include production App IDs, seller IDs, application private keys, Alipay public keys from production accounts, signed callback payloads, transaction IDs, real invoice numbers, customer data, WHMCS license data, or production callback URLs in public reports.

## Public issues

Installation problems, sandbox configuration questions, and documentation fixes may be submitted through GitHub Issues.

Security vulnerabilities must be reported privately by email.

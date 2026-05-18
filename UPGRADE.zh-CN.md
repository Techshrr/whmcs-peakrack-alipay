# 升级说明

## 1.1.4

- 增加可见的 `后台语言 / Admin Language` 网关配置项，避免 WHMCS 不渲染自定义 `System` 网关字段时看不到语言控制。
- 如果 WHMCS 允许配置描述渲染 HTML，会在语言设置旁显示中文 / English 快速切换按钮。
- 发布包元数据版本号升级到 `1.1.4`。

## 1.1.3

- 仅调整仓库展示结构：网关文件现在位于仓库根目录 `alipay.php`、`alipay/` 和 `callback/alipay.php`。
- 已安装站点升级到此版本不需要修改数据库。
- 手动更新时，把 `alipay.php` 和 `alipay/` 覆盖上传到 `modules/gateways/`，把 `callback/alipay.php` 覆盖上传到 `modules/gateways/callback/alipay.php`。
- 将原来的后台语言保存下拉框改为 Risk 风格语言按钮，点击后立即切换并记住当前管理员浏览器选择。
- 发布包元数据版本号升级到 `1.1.3`。

## 1.1.2

- 优化 WHMCS 原生支付网关配置页，增加分区式配置说明。
- 增加可保存的 `后台语言 / Admin Language` 选择项，可切换中文或英文配置字段。
- 不需要数据库迁移。保存一次网关配置后，WHMCS 会自动保存新的 `adminLanguage` 设置。
- 现有正式密钥、回调地址和发票入账逻辑不变。
- 发布包元数据版本号升级到 `1.1.2`。

## 1.1.1

- 仅调整仓库发布目录结构：可部署文件现在位于 `whmcs-peakrack-alipay/modules`。
- 已安装站点升级到此版本不需要修改数据库。
- 手动更新时，把新的 `whmcs-peakrack-alipay/modules` 目录内容覆盖上传到 WHMCS 根目录即可。
- 发布包元数据版本号升级到 `1.1.1`。

## 1.1.0

- 增加带签名的同步返回处理，让客户浏览器返回后更可靠地刷新 WHMCS 发票状态。
- 优化支付宝页面展示文字的 UTF-8 处理。
- 将发布包元数据统一标记为 MIT 开源协议。

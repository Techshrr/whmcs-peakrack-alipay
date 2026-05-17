# WHMCS PeakRack 支付宝支付网关

用于 WHMCS 9.x 的支付宝电脑网站支付网关模块，支持 `alipay.trade.page.pay`、RSA2 签名、异步通知回调，以及 WHMCS 多货币转换为人民币支付。

English documentation: [README.md](README.md)

## 功能

- 支付宝电脑网站支付 `alipay.trade.page.pay`
- RSA2 请求签名和回调验签
- WHMCS 发票回调入账
- 支持 WHMCS `Convert To For Processing = CNY`
- 支持 USD 默认货币、CNY 支付处理
- 支付宝返回金额校验
- 后台配置项中英双语
- 客户前台按钮和错误提示中英文切换
- 包含 WHMCS 网关 logo 和支付按钮图标

## 环境要求

- WHMCS 9.x 自托管安装
- PHP OpenSSL 扩展
- 支付宝开放平台应用
- 已开通支付宝电脑网站支付产品

本模块使用支付宝开放平台普通公钥模式和 RSA2，不支持证书模式。

## 安装

仓库根目录保留说明文档，真正用于上传部署的文件放在 `whmcs-peakrack-alipay` 发布目录中：

```text
whmcs-peakrack-alipay/
  modules/
    gateways/
```

把这个路径上传到 WHMCS 根目录：

```text
whmcs-peakrack-alipay/modules
```

上传后应包含：

```text
modules/gateways/alipay.php
modules/gateways/alipay/lib.php
modules/gateways/alipay/logo.png
modules/gateways/alipay/logo-icon.png
modules/gateways/alipay/whmcs.json
modules/gateways/callback/alipay.php
```

然后在 WHMCS `系统设置 > 支付网关` 中启用 `Alipay (支付宝)`。

## 后台配置

填写以下字段：

- `App ID`
- `应用私钥 / Application Private Key`
- `支付宝公钥 / Alipay Public Key`
- `Seller ID / PID`，可选但建议填写
- `订单号前缀 / Order Prefix`
- `Product Code`，通常为 `FAST_INSTANT_TRADE_PAY`
- `支付超时 / Payment Timeout`，例如 `30m`

如果 WHMCS 默认货币是 USD，支付宝收款使用 CNY，请把该网关公共设置里的：

```text
Convert To For Processing
```

设置为：

```text
CNY
```

WHMCS 会在客户跳转支付宝前按后台汇率换算成人民币。支付宝回调后，模块会校验人民币支付金额，再让 WHMCS 按该发票当前余额入账。

## 回调地址

模块会在每次支付请求中动态传入异步通知地址：

```text
https://你的WHMCS域名/modules/gateways/callback/alipay.php
```

站点必须能被支付宝服务器通过公网 HTTPS 访问。

## 支付宝页面展示

支付宝页面上的商品名称会优先使用 WHMCS 发票第一条项目描述的清洗后短文本。

商品描述使用：

```text
公司名称 - Invoice #发票号
```

如果发票包含多条项目，会追加 `N items`。这些文字只影响支付宝页面展示，不影响回调入账、发票状态或产品开通。

## 图标说明

模块包含：

- `logo.png`：WHMCS 网关元数据/后台卡片使用
- `logo-icon.png`：发票支付按钮使用

WHMCS 默认 `standard_cart` 订单模板的支付方式选择列表只输出支付方式名称，不会自动读取网关 logo。要在结账页单选支付方式处显示图标，需要额外修改订单模板。

## 更新记录

### 1.1.0

- 增加带签名的同步返回处理，让客户浏览器返回后更可靠地刷新 WHMCS 发票状态。
- 优化支付宝页面展示文字的 UTF-8 处理。
- 将发布包元数据统一标记为 MIT 开源协议。

### 1.1.1

- 将发布包命名统一为 `whmcs-peakrack-alipay`。
- 将可部署文件统一放到 `whmcs-peakrack-alipay/modules`，方便和其他 WHMCS 支付网关仓库保持一致。

详细升级说明见 [UPGRADE.zh-CN.md](UPGRADE.zh-CN.md)。

## 免责声明

本项目是独立开发的 WHMCS 支付网关模块，不隶属于 WHMCS 或支付宝，也未获得其官方背书。WHMCS 和支付宝相关商标归各自权利人所有。

## 开源协议

MIT License。详见 [LICENSE](LICENSE)。

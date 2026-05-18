# WHMCS PeakRack 支付宝支付网关

适用范围：WHMCS 9.x，自托管安装，支付宝开放平台电脑网站支付。

## 文件位置

上传到 WHMCS 根目录后应包含：

- `modules/gateways/alipay.php`
- `modules/gateways/alipay/lib.php`
- `modules/gateways/alipay/logo.png`
- `modules/gateways/alipay/logo-icon.png`
- `modules/gateways/alipay/whmcs.json`
- `modules/gateways/callback/alipay.php`

## 支付宝开放平台配置

本模块使用普通公钥模式，不是证书模式。

需要准备：

- App ID
- 应用私钥
- 支付宝公钥
- 可选：收款账号 PID / Seller ID

异步通知地址由模块自动传给支付宝：

```text
https://你的WHMCS域名/modules/gateways/callback/alipay.php
```

站点必须能被支付宝服务器通过公网 HTTPS 访问。

## WHMCS 后台配置

在 `系统设置 > 支付网关` 中启用 `Alipay (支付宝)`，填写：

- 使用配置页右上角 `中文 / English` 按钮切换后台字段语言
- `App ID`
- `应用私钥 / Application Private Key`
- `支付宝公钥 / Alipay Public Key`
- `Seller ID / PID`，可选但建议填写
- `订单号前缀 / Order Prefix`，多个站点共用同一个支付宝应用时必须不同

如果 WHMCS 默认货币是 USD，支付宝收款使用 CNY，请把该网关的：

```text
Convert To For Processing
```

设置为：

```text
CNY
```

WHMCS 会在客户跳转到支付宝前按后台货币汇率换算成人民币。回调时模块会校验支付宝返回的人民币金额，并让 WHMCS 按发票当前余额入账。

模块会通过支付宝 `passback_params` 回传 WHMCS 发票 ID 和发起支付时的 CNY 金额。该字段必须整体 URL 编码，否则支付宝付款页可能提示“订单信息无法识别 / INVALID_PARAMETER”。

支付宝页面上的商品名称会优先使用 WHMCS 发票第一条项目描述的清洗后短文本。商品描述使用 `公司名称 - Invoice #发票号`，如果发票包含多条项目，会追加 `N items`。这只影响支付宝页面展示，不影响回调入账、发票状态或产品开通。

## 语言显示

客户前台支付按钮和错误提示会按客户语言在中文和英文之间切换：

- 中文客户：显示中文
- 非中文客户：显示英文

后台配置页内置分区说明，并提供右上角 `中文 / English` 语言按钮。点击后会立即切换，并记住当前管理员浏览器选择。

## 图标

模块已包含：

- `logo.png`：WHMCS 网关模块卡片/后台元数据使用
- `logo-icon.png`：发票支付按钮内使用

WHMCS 默认 `standard_cart` 订单模板的支付方式单选列表只输出支付方式名称，不自动读取网关 logo。若要在结账页“选择支付方式”列表中显示图标，需要额外改订单模板。

可在 `templates/orderforms/standard_cart/checkout.tpl` 的支付方式循环里，把：

```smarty
{$gateway.name}
```

替换成按网关名判断并输出图标的自定义 HTML。升级 WHMCS 或更换订单模板时，需要重新检查这类模板改动。

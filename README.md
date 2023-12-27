<h1 align="center">Robot</h1>

<p align="center">🤖️ 轻松给钉钉/企业微信/飞书机器人发送消息</p>


## 特点

1. 支持群发   
2. 基于配置，易于管理
3. 统一的返回值格式, 方便监控
4. 易于扩展

## 环境需求

- PHP >= 7.1

## 安装

```shell
composer require mitoop/robot
```

## 使用

```php
use Mitoop\Robot\Robot;

$config = [
     // 默认发送的分组 支持多个
    'default' => ['feishu.jishu'],
     //  bool值 是否显示服务器env 为 true 消息头部将会显示服务器env 未配置时缺省为 true
    'show_env' => true, 
    // 发送消息的服务器env 如: production/development/local 等, 表示消息来自哪个环境
    'env' => 'production', 
    // 发送通知的超时时间(秒)
    'timeout' => 5, 
    // 机器人提供商 feishu : 飞书 / wecom : 企业微信 / dingding : 钉钉 / lark : Lark
    'channels' => [
        // 飞书
        'feishu' => [
            // demo 技术组
            'jishu' => [ 
                // 【必填】webhook 地址, 可以只填写最后的 uuid, 当然也支持全链接
                'webhook' => '00000000-0000-0000-0000-000000000000',
                // 【可选】分组默认 at 的成员 手机号或者/all
                'at' => ['all'],
                // 【可选】校验密钥 
                'secret' => '', 
            ]
        ],
       // 配置同 feishu 相同(v2.1 新增)
       'lark' => [
            // demo 技术组
            'jishu' => [ 
                // 【必填】webhook 地址, 可以只填写最后的 uuid, 当然也支持全链接
                'webhook' => '00000000-0000-0000-0000-000000000000',
                // 【可选】分组默认 at 的成员 手机号或者/all
                'at' => ['all'],
                // 【可选】校验密钥 
                'secret' => '', 
            ]
        ],
        // 企业微信 配置解释参考飞书(企业微信没有密钥校验策略)
        'wecom' => [
            'jishu' => [
                // 【必填】webhook 地址, 可以只填写最后的 key, 当然也支持全链接
                'webhook' => 'key',
                'at' => ['all'], 
            ],
        ],
        // 钉钉 配置解释参考飞书
        'dingding' => [
            'jishu' => [
                // 【必填】webhook 地址, 可以只填写最后的 access_token, 当然也支持全链接
                'webhook' => 'access_token',
                'at' => ['13888888888'],
                'secret' => ''
            ],
        ],
    ],
];

$robot = new Robot($config);

$robot->sendMarkdownMsg('### Markdown');

```

## 支持的消息类型

各家机器人消息类型都有多种，而且不尽相同。

我们提供两种最常用的: 发送文本消息和发送Markdown消息, v2.1新增发送原始消息

1. 发送文本消息

```php
$robot->sendTextMsg('debug回调信息', [
    'method' => 'callback/get_item_detail',
    'ip' => '127.0.0.1',
    'ua' => 'okhttp/3.12.3',
    'request_data' => [
       'foo' => 'bar',
    ],
    'time' => 1619754172,
]);
```

2. 发送markdown消息(飞书目前不支持markdown消息)

```php
$markdownMessage = "### 五一值班安排 \n
五月一号：技术: 技术甲, 客服: 客服甲 \n
五月二号：技术: 技术乙, 客服: 客服乙 \n
五月三号：技术: 技术丙, 客服: 客服丙 \n
五月四号：技术: 技术丁, 客服: 客服丁";

$robot->sendMarkdownMsg($markdownMessage);
```

各家markdown都是标准Markdown的子集，而且不太相同，所以可以根据 `$channel` 参数类型来判断返回值，例如：

```php

$dingdingMessage = "### 五一值班安排 \n";
$wecomMessage = "### <font color='info'>五一值班安排</font> ";

use Mitoop\Robot\Channels\Channel;

$robot->group(['dingding.jishu', 'wecom.jishu'])
      ->sendMarkdownMsg(function(Channel $channel)use($dingdingMessage, $wecomMessage){
         if($channel->getName() == 'wecom') {
             return $wecomMessage;
         }
         
         if($channel->getName() == 'dingding') {
            return $dingdingMessage;
         }
      });
```

3. v2.1 新增发送原始消息, 发送原始消息时, 配置项中的`show_env`, `at`配置项将不起作用.

```php
$data = []; // 各家原始的消息体

$robot->sendRawMsg($data);

$robot->group(['dingding.jishu', 'wecom.jishu'])->sendRawMsg($data);
```

## 发送群组

默认向 `default` 中的群组发送消息，如果向其他群组发送消息，调用 `group` 方法即可，支持多个群组发送。

```php
$robot->group(['feishu.kefu', 'wecom.jishu'])
      ->sendTextMsg('...', [...]);
```

## 消息中@其他成员

消息默认会@对应群组配置中 `at` 里的成员，如果要自定义@成员，传入 `at` 参数即可 

`sendTextMsg` 和 `sendMarkdownMsg` 都支持传入 `at` 参数

```php
$at = 'all';
$at = ['all'];
$at = function(Channel $channel){
         if($channel->getName() == 'wecome') {
             return ['mitoop'];
         }
         
         if($channel->getName() == 'dingding') {
            return ['13888888888'];
         }
      };

$robot->group(['feishu.kefu', 'wecom.jishu'])
      ->sendTextMsg('...', [...], $at); // 这里的 $at 将会覆盖 group 配置中的 `at`
```

## 返回值

由于支持群发，所以返回值为一个数组，结构如下：
```php
[
    'feishu.jishu' => [
        'status' => 'success', // 成功
        'result' => [...] // 平台返回值
    ],
    'wecom.jishu' => [
        'status' => 'failure', // 失败
        'exception_msg' => '', // 异常信息
        'exception_file' => '', // 异常文件以及行数
        'response' => '', // 平台返回的信息
    ],
    //...
]
```

## 自定义channel

支持自定义发送channel，自定义的channel类可继承 `Mitoop\Robot\Channels\Channel` 类

```php
$config = [
    ...
    'channels' => [
        ...
        'my-channel' => [
           'jishu' => [
              'webhook' => ''
           ],
        ] 
    ]
];

$robot = new Robot($config);

// 注册
$robot->extend('my-channel', function($channelConfig){
    // $channelConfig 来自配置文件里的 `channels.my-channel`
    return new MyChannel($channelConfig);
});
// 调用
$robot->group('my-channel.jishu')
      ->sendTextMsg('自定义通道', [
           'msg' => 'success'    
      ]);
```


## 参考

- [钉钉](https://developers.dingtalk.com/document/app/custom-robot-access)
- [企业微信](https://work.weixin.qq.com/api/doc/90000/90136/91770)
- [飞书](https://open.feishu.cn/document/ukTMukTMukTM/uMDMxEjLzATMx4yMwETM)
- [Lark](https://open.larksuite.com/document/client-docs/bot-v3/add-custom-bot)

## ⚠️注意

1. 服务商发送频率限制 
   1. 飞书(Lark同)：客服反馈目前没有频率限制
   2. 企业微信：每个机器人发送的消息不能超过20条/分钟 [点击查看详情](https://work.weixin.qq.com/api/doc/90000/90136/91770#%E6%B6%88%E6%81%AF%E5%8F%91%E9%80%81%E9%A2%91%E7%8E%87%E9%99%90%E5%88%B6)
   3. 钉钉：每个机器人每分钟最多发送20条。如果超过20条，会限流10分钟 [点击查看详情](https://developers.dingtalk.com/document/app/invocation-frequency-limit)
2. 消息大小(消息字数太多，可能会发送失败)
   1. 飞书(Lark同)：建议 JSON 的长度不超过 30k [点击查看详情](https://open.feishu.cn/document/ugTN1YjL4UTN24CO1UjN/uYzN1YjL2cTN24iN3UjN)
   2. 企业微信：未找到文档 不建议太大
   3. 钉钉：未找到文档 不建议太大

## License

MIT

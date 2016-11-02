# Sudiyi Ruby Marshal for PHP

## 概述



## 运行环境
- PHP 5.4+
- mbstring extension

## 安装方法

1. 如果您通过composer管理您的项目依赖，可以在你的项目根目录运行：

        $ composer require sudiyi/ruby-marshal

   或者在你的`composer.json`中声明对速递易开放平台 SDK 的依赖：

        "require": {
            "sudiyi/ruby-marshal": "~1.0"
        }

   然后通过`composer install`安装依赖。

2. 下载SDK源码，在您的代码中引入 SDK 目录下的`autoload.php`文件：

       require_once '/path/to/ruby_marshal/autoload.php';

## SDK 主要目录结构

```
|-- example
|   `-- demo.php         案例程序代码
|-- src
|   |-- Helper.php          帮助类
|   |-- Ints.php            辅助类
|   |-- RubyMarshalLoad.php 主程序类
|   `-- RubyMarshalException.php    异常类
|-- autoload.php            PSR-4 自动加载
`-- composer.json
```

### 异常处理

SDK 执行过程中若遇到异常，将会抛出一个 RubyMarshalException 异常，用户可自行捕获并处理。

```php
use SuDiYi\RubyMarshal\RubyMarshalLoad;
use SuDiYi\RubyMarshal\UnEscape;
use SuDiYi\RubyMarshal\RubyMarshalException

try {
    $rubyMarshalLoad = new RubyMarshalLoad();
    $content = 'marshal-str'
    $arr = $rubyMarshalLoad->load($content);
} catch (RubyMarshalException $e) {
    echo "============== ERROR ==============\n";
    echo $e->getMessage() . "\n";
    var_dump($e->getErrorBody());
    echo "===================================\n";
}
```

### 运行Sample程序

1. 执行 `php ./example/demo.php`

## 问题反馈

Goto: [ISSUES](https://github.com/sudiyi/ruby_marshal/issues)

## 开源协议

MIT



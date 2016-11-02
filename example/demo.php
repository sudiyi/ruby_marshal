<?php
require getcwd() . '/autoload.php';
use Sudiyi\RubyMarshal\RubyMarshalLoad;
use Sudiyi\RubyMarshal\UnEscape;

$data = 'BAhbCUkiAAY6BkVUaRFJIglzZHNhBjsAVGkX%0A';
$data = UnEscape::toUnEscape($data);
$rubyMarshalLoad = new RubyMarshalLoad();
$content = base64_decode($data);
$arr = $rubyMarshalLoad->load($content);
print_r($arr);
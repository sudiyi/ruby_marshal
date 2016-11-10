<?php
require __DIR__ . '/../autoload.php';
use Sudiyi\RubyMarshal\RubyMarshalLoad;
use Sudiyi\RubyMarshal\UnEscape;

$rubyMarshalLoad = new RubyMarshalLoad();

$arr = [ 'aa', 'bb', 'bb', 'cc' ];
$dumpStr = $rubyMarshalLoad->dump($arr);
$base = \Sudiyi\RubyMarshal\Helper::binToString($dumpStr);
$encodeStr =  base64_encode($base);
$content = base64_decode($encodeStr);
$arr = $rubyMarshalLoad->load($content);
print_r($arr);

<?php
require getcwd() . '/autoload.php';
use SuDiYi\RubyMarshal\RubyMarshalLoad;
use SuDiYi\RubyMarshal\UnEscape;

$data = 'BAh7BzoGYVsJSSIGYQY6BkVUSSIGIAY7BlRJIgZiBjsGVEkiBmMGOwZUOgZi%0AZg4xMjMxMi4xMTE%3D%0A';
$data = UnEscape::toUnEscape($data);
$rubyMarshalLoad = new RubyMarshalLoad();
$content = base64_decode($data);
$arr = $rubyMarshalLoad->load($content);
print_r($arr);
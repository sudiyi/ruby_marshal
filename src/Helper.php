<?php
namespace Sudiyi\RubyMarshal;

class Helper
{
    public static function binToString($buffer)
    {
        $str = '';
        foreach ($buffer as $value){
            $str .= pack('C',$value);
        }
        $codeWay = mb_detect_encoding($str);
        return iconv($codeWay,'UTF-8',$str);
    }
}
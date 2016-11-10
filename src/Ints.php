<?php
namespace Sudiyi\RubyMarshal;

class Ints
{
    public static function length($initialByte)
    {
        switch($initialByte) {
            case 4:
            case 252:
                return 5;
            case 3:
            case 253:
                return 4;
            case 2:
            case 254:
                return 3;
            case 1:
            case 255:
                return 2;
            case 0:
            default:
                return 1;
        }
    }

    public static function load($buffer)
    {
        switch(count($buffer)) {
            case 1:
                if($buffer[0] === 0) return 0;
                $num = $buffer[0];

                if($num > 0) {
                    $num -= 5;
                } else {
                    $num += 5;
                }
                return $num;
            case 2:
                $num = self::readUInt8($buffer,1);
                if($buffer[0] === 255) {
                    $num = -(256 - $num);
                }
                return $num;
            case 3:
                $num = self::readUInt16LE($buffer,1);
                if($buffer[0] === 254) {
                    $num = -(65536 - $num);
                }
                return $num;
            case 4:
                $tmpBuffer = array_merge(array_slice($buffer,1,count($buffer)),[0]);
                $num = self::readUInt32LE($tmpBuffer,0);
                if($buffer[0] === 253) {
                    $num = -(16777216 - $num);
                }
                return $num;
            case 5:
                return self::readUInt32LE($buffer,1);
            default:
                throw new RubyMarshalException('This is not an int');
        }
    }

    public static function dump($input)
    {
        if($input == 0 ) return [0];
        if($input > 0){
            if($input > 0) {
                if($input < 123) {
                    return [$input + 5];
                } else if($input < 256) {
                    return [1, $input];
                } else if($input < 65536) {
                    $unsignedInt = [0xFF,0xFF];
                    $unsignedInt = self::writeUInt16LE($unsignedInt,$input, 0);
                    return array_merge([2], $unsignedInt);
                } else if ($input < 16777216) {
                    $unsignedInt = [0xFF,0xFF,0xFF,0xFF];;
                    $unsignedInt = self::writeUInt32LE($unsignedInt,$input, 0);
                    return array_merge([3],array_slice($unsignedInt,0,count($unsignedInt)));
                } else {
                    $unsignedInt = [0xFF,0xFF,0xFF,0xFF];;
                    $unsignedInt = self::writeUInt32LE($unsignedInt,$input, 0);
                    return array_merge([4],$unsignedInt);
                  }
            } else {
                if($input > -124) {
                    return [$input - 5];
                } else if($input > -257) {
                    return [255,$input];
                } else if($input > -65537) {
                    $unsignedInt = [0xFF,0xFF];
                    $unsignedInt = self::writeUInt16LE($unsignedInt,65536 - abs($input), 0);
                    return array_merge([254,$unsignedInt]);
                } else if ($input > -16777217) {
                    $unsignedInt = [0xFF,0xFF,0xFF,0xFF];
                    $unsignedInt = self::writeUInt32LE($unsignedInt,16777216 - abs($input), 0);
                    array_merge([253],array_slice($unsignedInt,0,count($unsignedInt)));
                } else {
                    $unsignedInt = [0xFF,0xFF,0xFF,0xFF];
                    $unsignedInt = self::writeUInt32LE($unsignedInt, 4294967296 - abs($input), 0);
                    return array_merge([252],$unsignedInt);
                }
            }
        }
        return [];
    }

    protected static function initBuffer(&$buffer)
    {
        array_walk($buffer,function(&$value,$key){
            $value = $key == 0 ? 0x01 : 0;
        });
    }

    protected static function writeUInt16LE($buffer,$input,$offset)
    {
        self::initBuffer($buffer);
        $lowBit = 0x00FF & $input;
        $highBit = 0xFF00 & $input;
        $buffer[$offset] = $lowBit;
        $buffer[$offset + 1] = $highBit;
        return $buffer;
    }

    protected static function writeUInt32LE($buffer,$input,$offset)
    {
        for($i = 0;  $i <= 3 ;$i ++){
            $leftBit = (8 * $i);
            $bit = ( 0xFF << $leftBit ) & $input;
            $bit = $bit >> $leftBit;
            if( ! isset($buffer[$offset + $i])){
                throw new RubyMarshalException('RangeError: Index out of range');
            }
            $buffer[$offset + $i] = $bit;
        }
        return $buffer;
    }



    protected static function readUInt8($buffer,$offset)
    {
        $bit = $buffer[$offset];
        return $bit;
    }

    protected static function readUInt16LE($buffer,$offset)
    {
        $lowBit = $buffer[$offset];
        $highBit = $buffer[$offset + 1];
        $highBit = $highBit << 8;
        return $highBit + $lowBit;
    }

    protected static function readUInt32LE($buffer,$offset)
    {
        $bit = 0;
        for ($i = $offset; $i <= $offset + 3; $i++){
            $leftBit = 0;
            $i > $offset && $leftBit = ($i * 8);
            $bit += $buffer[$i] << $leftBit;
        }
        return $bit;
    }
}
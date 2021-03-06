<?php
namespace Sudiyi\RubyMarshal;

class RubyMarshalLoad
{
    const MARSHAL_MAJOR   = 4;
    const MARSHAL_MINOR   = 8;

    const MARSHAL_TRUE = 'T';
    const MARSHAL_FALSE = 'F';
    const MARSHAL_NULL = '0';
    const MARSHAL_ARRAY = '[';
    const MARSHAL_HASH = '{';
    const MARSHAL_INT = 'i';
    const MARSHAL_SYM = ':';
    const MARSHAL_SYM_REF = ';';
    const MARSHAL_INSTANCEVAR = 'I';
    const MARSHAL_IVAR_STR = '"';
    const MARSHAL_FLOAT = 'f';

    static protected $offset = 0;
    static protected $stringEncodingOffset;

    public function load($content) {
        $input = unpack('C*', $content);
        list($major, $minor) = array_slice($input,0,2);
        if ($major != self::MARSHAL_MAJOR || $minor != self::MARSHAL_MINOR)
            throw(new RubyMarshalException("Invalid binary file"));
        $input = array_slice($input,2,count($input));
        $symbols = [];
        return $this->identifyNextToken($input,$symbols);
    }

    protected function identifyNextToken($buffer, &$symbols) {
        $type = pack('C',$buffer[self::$offset]);
        switch ($type) {
            case self::MARSHAL_TRUE:
                self::$offset += 1;
                return true;
            case self::MARSHAL_FALSE:
                self::$offset += 1;
                return false;
            case self::MARSHAL_NULL:
                self::$offset += 1;
                return null;
            case self::MARSHAL_INT:
                $length = Ints::length($buffer[self::$offset + 1]);
                $slice = array_slice($buffer,self::$offset + 1, $length);
                self::$offset += $length + 1;
                return  Ints::load($slice);
            case self::MARSHAL_FLOAT:
                $length = Ints::load(array_slice($buffer, self::$offset + 1, 1));
                $tempBuf = array_slice($buffer, self::$offset + 2, $length);
                self::$offset += $length + 2;

                if(Helper::binToString($tempBuf) === 'inf') return INF;
                if(Helper::binToString($tempBuf) === '-inf') return -INF;
                return floatval(Helper::binToString($tempBuf));
            case self::MARSHAL_SYM:
                $length = Ints::load(array_slice($buffer, self::$offset + 1, 1));
                $tempBuf = array_slice($buffer, self::$offset + 2, $length);
                self::$offset += $length + 2;
                $sym = Helper::binToString($tempBuf);
                array_push($symbols,$sym);
                return $sym;
            case self::MARSHAL_SYM_REF:
                $index = Ints::load(array_slice($buffer, self::$offset + 1, 1));
                self::$offset += 2;
                return $symbols[$index - 1];
            case self::MARSHAL_IVAR_STR:
                return self::parseString($buffer);
            case self::MARSHAL_INSTANCEVAR:
                $ivarType = $buffer[self::$offset + 1];
                $ivarType = pack('C',$ivarType);
                switch($ivarType) {
                    case self::MARSHAL_IVAR_STR:
                        return self::parseString($buffer);
                    default:
                        throw new RubyMarshalException('Unrecognised instance variable type' .
                            '(instance variables currently can only be strings)');
                }
            case self::MARSHAL_ARRAY:
                $tokensExpected = Ints::load(array_slice($buffer, self::$offset + 1, 1));
                $elements = [];
                self::$offset += 2;
                for($i = 0; $i < $tokensExpected; $i++) {
                    array_push($elements,self::identifyNextToken($buffer,$symbols));
                }
                return $elements;
            case self::MARSHAL_HASH:
                $tokensExpected = Ints::load(array_slice($buffer, self::$offset + 1, 1)) * 2;
                $hashOut = [];
                self::$offset += 2;
                for($i = 0; $i < $tokensExpected; $i += 2) {
                    $key = self::identifyNextToken($buffer,$symbols);
                    $val = self::identifyNextToken($buffer,$symbols);
                    $hashOut[strval($key)] = $val;
                }
                return $hashOut;
            default:
                throw new RubyMarshalException('Unexpected data, value ' .
                    $buffer[self::$offset] .
                    ' at offset ' . self::$offset .
                    ' on buffer. ' .
                    'Parsing this sort of data is probably not yet implemented!');
        }
    }


    protected static function parseString($buffer)
    {
        $length = Ints::load(array_slice($buffer,self::$offset + 2, 1));
        $isIvar = $buffer[self::$offset + 1] == 34;
        $offsetFastForward = $isIvar ? 3 : 2;
        $tempBuf = array_slice($buffer, self::$offset + $offsetFastForward, $length);
        self::$offset += count($tempBuf) + 3;
        if(isset($buffer[self::$offset + 1]) && pack('C',$buffer[self::$offset + 1]) == self::MARSHAL_SYM) {
            self::$offset += 5;
        } else if(isset($buffer[self::$offset + 1]) && pack('C',$buffer[self::$offset + 1]) == self::MARSHAL_SYM_REF) {
            self::$offset += 4;
        } else if(!isset($buffer[self::$offset + 1])) {

        } else {
            throw new RubyMarshalException('String not terminated with encoding symbol (expected 3a or 3b, got ' .
                $buffer[self::$offset + 1] . '), not sure what to do');
        }
        return Helper::binToString($tempBuf);
    }

    public function dump($value)
    {
        $buffer = [self::MARSHAL_MAJOR,self::MARSHAL_MINOR];
        $buffer = array_merge($buffer, $this->dumpValue($value));
        $arr = [];
        array_walk_recursive($buffer,function (&$v) use(&$arr){
            $arr[] = $v;
        });
        return $arr;
    }

    protected function dumpValue($value)
    {
        if(is_bool($value) && $value == true) return [self::MARSHAL_TRUE];
        if(is_bool($value) && $value == false) return [self::MARSHAL_FALSE];
        if(is_numeric($value)){
            if($value == round($value)){
                if($value == INF){
                    $str = 'inf';
                    $arr = array_merge(
                        unpack("C*",self::MARSHAL_FLOAT),
                        Ints::dump(strlen($str)),
                        unpack('C*',$str)
                    );
                    return $arr;
                } else if($value == -INF){
                    $str = '-inf';
                    $arr = array_merge(
                        unpack("C*",self::MARSHAL_FLOAT),
                        Ints::dump(strlen($str)),
                        unpack('C*',$str)
                    );
                    return $arr;
                } else {
                    return array_merge(
                        unpack("C*",self::MARSHAL_INT),
                        Ints::dump($value)
                    );
                }
            } else {
                $str = Helper::binToString($value);
                $arr = array_merge(
                    unpack("C*",self::MARSHAL_FLOAT),
                    Ints::dump(strlen($str)),
                    unpack("C*",$str)
                );
                return $arr;
            }
        }

        if(is_string($value)){
            $ascii = unpack("C*",self::MARSHAL_IVAR_STR);
            $ascii = array_pop($ascii);
            return self::iVar($ascii,$value);
        }
        
        if(is_array($value)){
            $arr = array_merge(
                unpack("C*",self::MARSHAL_ARRAY),
                Ints::dump(count($value)),
                array_map(function($item){
                    return self::dumpValue($item);
                },$value)
            );
            return $arr;
        }

        if(is_object($value)){
            throw new RubyMarshalException('Not Support PHP object to ruby marshal');;
        }

        return unpack("C*",self::MARSHAL_NULL);
    }

    protected function iVar($type,$value)
    {
        if(self::$stringEncodingOffset) {
            $ascii = unpack("C*",self::MARSHAL_SYM_REF);
            $ascii = array_pop($ascii);
            $trail = [6, $ascii, 00, 84];
        } else {
            $ascii = unpack("C*",self::MARSHAL_SYM);
            $ascii = array_pop($ascii);
            $trail = [6, $ascii, 06, 69, 84];
            self::$stringEncodingOffset = true;
        }

        $arr = array_merge(
            unpack("C*",self::MARSHAL_INSTANCEVAR),
            [$type],
            [strlen($value) + 5],
            unpack("C*",$value),
            $trail
            );
        return $arr;
    }

}
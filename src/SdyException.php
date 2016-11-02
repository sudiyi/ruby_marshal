<?php
namespace SuDiYi\RubyMarshal;

class SdyException extends \Exception
{
    private $details = array();

    function __construct($details)
    {
        if (is_array($details)) {
            $this->details = $details;
            array_pop($details);
            $message = json_encode($details, 320);
            parent::__construct($message);
        } else {
            $message = $details;
            parent::__construct($message);
        }
    }
    public function getHTTPStatus()
    {
        return isset($this->details['status']) ? $this->details['status'] : '';
    }
    public function getErrorCode()
    {
        return isset($this->details['code']) ? $this->details['code'] : '';
    }
    public function getErrorResponse()
    {
        return isset($this->details['response']) ? $this->details['response'] : '';
    }
    public function getErrorBody()
    {
        return isset($this->details['body']) ? $this->details['body'] : '';
    }
}
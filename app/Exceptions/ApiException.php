<?php
namespace App\Exceptions;

class ApiException extends \Exception
{
    public $http_code = 200;

    function __construct($msg='', $err_code, $http_code = 200)
    {
        $this->http_code = $http_code;
        parent::__construct($msg, $err_code);
    }
}

?>
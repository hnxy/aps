<?php

namespace App\Exceptions;

class FileUploadException extends \Exception
{
    public $http_code = 200;

    public function __construct($msg = '', $err_code = -1, $http_code = 200)
    {
        $this->http_code = $http_code;
        parent::__construct($msg, $err_code);
    }
}

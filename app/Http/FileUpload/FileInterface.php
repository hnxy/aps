<?php

namespace App\Http\FileUpload;


interface FileInterface
{
    public function hasFile($path);
    public function isSuccess($code);
    public function getPath($path);
}
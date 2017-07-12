<?php
namespace App\Models;

interface ModelInterface
{

    public function __toString();

    public function __get($name);
}

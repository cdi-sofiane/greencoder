<?php

namespace App\Helper;


use App\Services\JsonResponseMessage;

class JsonDumper

{


    static function dd($data)
    {
         return (new JsonResponseMessage())->setCode(200)->setContent($data)->setError(['text']) . die();
    }
}
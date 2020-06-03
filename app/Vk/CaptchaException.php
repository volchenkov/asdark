<?php

namespace App\Vk;

/**
 *
 */
class CaptchaException extends \Exception
{

    public int $sid;
    public string $img;

}

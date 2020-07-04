<?php

namespace App\Vk;

/**
 * Исключение при прерывании действия с API ВК из-за необходимости проверки капчи
 */
class CaptchaException extends \Exception
{

    public int $sid;
    public string $img;

}

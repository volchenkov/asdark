<?php

namespace App\Vk;

/**
 * Исключение при превышении допустимого числа обращений к API VK
 */
class ErrorResponseException extends \RuntimeException
{

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->view('error', [
            'message' => 'Во время запроса к API ВК произошла ошибка.',
            'todo'    => 'Повторите попытку, если это не поможет - обратитесь к администратору',
            'details' => $this->getMessage()
        ]);
    }

}

<?php

namespace App\Vk;

/**
 * Исключение при превышении допустимого числа обращений к API VK
 */
class FloodControlException extends ErrorResponseException
{

    /**E
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->view('error', [
            'message' => 'Превышено допустимое число обращений к API ВК',
            'todo'    => 'Ничего страшного, подождите несколько секунд и повторите попытку, должно сработать',
            'details' => $this->getMessage()
        ], 429);
    }

}

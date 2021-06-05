<?php

namespace App\Http\Requests\Api;


class MessageRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "api_id" => "required",
            "api_key" => "required",
            "content.nickname" => "required",
            "content.wxid" => "required",
            "content.message.msg_type" => "required",
            "content.message.wxid" => "required",
            "content.message.sender" => "required",
            "content.message.content" => "required",
        ];
    }
}

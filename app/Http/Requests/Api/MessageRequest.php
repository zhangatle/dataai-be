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
            "content_json.nickname" => "required",
            "content_json.wxid" => "required",
            "content_json.message.msg_type" => "required",
            "content_json.message.wxid" => "required",
            "content_json.message.sender" => "required",
            "content_json.message.content" => "required",
        ];
    }
}

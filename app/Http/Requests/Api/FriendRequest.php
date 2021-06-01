<?php

namespace App\Http\Requests\Api;

class FriendRequest extends BaseRequest
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
            "content_json.user_list.*.userid" => "required",
            "content_json.user_list.*.remark" => "required",
            "content_json.user_list.*.nickname" => "required",
            "content_json.user_list.*.user_number" => "required",
        ];
    }
}

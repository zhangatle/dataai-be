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
            "content.nickname" => "required",
            "content.wxid" => "required",
            "content.user_list.*.userid" => "required",
            "content.user_list.*.remark" => "required",
            "content.user_list.*.nickname" => "required",
            "content.user_list.*.user_number" => "required",
        ];
    }
}

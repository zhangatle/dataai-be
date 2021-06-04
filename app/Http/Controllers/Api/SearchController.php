<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\FriendRequest;
use App\Http\Requests\Api\MessageRequest;
use App\Models\Customer;
use App\Models\Friend;
use Carbon\Carbon;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SearchController extends ApiController
{
    /**
     * 获取搜索建议
     */
    public function suggest(Request $request)
    {
        $keywords = $request->input("s", "");
        $suggest_list = [];
        $client = ClientBuilder::create()->build();
        $user = Auth::user();
        $customer_id = $user->customer_id;
        if (!$customer = Customer::query()->where("id", $customer_id)->where("is_active", 1)->first()) {
            return ["message" => "企业不存在"];
        }
        $es_index = "dataai_es_index_" . md5($customer->api_id . $customer->api_key);
        $params = [
            'index' => $es_index,
            'type' => '_doc',
            "body" => [
                "suggest" => [
                    "my-suggest" => [
                        "text" => $keywords,
                        "completion" => [
                            "field" => "suggest",
                            "size" => 10,
//                            "fuzzy" => [
//                                "fuzziness"=> 5,
//                            ]
                        ]
                    ]
                ]
            ]
        ];
        $suggestions = $client->search($params);
        $suggestions = $suggestions["suggest"]["my-suggest"][0]["options"];
        foreach ($suggestions as $item) {
            $source = $item["_source"];
            array_push($suggest_list,Str::limit($source["message_content"], $limit = 80, $end = '...'));
        }
        return $this->success($suggest_list);
    }

    /**
     * 添加好友关系
     */
    public function friend(FriendRequest $request)
    {
        $api_id = $request->input("api_id", "");
        $api_key = $request->input("api_key", "");

        if(!$customer = Customer::query()->where("api_id", $api_id)->where("api_key", $api_key)->where("is_active", 1)->first()){
            return $this->success([], "企业不存在");
        }

        $content_json = $request->input("content_json", []);

        $wxid = $content_json["wxid"];
        $nickname = $content_json["nickname"];
        $user_list = $content_json["user_list"];
        foreach ($user_list as $user) {
            $friend_id = $user["userid"];
            $friend_remark = $user["remark"];
            $friend_nickname = $user['nickname'];
            $friend_number = $user["user_number"];
            $friend = new Friend();
            $friend->customer_id = $customer->id;
            $friend->wxid = $wxid;
            $friend->nickname = $nickname;
            $friend->friend_id = $friend_id;
            $friend->friend_remark = $friend_remark;
            $friend->friend_nickname = $friend_nickname;
            $friend->friend_number = $friend_number;
            try {
                $friend->save();
            }catch (\Exception $exception){
                Log::info($exception);
            }
        }
        return $this->success([]);
    }

    /**
     * 存储记录
     */
    public function addMessage(MessageRequest $request) {
        $client = ClientBuilder::create()->build();

        $api_id = $request->input("api_id","");
        $api_key = $request->input("api_key","");

        $content_json = $request->input("content_json", []);

        $nickname = $content_json["nickname"];
        $wxid = $content_json["wxid"];
        $message_msg_type = $content_json["message"]["msg_type"];
        $message_wxid = $content_json["message"]["wxid"];
        $message_sender = $content_json["message"]["sender"];
        $message_content = $content_json["message"]["content"];


        $es_index = "dataai_es_index_".md5($api_id.$api_key);
        // 判断索引是否存在(如果不存在，则可以直接判定企业不存在)
        if(!$client->indices()->exists(["index"=> $es_index])){
            return $this->success([], "索引错误");
        }

        $suggests = $this->gen_suggest($es_index, [$message_content=>10]);
        $params = [
            "index" => $es_index,
            "type" => "_doc",
            "body" => [
                "nickname" => $nickname,
                "wxid" => $wxid,
                "message_msg_type" => $message_msg_type,
                "message_wxid" => $message_wxid,
                "message_sender" => $message_sender,
                "message_content" => $message_content,
                "add_time" => Carbon::now()->timestamp,
                "suggest" => $suggests,
            ]
        ];
        $client->index($params);
        return $this->success([]);
    }

    /**
     * 生成搜索建议
     * @param $es_index
     * @param $params
     * @return array
     */
    public function gen_suggest($es_index , $params): array
    {
        $client = ClientBuilder::create()->build();
        $suggests = [];
        foreach ($params as $key => $value) {
            $words = $client->indices()->analyze(["index"=>$es_index, "body" => ["analyzer"=> "ik_max_word", "text"=>$key]]);
            $analyzed_words = [];
            foreach ($words["tokens"] as $word) {
                array_push($analyzed_words, $word["token"]);
            }
            array_push($suggests, ["input"=> $analyzed_words, "weight"=> $value]);
        }
        return $suggests;
    }

    /**
     * 获取热门搜索词
     */
    public function hot()
    {
        // 获取热门搜索关键词
        $top_search = Redis::zrevrangebyscore("search_keywords_set", "+inf", "-inf", ["limit" => ["offset" => 0, "count" => 5]]);
        return $this->success($top_search);
    }

    /**
     * 获取搜索结果
     */
    public function search(Request $request)
    {
        $user = Auth::user();
        $customer_id = $user->customer_id;
        if (!$customer = Customer::query()->where("id", $customer_id)->first()) {
            return ["message" => "企业不存在"];
        }
        $es_index = "dataai_es_index_" . md5($customer->api_id . $customer->api_key);
        $page = $request->input("p", 1);
        $keywords = $request->input("q", "");

        if ($keywords == "") {
            return $this->success([]);
        }
        Redis::zincrby("search_keywords_set", 1, Str::limit($keywords, 10, "..."));
        $top_search = Redis::zrevrangebyscore("search_keywords_set", "+inf", "-inf", ["limit" => ["offset" => 0, "count" => 5]]);

        $client = ClientBuilder::create()->build();
        $params = [
            'index' => $es_index,
            'type' => '_doc',
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $keywords,
                        'fields' => [
                            'message_content'
                        ]
                    ]
                ],
                "from" => ($page - 1) * 10,
                "size" => 10,
                "sort" => [
                    "_score"=> [
                        "order" => "desc"
                    ],
                    "add_time"=> [
                        "order" => "desc"
                    ]
                ],
                "highlight" => [
                    "pre_tags" => ['<span class="keyword">'],
                    "post_tags" => ['</span>'],
                    "number_of_fragments" => 0,
                    "fields" => [
                        "message_content" => (object)[],
                    ]
                ]
            ]
        ];
        $start = microtime(true);
        $response = $client->search($params);
        $last_time = microtime(true) - $start;
        $hit_list = [];
        $distinct_score = [];
        foreach ($response['hits']['hits'] as $item) {
            $source = $item["_source"];
            /** 此处应该借助redis提高效率 */
            $group = Friend::query()->where(["wxid" => $source["wxid"], "nickname" => $source["nickname"], "customer_id" => $customer_id, "friend_id" => $source["message_wxid"]])->first();

            /** 有个去重的需要，es本身比较难实现，这里巧妙利用打分机制，打分完成相同的，就只取最新的一条 */
            if(in_array($item["_score"], $distinct_score)){
                continue;
            }else{
                array_push($distinct_score, $item["_score"]);
            }
            array_push($distinct_score, $item["_score"]);
            $group_name = $group ? $group->friend_nickname : "未知";
            $item_arr = [
                "nickname" => $source["nickname"] ?? "未知",
                "wxid" => $source["wxid"] ?? "未知",
                "message_sender" => $source["message_sender"] ?? "未知",
                "message_wxid" => $source["message_wxid"] ?? "未知",
                "message_group" => $group_name,
                "score" => $item["_score"],
                "create_date" => Carbon::parse($source['add_time'])->toDateTimeString()
            ];
            if (isset($item["highlight"]["message_content"])) {
                $item_arr["content"] = nl2br("" . join($item["highlight"]["message_content"]));
            } else {
                $item_arr["content"] = nl2br($source["message_content"]);
            }
            array_push($hit_list, $item_arr);
        }
        $total = $response["hits"]["total"]["value"];
        $res = [
            "page" => $page,
            "hit_list" => $hit_list,
            "total" => $total,
            "page_nums" => $total < 10 ? 1 : intval($total / 10)+1,
            "last_seconds" => $last_time,
            "hot_search" => $top_search,
            "count" => 1,
            "key_words" => $keywords,
        ];
        return $this->success($res);
    }
}

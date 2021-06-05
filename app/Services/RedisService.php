<?php


namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * redis服务
 * Class RedisService
 * @package App\Services
 */
class RedisService
{
    static protected $prefix = "__pf__";
    static protected $suffix = "__sf__";

    static public function cacheObject($name, $keys, $callback, $expire = 180)
    {
        $key = self::makeKey($name, $keys);
        $string = Redis::get($key);
        if (is_null($string)) {
            $data = $callback();
            Redis::setex($key, $expire, serialize(["data" => $data])); // 序列化存储
            return $data;
        } else {
            $pack = unserialize($string);
            return $pack['data'];
        }
    }

    /**
     * 组装redis的key
     * @param $name
     * @param $keys
     * @return string
     */
    static public function makeKey($name, $keys): string
    {
        if (is_array($keys)) {
            $key = self::$prefix . $name . self::$suffix . implode(",", $keys);
        } else {
            $key = self::$prefix . $name . self::$suffix . $keys;
        }
        if (strlen($key) > 64)
            return sha1($key);
        return $key;
    }
}

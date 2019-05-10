<?php
/**
 * Created by PhpStorm.
 * User: qinming
 * Date: 2019/5/9
 * Time: 下午12:16
 */

namespace App\Helpers;


use Illuminate\Support\Facades\Redis;

class IdGenerator
{

    const TIMESTAMP_SHITF = 16;
    const WORKER_SHITF = 12;

    protected $workerId;


    public function __construct($workerId = 1)
    {
        $this->workerId = $workerId;
    }

    protected function timestamp()
    {
        return round(microtime(true) * 1000);
    }

    public function next()
    {
        $timestamp = $this->timestamp();
        return ($timestamp << self::TIMESTAMP_SHITF)
            | ($this->getWorkerId() << self::SEED_SHITF)
            | $this->getCount($timestamp);
    }

    /**
     * 4bit for seed, 可用于业务和环境区分
     * @return int
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }

    /**
     * 12bit for counter ,max 4096
     * @param $timestamp
     * @return int
     */
    public function getCount($timestamp)
    {
        $n = Redis::incr($timestamp) + 1;
        if ($n > 4096) { //2^12
            usleep(200);
            $timestamp = $this->timestamp();
            $n = Redis::incr($timestamp) + 1;
            Redis::expire($timestamp, 2);
        }
        return $n;
    }
}
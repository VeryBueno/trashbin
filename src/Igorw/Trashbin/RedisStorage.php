<?php

namespace Igorw\Trashbin;

use Predis\Client;

class RedisStorage implements Storage
{
    private $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    public function get($id)
    {
        return $this->redis->hgetall($id);
    }

    public function set($id, array $value)
    {
        return $this->redis->hmset($id, $value);
    }

    public function all()
    {
	return $this->redis->keys('*');
    }

    public function delete($id)
    {
        return $this->redis->del($id);
    }
}

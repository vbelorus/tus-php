<?php

namespace TusPhp\Cache;

use Carbon\Carbon;
use Predis\Client as RedisClient;

class RedisStore extends AbstractCache
{
    /** @const string Prefix for redis keys */
    const TUS_REDIS_PREFIX = 'tus:';

    /** @var RedisClient */
    protected $redis;

    /**
     * RedisStore constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->redis = new RedisClient($options);
    }

    /**
     * Get redis.
     *
     * @return RedisClient
     */
    public function getRedis() : RedisClient
    {
        return $this->redis;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, bool $withExpired = false)
    {
        if (false === strpos($key, self::TUS_REDIS_PREFIX)) {
            $key = self::TUS_REDIS_PREFIX . $key;
        }

        $contents = $this->redis->get($key);
        $contents = json_decode($contents, true);

        if ($withExpired) {
            return $contents;
        }

        $isExpired = Carbon::parse($contents['expires_at'])->lt(Carbon::now());

        return $isExpired ? null : $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value)
    {
        $contents = $this->get($key) ?? [];

        if (is_array($value)) {
            $contents = $value + $contents;
        } else {
            array_push($contents, $value);
        }

        $status = $this->redis->set(self::TUS_REDIS_PREFIX . $key, json_encode($contents));

        return 'OK' === $status->getPayload();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key) : bool
    {
        if (false === strpos($key, self::TUS_REDIS_PREFIX)) {
            $key = self::TUS_REDIS_PREFIX . $key;
        }

        return $this->redis->del($key) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function keys() : array
    {
        return $this->redis->keys(self::TUS_REDIS_PREFIX . '*');
    }
}

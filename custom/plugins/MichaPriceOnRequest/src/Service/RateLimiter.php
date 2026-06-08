<?php declare(strict_types=1);

namespace MichaPriceOnRequest\Service;

use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class RateLimiter
{
    private FilesystemAdapter $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter('micha_por_rate_limit', 0, sys_get_temp_dir());
    }

    public function isAllowed(string $ip, int $maxRequests): bool
    {
        $key = 'rate_limit_' . md5($ip);
        $item = $this->cache->getItem($key);

        $data = $item->isHit() ? $item->get() : ['count' => 0, 'expires' => time() + 3600];

        if (time() > $data['expires']) {
            $data = ['count' => 0, 'expires' => time() + 3600];
        }

        if ($data['count'] >= $maxRequests) {
            return false;
        }

        $data['count']++;
        $item->set($data);
        $item->expiresAfter(3600);
        $this->cache->save($item);

        return true;
    }
}
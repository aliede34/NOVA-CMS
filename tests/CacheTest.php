<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Cache.php';

final class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::clear();
    }

    protected function tearDown(): void
    {
        Cache::clear();
    }

    public function testSetAndGet(): void
    {
        $key = 'test_sample_key';
        $value = ['message' => 'NovaCMS Cache Test', 'status' => 200];

        Cache::set($key, $value, 60);
        $retrieved = Cache::get($key);

        $this->assertEquals($value, $retrieved);
    }

    public function testDelete(): void
    {
        $key = 'to_delete_key';
        Cache::set($key, 'deletable_val', 60);
        $this->assertEquals('deletable_val', Cache::get($key));

        Cache::delete($key);
        $this->assertNull(Cache::get($key));
    }

    public function testExpiration(): void
    {
        $key = 'expired_key';
        // 1 saniyelik ttl
        Cache::set($key, 'expire_soon', 1);
        sleep(2);

        $this->assertNull(Cache::get($key));
    }
}


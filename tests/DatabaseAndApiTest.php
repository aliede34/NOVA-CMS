<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config.php';

final class DatabaseAndApiTest extends TestCase
{
    protected function setUp(): void
    {
        initDB();
    }

    public function testDatabaseTablesExist(): void
    {
        $db = getDB();
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

        $this->assertContains('users', $tables);
        $this->assertContains('posts', $tables);
        $this->assertContains('categories', $tables);
        $this->assertContains('tags', $tables);
        $this->assertContains('comments', $tables);
        $this->assertContains('messages', $tables);
        $this->assertContains('subscribers', $tables);
        $this->assertContains('analytics', $tables);
    }

    public function testAdminUserCreated(): void
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();

        $this->assertNotEmpty($admin);
        $this->assertEquals('admin', $admin['role']);
        $this->assertTrue(password_verify('admin123', $admin['password']));
    }

    public function testRateLimitingFunctionality(): void
    {
        $testIp = '192.168.1.99';
        clearLoginAttempts($testIp);

        // Başlangıçta izin verilmeli
        $this->assertTrue(checkLoginRateLimit($testIp));

        // 5 hatalı deneme simüle et
        for ($i = 0; $i < 5; $i++) {
            recordLoginAttempt($testIp);
        }

        // 5. denemeden sonra limit dolmalı
        $this->assertFalse(checkLoginRateLimit($testIp));

        // Temizlendikten sonra tekrar izin verilmeli
        clearLoginAttempts($testIp);
        $this->assertTrue(checkLoginRateLimit($testIp));
    }
}


<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config.php';

final class CoreFunctionsTest extends TestCase
{
    public function testSlugifyTurkishCharacters(): void
    {
        $input = "Bu Bir Türkçe Başlıktır: Şeker, Işık & Ağaç!";
        $expected = "bu-bir-turkce-basliktir-seker-isik-agac";
        $this->assertEquals($expected, slugify($input));
    }

    public function testSlugifySpecialSymbols(): void
    {
        $input = "Hello World / PHP 8.2 & SQLite --- Test";
        $expected = "hello-world-php-8-2-sqlite-test";
        $this->assertEquals($expected, slugify($input));
    }

    public function testCalculateReadTime(): void
    {
        // 200 kelimelik metin 1 dk olmalı
        $words = implode(' ', array_fill(0, 190, 'kelime'));
        $this->assertEquals(1, calculateReadTime($words));

        // 450 kelimelik metin 3 dk olmalı
        $wordsLong = implode(' ', array_fill(0, 450, 'kelime'));
        $this->assertEquals(3, calculateReadTime($wordsLong));
    }

    public function testSanitize(): void
    {
        $malicious = "<script>alert('XSS');</script>";
        $clean = sanitize($malicious);
        $this->assertStringNotContainsString("<script>", $clean);
        $this->assertEquals("&lt;script&gt;alert(&#039;XSS&#039;);&lt;/script&gt;", $clean);
    }
}

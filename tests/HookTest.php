<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Hook.php';

final class HookTest extends TestCase
{
    public function testActionHook(): void
    {
        $executed = false;
        Hook::addAction('custom_test_action', function() use (&$executed) {
            $executed = true;
        });

        Hook::doAction('custom_test_action');
        $this->assertTrue($executed);
    }

    public function testFilterHook(): void
    {
        Hook::addFilter('custom_content_filter', function($content) {
            return $content . ' [Eklenmis Metin]';
        });

        $original = "Merhaba Dunya";
        $filtered = Hook::applyFilters('custom_content_filter', $original);

        $this->assertEquals("Merhaba Dunya [Eklenmis Metin]", $filtered);
    }

    public function testMultipleFiltersWithPriority(): void
    {
        Hook::addFilter('priority_filter', function($val) {
            return $val . '-Birinci';
        }, 10);

        Hook::addFilter('priority_filter', function($val) {
            return $val . '-Ikinci';
        }, 20);

        $result = Hook::applyFilters('priority_filter', 'Basla');
        $this->assertEquals('Basla-Birinci-Ikinci', $result);
    }
}


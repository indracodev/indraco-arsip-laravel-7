<?php

namespace Tests\Unit;

use App\Services\UserPresenceService;
use PHPUnit\Framework\TestCase;

/**
 * Same labels as Laravel 7 UserPresenceService::parseUserAgent (shown on the LAN monitor).
 */
class UserAgentParserTest extends TestCase
{
    public function test_common_clients(): void
    {
        $parser = new UserPresenceService;

        $this->assertSame(
            ['device' => 'Desktop PC', 'os' => 'Windows 10 / 11', 'browser' => 'Microsoft Edge 120', 'device_icon' => 'monitor', 'browser_icon' => 'compass'],
            $parser->parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 Edg/120.0'),
        );
        $this->assertSame(
            ['device' => 'Smartphone Android', 'os' => 'Android 13', 'browser' => 'Chrome 119', 'device_icon' => 'smartphone', 'browser_icon' => 'chrome'],
            $parser->parseUserAgent('Mozilla/5.0 (Linux; Android 13; SM-A135F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0 Mobile Safari/537.36'),
        );
        $this->assertSame('Firefox 128', $parser->parseUserAgent('Mozilla/5.0 (Windows NT 6.1; rv:128.0) Gecko/20100101 Firefox/128.0')['browser']);
        $this->assertSame('Windows / LAN Client', $parser->parseUserAgent('')['os']);
    }
}

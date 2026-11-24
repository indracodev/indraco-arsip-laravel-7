<?php

namespace Tests\Feature\Ops;

use Tests\TestCase;

/**
 * GET /api/health/ping contract used by the desktop client (desktop/src/app.js): its page origin
 * is http://tauri.localhost (exe) or file:// (START-CLIENT-WORKSTATION), so the answer must allow
 * any origin or the client shows "server offline". Production answers from Caddy, dev from Laravel.
 */
class PingContractTest extends TestCase
{
    private const KEYS = ['status', 'server_time', 'server_ip', 'client_ip', 'app'];

    public function test_laravel_route_answers_cross_origin_with_the_client_fields(): void
    {
        $response = $this->getJson('/api/health/ping', ['Origin' => 'http://tauri.localhost']);

        $response->assertOk()
            ->assertJsonStructure(self::KEYS)
            ->assertJson(['status' => 'ok', 'app' => 'DMS PT INDRACO'])
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_caddy_ping_block_has_the_same_fields_and_allows_any_origin(): void
    {
        $caddyfile = (string) file_get_contents(base_path('Caddyfile'));
        $this->assertSame(1, preg_match('/handle \/api\/health\/ping \{(.*?)\n\t\}/s', $caddyfile, $block));

        $this->assertStringContainsString('header Access-Control-Allow-Origin "*"', $block[1]);
        $this->assertStringContainsString('no-cache, no-store, must-revalidate', $block[1]);
        foreach (self::KEYS as $key) {
            $this->assertStringContainsString('"'.$key.'":', $block[1]);
        }
    }
}

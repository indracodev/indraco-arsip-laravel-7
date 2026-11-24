<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\UserPresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PresenceThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function lastSeen(User $user): ?int
    {
        return Cache::get(UserPresenceService::CACHE_KEY_PREFIX.$user->id)['last_activity_at'] ?? null;
    }

    public function test_presence_is_written_at_most_once_per_minute(): void
    {
        $user = User::factory()->create();
        $this->freezeSecond();

        $this->actingAs($user)->get('/')->assertOk();
        $first = $this->lastSeen($user);
        $this->assertNotNull($first);
        $this->assertSame([$user->id], Cache::get(UserPresenceService::ACTIVE_IDS_KEY));

        $this->travel(30)->seconds();
        $this->get('/')->assertOk();
        $this->assertSame($first, $this->lastSeen($user), 'second visit within 60 s must not write');

        $this->travel(31)->seconds();
        $this->get('/')->assertOk();
        $this->assertSame($first + 61, $this->lastSeen($user));
    }

    public function test_json_and_post_requests_do_not_record_presence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/')->assertOk();
        $this->post('/profile/change-password', []);

        $this->assertNull($this->lastSeen($user));
    }
}

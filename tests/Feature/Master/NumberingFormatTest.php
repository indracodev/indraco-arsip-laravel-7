<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Archive\ArchiveFixtures;
use Tests\TestCase;

/**
 * Format Penomoran Box (Laravel 7 NumberingFormatController): admin only, preview without side
 * effect, one active format, Laravel 7 validation, stale counter refused.
 */
class NumberingFormatTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    // ArchiveFixtures is set up by Laravel (setUpArchiveFixtures runs automatically for the trait).

    private int $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = DB::table('numbering_formats')->insertGetId(['name' => 'Standar', 'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}', 'current_counter' => 12, 'padding' => 4, 'is_active' => 1]);
    }

    public function test_opening_the_page_previews_the_next_number_without_moving_the_counter(): void
    {
        $this->actingAs($this->admin)->get('/master/numbering')->assertOk()->assertSee('IND/FIN/2026/0013');
        $this->actingAs($this->admin)->get('/master/numbering')->assertSee('IND/FIN/2026/0013');

        $this->assertSame(12, (int) DB::table('numbering_formats')->value('current_counter'));
    }

    public function test_only_admin(): void
    {
        foreach ([$this->picHcs, $this->gudang] as $user) {
            $this->actingAs($user)->get('/master/numbering')->assertForbidden();
            $this->actingAs($user)->put("/master/numbering/{$this->format}", $this->payload())->assertForbidden();
        }
    }

    public function test_store_activates_exclusively_and_validates_like_laravel_7(): void
    {
        $this->actingAs($this->admin)->post('/master/numbering', ['name' => 'Baru', 'pattern' => '{DEPT}-{COUNTER}', 'current_counter' => 0, 'padding' => 3, 'is_active' => '1'])
            ->assertRedirect('/master/numbering')->assertSessionHas('success', 'Format penomoran box arsip baru berhasil ditambahkan.');
        $this->assertSame(['Baru'], DB::table('numbering_formats')->where('is_active', 1)->pluck('name')->all());
        $this->assertSame('MASTER_NUMBERING_CREATE', DB::table('activity_logs')->value('action'));

        $this->actingAs($this->admin)->post('/master/numbering', ['name' => '', 'pattern' => '', 'current_counter' => -1, 'padding' => 11])
            ->assertSessionHasErrors(['name', 'pattern', 'current_counter', 'padding']);
    }

    public function test_update_sets_the_counter_but_refuses_a_stale_form(): void
    {
        $this->actingAs($this->admin)->put("/master/numbering/{$this->format}", $this->payload(['current_counter' => 20]))
            ->assertSessionHas('success', 'Pengaturan format penomoran box arsip berhasil diperbarui.');
        $this->assertSame(20, (int) DB::table('numbering_formats')->value('current_counter'));

        // A box was numbered after the form was opened (counter 20 -> 21).
        DB::table('numbering_formats')->update(['current_counter' => 21]);
        $this->actingAs($this->admin)->put("/master/numbering/{$this->format}", $this->payload(['current_counter' => 20, 'original_counter' => 20, 'pattern' => 'X-{COUNTER}']))
            ->assertSessionHas('error');
        $this->assertSame([21, '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}'], [(int) DB::table('numbering_formats')->value('current_counter'), DB::table('numbering_formats')->value('pattern')]);

        $this->actingAs($this->admin)->put("/master/numbering/{$this->format}", $this->payload(['current_counter' => 21, 'original_counter' => 21]))->assertSessionHas('success');
        $this->assertSame(0, (int) DB::table('numbering_formats')->value('is_active'), 'unchecked "Aktifkan" deactivates (Laravel 7)');
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace(['name' => 'Standar', 'pattern' => '{COMPANY}/{DEPT}/{YEAR}/{COUNTER}', 'current_counter' => 12, 'padding' => 4, 'original_counter' => 12], $overrides);
    }
}

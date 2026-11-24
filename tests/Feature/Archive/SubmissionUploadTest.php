<?php

namespace Tests\Feature\Archive;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Uploads of the box form go through App\Services\FileStorage: Laravel 7 limits (pdf/jpg/jpeg/png,
 * 500 KB), type from the content, random server name, private disk.
 */
class SubmissionUploadTest extends TestCase
{
    use ArchiveFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpArchiveFixtures();
    }

    /** @return array<string, array{string, string, string}> */
    public static function rejectedUploads(): array
    {
        return [
            'php script' => ['test.php', '<?php echo 1;', 'application/x-php'],
            'double extension' => ['scan.pdf.php', "%PDF-1.4\n%%EOF\n", 'application/pdf'],
            'php disguised as pdf' => ['scan.pdf', '<?php echo 1;', 'application/pdf'],
            'html disguised as png' => ['photo.png', '<html></html>', 'image/png'],
        ];
    }

    #[DataProvider('rejectedUploads')]
    public function test_dangerous_uploads_are_rejected(string $name, string $content, string $clientMime): void
    {
        foreach (['scan_input_form', 'file'] as $field) {
            $this->actingAs($this->picHcs)->post('/archives', $this->submission([$field => $this->upload($name, $content, $clientMime)]))
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, DB::table('archives')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_files_over_500_kb_are_rejected(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['scan_input_form' => $this->pdf('scan.pdf', 501)]))
            ->assertSessionHasErrors('scan_input_form');
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['scan_input_form' => $this->pdf('scan.pdf', 498)]))
            ->assertSessionHasNoErrors();
    }

    public function test_upload_is_stored_privately_under_a_server_name(): void
    {
        $photo = $this->upload('foto box.JPG', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9", 'image/jpeg');

        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['file' => $photo]))->assertSessionHasNoErrors();

        $path = (string) DB::table('archives')->value('file_path');
        $this->assertMatchesRegularExpression('#^archive_digital/[A-Za-z0-9]{40}\.jpg$#', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertFileDoesNotExist(public_path($path));
    }
}

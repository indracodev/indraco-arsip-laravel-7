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

    /** Output of public/js/upload-compress.js (PDF of JPEG pages, /DCTDecode) passes `mimes:pdf`. */
    public function test_browser_compressed_pdf_is_accepted(): void
    {
        $this->actingAs($this->picHcs)->post('/archives', $this->submission(['scan_input_form' => $this->upload('scan besar.pdf', $this->jpegPagesPdf(2), 'application/pdf')]))
            ->assertSessionHasNoErrors();

        $this->assertMatchesRegularExpression('#^archive_scans/[A-Za-z0-9]{40}\.pdf$#', (string) DB::table('archives')->value('scan_input_form'));
    }

    /** Same layout as buildPdf() in upload-compress.js: binary marker, catalog, page tree, page/content/image per page. */
    private function jpegPagesPdf(int $pages): string
    {
        $image = imagecreatetruecolor(200, 283);
        ob_start();
        imagejpeg($image, null, 60);
        $jpeg = (string) ob_get_clean();

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        $object = function (int $id, string $body) use (&$out, &$offsets): void {
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$body}\nendobj\n";
        };
        $object(1, '<< /Type /Catalog /Pages 2 0 R >>');
        $object(2, '<< /Type /Pages /Kids ['.implode(' ', array_map(fn (int $i): string => (3 + $i * 3).' 0 R', range(0, $pages - 1)))."] /Count {$pages} >>");
        for ($i = 0; $i < $pages; $i++) {
            $id = 3 + $i * 3;
            $content = 'q 595.28 0 0 841.89 0 0 cm /Im0 Do Q';
            $object($id, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /XObject << /Im0 '.($id + 2).' 0 R >> >> /Contents '.($id + 1).' 0 R >>');
            $object($id + 1, '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream");
            $object($id + 2, '<< /Type /XObject /Subtype /Image /Width 200 /Height 283 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n{$jpeg}\nendstream");
        }
        $count = 3 + $pages * 3;
        $xref = strlen($out);
        $out .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($k = 1; $k < $count; $k++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$k]);
        }

        return $out."trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}

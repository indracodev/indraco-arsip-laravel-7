<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class QuickNotesController extends Controller
{
    protected function getStoragePath()
    {
        return storage_path('app/quick_notes.json');
    }

    protected function getNoteData()
    {
        $path = $this->getStoragePath();
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'content' => '',
            'updated_at' => null,
            'sender_ip' => null,
            'filename' => null,
            'char_count' => 0,
            'line_count' => 0,
        ];
    }

    public function index(Request $request)
    {
        $note = $this->getNoteData();
        $lanIp = gethostbyname(gethostname());

        return view('quick_notes.index', [
            'note' => $note,
            'lanIp' => $lanIp,
        ]);
    }

    public function apiGet()
    {
        return response()->json($this->getNoteData());
    }

    public function store(Request $request)
    {
        $content = '';
        $filename = 'catatan.txt';

        if ($request->hasFile('note_file')) {
            $file = $request->file('note_file');
            $filename = $file->getClientOriginalName();
            $content = File::get($file->getRealPath());
        } elseif ($request->filled('text_content')) {
            $content = (string) $request->input('text_content');
            if ($request->filled('append') && $request->input('append') == '1') {
                $existing = $this->getNoteData();
                if (!empty($existing['content'])) {
                    $content = rtrim($existing['content']) . "\n\n" . ltrim($content);
                }
            }
        }

        $charCount = mb_strlen($content);
        $lineCount = substr_count($content, "\n") + ($charCount > 0 ? 1 : 0);

        $data = [
            'content' => $content,
            'updated_at' => date('d-m-Y H:i:s'),
            'sender_ip' => $request->ip(),
            'filename' => $filename,
            'char_count' => $charCount,
            'line_count' => $lineCount,
        ];

        $dir = storage_path('app');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($this->getStoragePath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dikirim!',
                'note' => $data,
            ]);
        }

        return redirect()->route('quick_notes.index')->with('success', 'Catatan berhasil disimpan dan diperbarui!');
    }

    public function download()
    {
        $note = $this->getNoteData();
        $content = $note['content'] ?? '';
        $filename = $note['filename'] ?: ('catatan_' . date('Ymd_His') . '.txt');

        if (!str_ends_with(strtolower($filename), '.txt')) {
            $filename .= '.txt';
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function clear(Request $request)
    {
        $path = $this->getStoragePath();
        if (File::exists($path)) {
            File::delete($path);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dikosongkan.',
            ]);
        }

        return redirect()->route('quick_notes.index')->with('success', 'Catatan berhasil dikosongkan.');
    }
}

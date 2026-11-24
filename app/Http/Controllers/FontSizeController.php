<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Simpan" in Pengaturan Tampilan stores the font size on the account (users.font_size), so it
 * follows the user to another PC. The layout renders it before first paint (public/js/prefs.js).
 */
class FontSizeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['font_size' => ['required', 'integer', 'between:10,26']]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['font_size' => (int) $validated['font_size']])->save();

        return response()->json(['status' => 'success', 'font_size' => $user->font_size]);
    }
}

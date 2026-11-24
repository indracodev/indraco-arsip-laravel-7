<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveCatalogRequest;
use App\Models\Archive;
use App\Models\User;
use App\Services\ArchiveCatalog;
use App\Support\Menu;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Katalog Arsip" (Laravel 7 archives.index), read-only for the Phase 3 mockup.
 */
class ArchiveCatalogController extends Controller
{
    public function index(ArchiveCatalogRequest $request, ArchiveCatalog $catalog): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('archives.index', [
            'title' => Menu::labelFor($user, 'archives.index'),
            'archives' => $catalog->paginate($user, $request->validated()),
            'departments' => $catalog->departments($user),
            'documentTypes' => $catalog->documentTypes($user),
            'isPic' => $user->role === User::ROLE_PIC_DEPT,
        ]);
    }

    /**
     * Detail and edit pages are rebuilt in Phase 4; the department check is already enforced.
     */
    public function show(Archive $archive): View
    {
        Gate::authorize('view', $archive);

        return view('placeholder', ['title' => 'Detail Arsip: '.$archive->title]);
    }
}

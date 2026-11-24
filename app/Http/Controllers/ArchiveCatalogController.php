<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveCatalogRequest;
use App\Models\Archive;
use App\Models\User;
use App\Services\ArchiveCatalog;
use App\Support\ArchiveFileList;
use App\Support\Menu;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * "Katalog Arsip" (Laravel 7 archives.index) and the archive detail page (archives.show).
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
     * "Detail Arsip" (Laravel 7 archives.show). PIC Departemen only their own department
     * (ArchivePolicy@view, findings #5); admin and PIC Gudang every archive.
     */
    public function show(Archive $archive): View
    {
        Gate::authorize('view', $archive);
        $archive->load([
            'department', 'subDepartment', 'creator', 'items', 'location.warehouse', 'rackSlot',
            'entryLogs.picGudang', 'borrowingLogs.borrower', 'destructionLog',
        ]);

        return view('archives.show', ['archive' => $archive, 'files' => ArchiveFileList::for($archive)]);
    }
}

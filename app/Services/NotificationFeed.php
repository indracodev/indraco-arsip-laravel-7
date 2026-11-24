<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\BorrowingLog;
use App\Models\User;
use App\Support\StatusLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Realtime notifications, menu badges and the expiry popup (Laravel 7 HandlesDashboardRealtime
 * realtimeCheck / apiExpiringList + desktop_realtime_scripts / pic_realtime_scripts rules).
 * Alert rules are applied here (not in JS), so the browser only receives what it may show.
 */
final class NotificationFeed
{
    /** Laravel 7 borrowing status texts in the toast. */
    private const BORROWING_STATUS = ['requested' => 'Menunggu Approval', 'approved' => 'Disetujui / Siap Ambil', 'dispatched' => 'Sudah Ditarik'];

    /**
     * @return array{latest_seq: int, changed: bool, notifications: list<array<string, mixed>>, badges?: array<string, int>}
     */
    public function poll(User $user, int $sinceSeq, bool $initial): array
    {
        $latest = SystemEventStream::latestId();
        // Idle: the client already has the latest event -> one MAX(id), no counts (Laravel 7 fast path).
        if (! $initial && $sinceSeq >= $latest) {
            return ['latest_seq' => $latest, 'changed' => false, 'notifications' => []];
        }
        $badges = $this->badges($user);
        // First poll of a tab: only the cursor (Laravel 7 never alerted on what happened before).
        if ($initial) {
            return ['latest_seq' => $latest, 'changed' => false, 'notifications' => [], 'badges' => $badges];
        }
        $events = SystemEventStream::visibleTo($user, $sinceSeq);

        return ['latest_seq' => $latest, 'changed' => $events->isNotEmpty(), 'notifications' => $this->notifications($user, $events), 'badges' => $badges];
    }

    /**
     * Menu badge counts keyed by menu route (Laravel 7 badges archives / borrowings / destructions).
     *
     * @return array<string, int>
     */
    public function badges(User $user): array
    {
        $isPic = $user->role === User::ROLE_PIC_DEPT;
        $borrowings = BorrowingLog::query()
            ->whereIn('status', $isPic ? ['requested', 'approved'] : ['requested', 'dept_approved', 'approved'])
            ->when($isPic, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('borrower_user_id', $user->id)
                ->orWhereHas('archive', fn (Builder $a) => $a->where('department_id', $user->department_id))));

        return [
            'archives.index' => Archive::query()->visibleTo($user)->where('status', 'pending_verification')->count(),
            'borrowings.index' => $borrowings->count(),
            'destructions.index' => $this->expiringQuery($user)->count(),
        ];
    }

    /**
     * Boxes for the "Peringatan Masa Simpan Berkas" popup: expired or within H-30 of the end of the
     * expiry month (Laravel 7 rule), expired first.
     *
     * @return list<array<string, mixed>>
     */
    public function expiring(User $user): array
    {
        $today = Carbon::today();
        $items = $this->expiringQuery($user)
            ->with(['department:id,code,name', 'location.warehouse', 'rackSlot'])
            ->limit(200)
            ->get()
            ->map(function (Archive $archive) use ($today): array {
                $end = $archive->retention_expiry_date?->copy()->endOfMonth()->endOfDay() ?? $today;
                $expired = $today->gt($end);
                $days = abs((int) $today->diffInDays($end, false));

                return [
                    'id' => $archive->id,
                    'box_number' => $archive->box_number ?: 'Belum Ada No. Box',
                    'title' => $archive->title,
                    'dept_code' => $archive->department->code ?? 'GEN',
                    'expiry_date' => $archive->retention_expiry_date?->format('d M Y'),
                    'is_expired' => $expired,
                    'days_diff' => $days,
                    'badge_text' => $expired ? "LEWAT {$days} HARI" : "H-{$days} HARI",
                    'location' => $archive->location ? $archive->displayLocation() : 'Gudang Utama',
                ];
            })
            ->sort(fn (array $a, array $b): int => [$b['is_expired'], $b['days_diff']] <=> [$a['is_expired'], $a['days_diff']]);

        return $items->values()->all();
    }

    /** @return Builder<Archive> */
    private function expiringQuery(User $user): Builder
    {
        // end of the expiry month <= today + 30 days  <=>  today >= end of month - 30 days (Laravel 7).
        return Archive::query()->visibleTo($user)
            ->whereNotIn('status', ['destroyed', 'draft'])
            ->whereNotNull('retention_expiry_date')
            ->whereRaw("date(retention_expiry_date, 'start of month', '+1 month', '-1 day') <= ?", [Carbon::today()->addDays(30)->toDateString()]);
    }

    /**
     * Toasts for the user (Laravel 7 rules): never for their own action; new penarikan requests
     * only for PIC Gudang / admin; box submissions (not drafts) of other users.
     *
     * @param  Collection<int, stdClass>  $events
     * @return list<array<string, mixed>>
     */
    private function notifications(User $user, Collection $events): array
    {
        $isWarehouse = $user->role !== User::ROLE_PIC_DEPT;
        $creators = User::query()->whereIn('id', $events->pluck('payload.created_by_user_id')->filter()->unique())->pluck('name', 'id');
        $list = [];
        foreach ($events as $event) {
            $p = $event->payload;
            $base = ['id' => 'ev'.$event->id, 'box' => $p['box_number'] ?? '', 'dept' => $p['dept_code'] ?? '', 'time' => Carbon::parse($event->created_at)->format('H:i:s')];
            if (in_array($event->event_type, ['borrowing_created', 'borrowing_updated'], true)) {
                $created = $event->event_type === 'borrowing_created';
                if ((int) ($p['action_by_user_id'] ?? 0) === $user->id || ($created && (int) ($p['borrower_user_id'] ?? 0) === $user->id) || ($created && ! $isWarehouse)) {
                    continue;
                }
                $list[] = $base + [
                    'title' => $created ? 'Permohonan Penarikan Berkas Masuk' : 'Status Penarikan Berkas Berubah',
                    'by' => $p['borrower'] ?? 'User',
                    'status' => self::BORROWING_STATUS[$p['status'] ?? ''] ?? ($p['status'] ?? ''),
                    'url' => route('borrowings.index'),
                ];
            } elseif ($event->event_type === 'archive_submitted' && (int) ($p['created_by_user_id'] ?? 0) !== $user->id) {
                $list[] = $base + [
                    'title' => $isWarehouse ? 'Pengajuan Box Arsip Masuk' : ($p['title'] ?? 'Pengajuan Berkas Baru'),
                    'by' => $creators[$p['created_by_user_id'] ?? 0] ?? 'User',
                    'status' => StatusLabel::archive((string) ($p['status'] ?? 'pending_verification'))['short'],
                    'url' => route('archives.show', (int) $event->entity_id),
                ];
            }
        }

        return $list;
    }
}

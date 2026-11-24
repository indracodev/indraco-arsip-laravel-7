{{--
    Archive status badge from the single map App\Support\StatusLabel.
    <x-badge-status :status="$archive->status" :rejected="filled($archive->rejection_note)" short />
    `short` = text of the Laravel 7 catalog column; default = Laravel 7 status_label.
--}}
@props(['status', 'rejected' => false, 'destruction' => null, 'short' => false])
@php($entry = \App\Support\StatusLabel::archive((string) $status, (bool) $rejected, $destruction))
<span {{ $attributes->class(['badge', 'badge--'.$entry['tone'] => $entry['tone'] !== 'neutral']) }} data-status="{{ $status }}">{{ $short ? $entry['short'] : $entry['label'] }}</span>

{{--
    <x-table> <thead>...</thead> <tbody> <tr><td data-label="KOLOM">...</td></tr> </tbody> </x-table>
    Below 768px rows become cards; each <td data-label> shows its column name (data-label="" hides it).
--}}
<div class="table-wrap">
    <table {{ $attributes->class(['table', 'table--cards']) }}>
        {{ $slot }}
    </table>
</div>

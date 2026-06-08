@props(['showNotes' => false])

<table class="workspace-table canonical-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Kind</th>
            @if ($showNotes)
                <th class="canonical-col-notes">Notes</th>
            @endif
            <th class="canonical-col-count">Properties</th>
            <th class="canonical-col-info" aria-label="Details"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($databases as $canonical)
            @include('console.canonical.partials.table-row', ['canonical' => $canonical, 'showNotes' => $showNotes])
        @endforeach
    </tbody>
</table>

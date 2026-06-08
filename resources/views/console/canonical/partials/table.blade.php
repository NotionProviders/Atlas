<table class="workspace-table canonical-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Kind</th>
            <th class="canonical-col-count">Properties</th>
            <th class="canonical-col-info" aria-label="Details"></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($databases as $canonical)
            @include('console.canonical.partials.table-row', ['canonical' => $canonical])
        @endforeach
    </tbody>
</table>

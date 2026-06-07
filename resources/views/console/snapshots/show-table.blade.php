@extends('console.layouts.app')

@section('title', $snapshotType->label().' — '.$project->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $snapshotType->label() }}</span>
@endsection

@section('content')
@include('console.snapshots.partials.toolbar', ['view' => 'table'])

<div id="workspace-table-app"
     data-nodes-url="{{ route('console.snapshots.nodes', [$project, $snapshotType->value]) }}">
    <div class="workspace-table-toolbar">
        <details class="workspace-column-menu">
            <summary class="console-btn workspace-column-menu-btn">Columns</summary>
            <div class="workspace-column-menu-panel" id="workspace-column-toggle"></div>
        </details>
    </div>
    <table class="workspace-table">
        <thead>
            <tr>
                <th data-col="name">Name</th>
                <th data-col="kind">Kind</th>
                <th data-col="notion_page_id">Notion page ID</th>
                <th data-col="notion_data_source_id">Data source ID</th>
                <th data-col="notes">Notes</th>
            </tr>
        </thead>
        <tbody id="workspace-table-body">
            @foreach ($teamspaces as $teamspace)
                @include('console.snapshots.partials.table-row', ['node' => $teamspace, 'depth' => 0, 'ancestors' => []])
            @endforeach
        </tbody>
    </table>
    <aside id="workspace-properties-panel" class="workspace-properties-panel hidden">
        <button type="button" id="workspace-properties-close" class="console-link-btn">&times; Close</button>
        <h3 id="workspace-properties-title"></h3>
        <div id="workspace-properties-body"></div>
    </aside>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/workspace-table.js') }}?v={{ @filemtime(public_path('js/workspace-table.js')) ?: 1 }}" defer></script>
@endpush

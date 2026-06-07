<div class="console-snapshot-toolbar">
    <div class="console-view-toggle">
        <a href="{{ route('console.snapshots.show', [$project, $snapshotType->value, 'view' => 'atlas']) }}"
           class="console-view-btn {{ ($view ?? 'atlas') === 'atlas' ? 'active' : '' }}">Atlas</a>
        <a href="{{ route('console.snapshots.show', [$project, $snapshotType->value, 'view' => 'table']) }}"
           class="console-view-btn {{ ($view ?? 'atlas') === 'table' ? 'active' : '' }}">Table</a>
    </div>
    <div class="console-snapshot-switcher">
        @foreach (\App\Enums\SnapshotType::cases() as $type)
            <a href="{{ route('console.snapshots.show', [$project, $type->value, 'view' => $view ?? 'atlas']) }}"
               class="console-snap-tab {{ $type === $snapshotType ? 'active' : '' }}">{{ $type->label() }}</a>
        @endforeach
    </div>
    <a href="{{ route('console.projects.show', $project) }}" class="console-btn">Back to project</a>
</div>

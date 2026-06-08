<div class="canonical-peek">
    <header class="canonical-peek-header">
        <a href="{{ route('console.mappings.database.show', [$project, $atlasNode]) }}"
           class="canonical-peek-expand"
           aria-label="Open full page"
           title="Open full page">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
            </svg>
        </a>
        <h2 class="canonical-peek-title" id="mapping-peek-title">
            {{ $atlasNode->label }}
            @if ($mapping)
                → {{ $mapping->canonicalDatabase->name }}
            @endif
        </h2>
        <button type="button" class="canonical-peek-close" data-console-peek-close aria-label="Close details">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </header>
    <div class="canonical-peek-body">
        @include('console.mappings.partials.database-details')
    </div>
</div>

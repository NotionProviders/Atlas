@php
    $panelUrl = route('console.canonical.panel', $canonical);
@endphp
<button type="button"
        class="canonical-info-btn canonical-template-btn"
        data-panel-url="{{ $panelUrl }}"
        data-peek-url=""
        aria-label="View canonical template for {{ $canonical->name }}">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
    <span class="canonical-info-tooltip" role="tooltip">
        <strong>{{ $canonical->name }}</strong>
        <span>Global canonical template reference</span>
        <span class="canonical-info-hint">Click for template details</span>
    </span>
</button>

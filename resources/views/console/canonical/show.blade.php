@extends('console.layouts.app')

@section('title', $canonicalDatabase->name)

@section('breadcrumb')
    <span class="console-breadcrumb-sep">/</span>
    <a href="{{ route('console.canonical.index') }}" class="console-breadcrumb">Canonical databases</a>
    <span class="console-breadcrumb-sep">/</span>
    <span class="console-breadcrumb">{{ $canonicalDatabase->name }}</span>
@endsection

@section('content')
<div class="console-page-header">
    <div>
        <h1>{{ $canonicalDatabase->name }}</h1>
        <p class="console-muted">
            @if ($canonicalDatabase->is_lookup)
                Lookup / taxonomy database
            @else
                Entity database
            @endif
        </p>
    </div>
    <div class="console-page-actions">
        <a href="{{ route('console.canonical.peek-page', $canonicalDatabase) }}" class="console-btn">Center peek</a>
        <a href="{{ route('console.canonical.index') }}" class="console-btn">Back to registry</a>
    </div>
</div>

<div class="canonical-show-panel">
    @include('console.canonical.partials.details-content')
</div>
@endsection

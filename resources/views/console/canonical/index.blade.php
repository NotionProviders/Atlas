@extends('console.layouts.app')

@section('title', 'Canonical databases')

@section('content')
<div class="console-page-header">
    <div>
        <h1>Canonical database registry</h1>
        <p class="console-muted">Master list of target databases used to map client workspaces. Grows over time as you onboard new clients.</p>
    </div>
</div>

<details class="console-add-canonical" open>
    <summary class="console-btn console-btn-primary">+ Add canonical database</summary>
    <form method="POST" action="{{ route('console.canonical.store') }}" class="console-form console-add-canonical-form">
        @csrf
        <label>
            <span>Name</span>
            <input type="text" name="name" required placeholder="e.g. Companies">
        </label>
        <label>
            <span>Description</span>
            <textarea name="description" rows="2"></textarea>
        </label>
        <fieldset class="console-property-fieldset">
            <legend>Standard properties</legend>
            @for ($i = 0; $i < 5; $i++)
                <div class="console-property-row">
                    <input type="text" name="properties[{{ $i }}][name]" placeholder="Property name">
                    <input type="text" name="properties[{{ $i }}][property_type]" placeholder="Type">
                </div>
            @endfor
        </fieldset>
        <button type="submit" class="console-btn console-btn-primary">Create</button>
    </form>
</details>

@if ($canonicalDatabases->isEmpty())
    <p class="console-muted">No canonical databases yet.</p>
@else
    <div class="console-card-list">
        @foreach ($canonicalDatabases as $canonical)
            <div class="console-card console-card-static">
                <div class="console-card-top">
                    <h2>{{ $canonical->name }}</h2>
                    <form method="POST" action="{{ route('console.canonical.destroy', $canonical) }}" onsubmit="return confirm('Remove this canonical database? Existing mappings will be deleted.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="console-link-btn">Remove</button>
                    </form>
                </div>
                @if ($canonical->description)
                    <p class="console-muted">{{ $canonical->description }}</p>
                @endif
                @if ($canonical->properties->isNotEmpty())
                    <table class="workspace-props-table">
                        <thead>
                            <tr><th>Property</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($canonical->properties as $prop)
                                <tr>
                                    <td>{{ $prop->name }}</td>
                                    <td>{{ $prop->property_type }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="console-muted">No standard properties defined.</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection

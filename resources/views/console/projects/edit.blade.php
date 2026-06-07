@extends('console.layouts.app')

@section('title', 'Edit '.$project->name)

@section('content')
<div class="console-page-header">
    <h1>Edit project</h1>
</div>

<form method="POST" action="{{ route('console.projects.update', $project) }}" class="console-form">
    @csrf
    @method('PUT')
    <label>
        <span>Project name</span>
        <input type="text" name="name" value="{{ old('name', $project->name) }}" required>
    </label>
    <label>
        <span>Client name</span>
        <input type="text" name="client_name" value="{{ old('client_name', $project->client_name) }}">
    </label>
    <label>
        <span>Notes</span>
        <textarea name="notes" rows="4">{{ old('notes', $project->notes) }}</textarea>
    </label>
    <div class="console-form-actions">
        <a href="{{ route('console.projects.show', $project) }}" class="console-btn">Cancel</a>
        <button type="submit" class="console-btn console-btn-primary">Save</button>
    </div>
</form>

<form method="POST" action="{{ route('console.projects.destroy', $project) }}" class="console-danger-zone" onsubmit="return confirm('Delete this project and all snapshots?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="console-btn console-btn-danger">Delete project</button>
</form>
@endsection

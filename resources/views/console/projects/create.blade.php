@extends('console.layouts.app')

@section('title', 'New project')

@section('content')
<div class="console-page-header">
    <h1>New project</h1>
</div>

<form method="POST" action="{{ route('console.projects.store') }}" class="console-form">
    @csrf
    <label>
        <span>Project name</span>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus>
    </label>
    <label>
        <span>Client name</span>
        <input type="text" name="client_name" value="{{ old('client_name') }}">
    </label>
    <label>
        <span>Notes</span>
        <textarea name="notes" rows="4">{{ old('notes') }}</textarea>
    </label>
    <div class="console-form-actions">
        <a href="{{ route('console.projects.index') }}" class="console-btn">Cancel</a>
        <button type="submit" class="console-btn console-btn-primary">Create project</button>
    </div>
</form>
@endsection

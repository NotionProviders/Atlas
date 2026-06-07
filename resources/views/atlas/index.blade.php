@extends('layouts.atlas')

@section('content')
@include('atlas.partials.map', ['kicker' => $kicker])
@endsection

@push('scripts')
<script>{!! $atlasConfigScript !!}</script>
<script src="{{ asset('js/atlas.js') }}?v={{ @filemtime(public_path('js/atlas.js')) ?: 1 }}" defer></script>
@endpush

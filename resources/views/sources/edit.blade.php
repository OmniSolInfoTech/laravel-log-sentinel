@extends('log-sentinel::layouts.app')

@section('title', 'Edit Log Source')

@section('content')
    <div class="card">
        <h1>Edit Log Source</h1>
        <p>Update this log source configuration.</p>

        <form method="POST" action="{{ route('log-sentinel.sources.update', $source) }}">
            @csrf
            @method('PUT')

            @include('log-sentinel::sources.form', [
                'buttonText' => 'Update Source'
            ])
        </form>
    </div>
@endsection

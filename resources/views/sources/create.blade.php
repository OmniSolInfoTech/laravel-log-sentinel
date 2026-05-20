@extends('log-sentinel::layouts.app')

@section('title', 'Add Log Source')

@section('content')
    <div class="card">
        <h1>Add Log Source</h1>
        <p>Add a log file that Log Sentinel should monitor.</p>

        <form method="POST" action="{{ route('log-sentinel.sources.store') }}">
            @csrf

            @include('log-sentinel::sources.form', [
                'buttonText' => 'Create Source'
            ])
        </form>
    </div>
@endsection

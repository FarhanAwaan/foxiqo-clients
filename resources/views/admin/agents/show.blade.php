@extends('layouts.admin')

@section('title', $agent->name)

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    {{ $agent->name }}
@endsection

@section('page-actions')
    <div class="btn-list">
        <a href="{{ route('admin.agents.edit', $agent) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
            Edit Assistant
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    @include('agents._show')
@endsection

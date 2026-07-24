@extends('layouts.customer')

@section('title', $agent->name)

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    {{ $agent->name }}
@endsection

@section('page-actions')
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
        Back to Assistants
    </a>
@endsection

@section('content')
    @include('agents._show')
@endsection

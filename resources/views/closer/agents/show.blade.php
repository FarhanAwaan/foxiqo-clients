@extends('layouts.closer')

@section('title', $agent->name)

@section('page-pretitle')
    {{ $agent->company->name }}
@endsection

@section('page-header')
    {{ $agent->name }}
@endsection

@section('page-actions')
    <a href="{{ route('closer.companies.show', $agent->company) }}" class="btn btn-outline-secondary">
        Back to {{ $agent->company->name }}
    </a>
@endsection

@section('content')
    @include('agents._show')
@endsection

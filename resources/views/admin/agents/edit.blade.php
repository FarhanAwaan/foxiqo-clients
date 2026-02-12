@extends('layouts.admin')

@section('title', 'Edit Assistant')

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    Edit {{ $agent->name }}
@endsection

@section('content')
    <form action="{{ route('admin.agents.update', $agent) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.agents._form')
    </form>
@endsection

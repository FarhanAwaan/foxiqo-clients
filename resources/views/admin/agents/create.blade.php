@extends('layouts.admin')

@section('title', 'Add Assistant')

@section('page-pretitle')
    Assistants
@endsection

@section('page-header')
    Add New Assistant
@endsection

@section('content')
    <form action="{{ route('admin.agents.store') }}" method="POST">
        @csrf
        @include('admin.agents._form')
    </form>
@endsection

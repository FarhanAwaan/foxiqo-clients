@extends('layouts.admin')

@section('title', 'Add Agent')

@section('page-pretitle')
    Agents
@endsection

@section('page-header')
    Add New Agent
@endsection

@section('content')
    <form action="{{ route('admin.agents.store') }}" method="POST">
        @csrf
        @include('admin.agents._form')
    </form>
@endsection

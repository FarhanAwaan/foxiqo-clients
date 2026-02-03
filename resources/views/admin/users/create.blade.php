@extends('layouts.admin')

@section('title', 'Create User')

@section('page-pretitle')
    Users
@endsection

@section('page-header')
    Create New User
@endsection

@section('content')
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        @include('admin.users._form')
    </form>
@endsection

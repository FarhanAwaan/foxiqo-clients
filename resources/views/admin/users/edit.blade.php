@extends('layouts.admin')

@section('title', 'Edit User')

@section('page-pretitle')
    Users
@endsection

@section('page-header')
    Edit: {{ $user->full_name }}
@endsection

@section('content')
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.users._form')
    </form>
@endsection

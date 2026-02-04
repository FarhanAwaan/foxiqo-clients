@extends('layouts.admin')

@section('title', 'Create Plan')

@section('page-pretitle')
    Plans
@endsection

@section('page-header')
    Create New Plan
@endsection

@section('content')
    <form action="{{ route('admin.plans.store') }}" method="POST">
        @csrf
        @include('admin.plans._form')
    </form>
@endsection

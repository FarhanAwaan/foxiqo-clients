@extends('layouts.admin')

@section('title', 'Create Company')

@section('page-pretitle')
    Companies
@endsection

@section('page-header')
    Create New Company
@endsection

@section('content')
    <form action="{{ route('admin.companies.store') }}" method="POST">
        @csrf
        @include('admin.companies._form')
    </form>
@endsection

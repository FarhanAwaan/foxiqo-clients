@extends('layouts.admin')

@section('title', 'Edit Company')

@section('page-pretitle')
    Companies
@endsection

@section('page-header')
    Edit: {{ $company->name }}
@endsection

@section('content')
    <form action="{{ route('admin.companies.update', $company) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.companies._form')
    </form>
@endsection

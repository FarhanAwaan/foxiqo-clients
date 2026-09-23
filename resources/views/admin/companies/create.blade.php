@extends('layouts.admin')

@section('title', 'Create Customer')

@section('page-pretitle')
    Customers
@endsection

@section('page-header')
    Create New Customer
@endsection

@section('content')
    <form action="{{ route('admin.companies.store') }}" method="POST">
        @csrf
        @include('admin.companies._form')
    </form>
@endsection

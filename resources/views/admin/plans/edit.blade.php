@extends('layouts.admin')

@section('title', 'Edit Plan')

@section('page-pretitle')
    Plans
@endsection

@section('page-header')
    Edit Plan: {{ $plan->name }}
@endsection

@section('content')
    <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.plans._form')
    </form>
@endsection

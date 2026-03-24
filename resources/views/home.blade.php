@extends('layouts.app')

@section('title', 'Home / Shore Reporting')

@section('content')
<div class="container" style="padding-top: 2rem;">
    <div class="card flex justify-between items-center">
        <div>
            <h1 style="font-size: 1.5rem; color: #111827; margin: 0;">Welcome, {{ auth()->user()->name }}</h1>
            <p style="color: #6b7280; margin: 0.25rem 0 0;">You are logged in.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">Log Out</button>
        </form>
    </div>
</div>
@endsection

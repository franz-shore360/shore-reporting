@extends('layouts.app')

@section('title', 'Sign In / Shore Reporting')

@section('content')
<div class="container" style="padding-top: 4rem;">
    <div class="card">
        <div class="text-center mb-4">
            <img src="{{ asset('images/shore-reporting-logo.svg') }}" alt="Shore Reporting" width="48" height="48" style="display: inline-block; border-radius: 0.5rem;" />
        </div>
        <h1 class="text-center mb-4" style="font-size: 1.5rem; color: #111827;">Sign In</h1>

        @if ($errors->any())
            <div class="form-group">
                @foreach ($errors->all() as $error)
                    <p class="text-danger">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
            </div>

            <div class="form-group flex items-center">
                <input type="checkbox" name="remember" id="remember" class="checkbox">
                <label for="remember" style="margin-bottom: 0;">Remember Me</label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
            </div>
        </form>
    </div>
</div>
@endsection

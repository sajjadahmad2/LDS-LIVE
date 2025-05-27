@extends('layouts.admin')
@section('content')
<div class="authentication-forgot d-flex align-items-center justify-content-center">
    <div class="card forgot-box">
        <div class="card-body">
                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif
            <div class="p-4 rounded  border">
                <div class="text-center">
                    <img src="{{asset('assets/images/icons/forgot-2.png')}}" width="120" alt="">
                </div>
                <h4 class="mt-5 font-weight-bold">Forgot Password?</h4>
                <p class="text-muted">Enter your registered email ID to reset the password</p>
                <form method="POST" action="{{ route('password.email1') }}">
                    @csrf
                <div class="my-4">
                    <label class="form-label">Email id</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                    {{ __('Send Password Reset Link') }}</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection

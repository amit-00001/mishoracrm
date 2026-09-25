@extends('layouts.portal')

@section('title', 'Enter code')

@section('content')
@if($notice)
<div class="pt-flash success">{{ $notice }}</div>
@endif
@if($error)
<div class="pt-flash error">{{ $error }}</div>
@endif
@if(!empty($devHint))
<div class="pt-flash success">{{ $devHint }}</div>
@endif

<div class="pt-card">
    <div class="pt-hero">
        <div class="pt-brand">One login for all your shops</div>
        <div class="pt-title">Enter your code</div>
    </div>

    <div class="pt-body">
        <div class="pt-step-title">Check your messages</div>
        <div class="pt-step-sub">Enter the 6-digit code we sent for <strong>{{ $phone }}</strong>. It expires in 10 minutes.</div>

        <form method="POST" action="{{ route('portal.login.verify') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}"/>
            <div class="pt-field">
                <label for="code">6-digit code</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" name="code" id="code" class="pt-input otp" autocomplete="one-time-code" required autofocus/>
                @error('code')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="pt-btn">Verify &amp; sign in</button>
        </form>

        <div class="pt-foot"><a href="{{ route('portal.login') }}">Use a different number</a></div>
    </div>
</div>
@endsection

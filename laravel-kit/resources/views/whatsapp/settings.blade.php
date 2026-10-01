{{-- Minimal tenant settings page. Put it inside your own layout (@extends('layouts.app')) and restyle freely. --}}
<h2>WhatsApp</h2>

@if(session('success')) <p style="color:green">{{ session('success') }}</p> @endif
@if(session('error'))   <p style="color:#b00020">{{ session('error') }}</p> @endif

@if($account->connected)
    <p>Connected: <strong>{{ $account->display_phone_number }}</strong> @if($account->verified_name) ({{ $account->verified_name }}) @endif</p>
    @if($account->last_warning) <p style="color:#b45309">{{ $account->last_warning }}</p> @endif

    <form method="POST" action="{{ route('whatsapp.disconnect') }}">
        @csrf
        <button type="submit" onclick="return confirm('Disconnect this WhatsApp number?')">Disconnect</button>
    </form>
@else
    <p>Connect your WhatsApp Business number to send and receive WhatsApp messages from here.</p>

    <form method="POST" action="{{ route('whatsapp.connect') }}">
        @csrf
        <button type="submit">Connect WhatsApp</button>
    </form>
@endif

@extends('errors.layout')

@section('title', 'Terlalu Banyak Permintaan')
@section('code', '429')
@section('message', 'Terlalu Banyak Permintaan')
@section('description', 'Sistem sedang membatasi permintaan Anda untuk menjaga stabilitas portal holding. Silakan tunggu beberapa saat sebelum mencoba lagi.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9" /><path d="M12 7v5l4 2" /><path d="M3 3l18 18" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/dashboard') }}">Kembali ke Dashboard</a>
@endsection

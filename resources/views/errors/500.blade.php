@extends('errors.layout')

@section('title', 'Kesalahan Server')
@section('code', '500')
@section('message', 'Kesalahan Server')
@section('description', 'Sistem gagal memproses permintaan Anda karena gangguan internal. Silakan coba beberapa saat lagi atau hubungi tim teknis.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="6" rx="2" /><rect x="2" y="14" width="20" height="6" rx="2" /><path d="M6 7h.01M6 17h.01M10 7h8M10 17h8" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/dashboard') }}">Kembali ke Dashboard</a>
@endsection

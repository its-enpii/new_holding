@extends('errors.layout')

@section('title', 'Sesi Telah Berakhir')
@section('code', '419')
@section('message', 'Sesi Telah Berakhir')
@section('description', 'Sesi keamanan Anda sudah tidak valid karena halaman dibiarkan terlalu lama. Muat ulang halaman atau masuk kembali bila diminta.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9" /><path d="M12 7v5l3 2" /><path d="M3 3l18 18" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/login') }}">Masuk Kembali</a>
@endsection

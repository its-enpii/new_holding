@extends('errors.layout')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('message', 'Akses Ditolak')
@section('description', 'Akun Anda tidak memiliki izin untuk membuka modul ini. Silakan hubungi superadmin jika Anda memerlukan akses tambahan.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0" /><path d="M12 2v10" /><path d="m16 24-8-8" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/dashboard') }}">Kembali ke Dashboard</a>
@endsection

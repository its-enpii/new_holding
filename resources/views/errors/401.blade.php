@extends('errors.layout')

@section('title', 'Autentikasi Diperlukan')
@section('code', '401')
@section('message', 'Autentikasi Diperlukan')
@section('description', 'Sesi akses Anda belum aktif atau tidak dapat diverifikasi. Silakan masuk kembali untuk melanjutkan pengelolaan holding.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="M10 17l5-5-5-5" /><path d="M15 12H3" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/login') }}">Masuk Kembali</a>
@endsection

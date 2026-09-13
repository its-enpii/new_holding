@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')
@section('code', '404')
@section('message', 'Halaman Tidak Ditemukan')
@section('description', 'Tautan yang Anda buka mungkin sudah berubah, dihapus, atau salah ketik. Silakan kembali ke beranda dan gunakan navigasi utama.')

@section('icon')
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m19 5-9 14" /><path d="m15 5-9 14" /><circle cx="12" cy="12" r="10" /></svg>
@endsection

@section('actions')
    <a class="button" href="{{ url('/') }}">Kembali ke Beranda</a>
@endsection

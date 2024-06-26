@extends('layout.empty')

@section('content')
    <h1>Private Test</h1>
    <p>You are identified with <strong>{!! $me->username !!}</strong> user!</p>
    <p>My url is <a href="{!! $me->url() !!}">{!! $me->url() !!}</a>.</p>
    <p>You can access the settings at the following <a href="index.php?module=Admin&controller=Config">link</a>.</p>
@endsection

@push('scripts')
    {{-- <script src="https://alixar/Templates/Lib/additional-script.js"></script> --}}
@endpush

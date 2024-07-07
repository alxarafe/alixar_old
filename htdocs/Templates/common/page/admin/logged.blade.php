@extends('layout.main')

@section('content')
    <div class="login-logo">
        <strong>Alixar</strong><small><em> Powered by <strong>Alxarafe</strong></em></small>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://alixar/Templates/theme/alixar/dist/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://alixar/Templates/theme/alixar/dist/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="https://alixar/Templates/theme/alixar/dist/css/alixar.min.css">
@endpush

@push('scripts')
    <!-- jQuery 3.3.1 -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- iCheck -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/iCheck/1.0.2/skins/square/blue.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/iCheck/1.0.2/icheck.min.js"></script>
    <script>
        $(function () {
            $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });
        });
    </script>
@endpush

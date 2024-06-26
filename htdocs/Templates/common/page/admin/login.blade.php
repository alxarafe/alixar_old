@extends('layout.empty')

@section('content')
    <div class="login-logo">
        <strong>Alixar</strong><small><em> Powered by <strong>Alxarafe</strong></em></small>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg">Iniciar sesión</p>
        <form action="{!! $me->url() !!}" method="POST">
            <div class="form-group has-feedback">
                <input type="text" name="username" class="form-control" placeholder="Usuario" value="{!! $me->username !!}" required>
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control" placeholder="Contraseña" value="{!! $me->password !!}" required>
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox" name="remember" @if ($me->remember) checked="" @endif> Recordarme
                        </label>
                    </div>
                </div>
                <!-- /.col -->
                <div class="col-xs-4">
                    <button type="submit" name="action" value="login" class="btn btn-primary btn-block btn-flat">Entrar</button>
                </div>
                <!-- /.col -->
            </div>
        </form>
        <!-- /.social-auth-links -->
        <a href="#">Olvidé mi contraseña</a><br>
        <a href="register.html" class="text-center">Registrar una nueva membresía</a>
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

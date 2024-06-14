@extends('install/master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="action" value="verify_admin_user">
    <input type="hidden" name="language" value="{!! $me->config->main->language!!}">
    <h3>
        <img class="valignmiddle inline-block paddingright"
             src="{!! $me->config->main->url !!}/Templates/common/octicons/build/svg/database.svg"
             width="20" alt="Database">{!! $me->langs->trans("DatabaseMigration") !!}
    </h3>
    @if ($me->vars->config_read_only)
        <h5>{!! $me->langs->trans("ConfFileIsNotWritable", $me->vars->config_filename) !!}</h5>
    @endif
    <h3>
        <img class="valignmiddle inline-block paddingright"
             src="{!! $me->config->main->url !!}/Templates/common/octicons/build/svg/key.svg" width="20" alt="Database">
        {!! $me->langs->trans("DolibarrAdminLogin") !!}
    </h3>
    @foreach($me->vars->errors ?? [] as $error)
        <div class="error">{!! $error !!}</div>
    @endforeach
    <table cellspacing="0" style="padding: 4px 4px 4px 0" border="0" width="100%">
        <tr>
            <td>
                <label for="login">{!! $me->langs->trans("Login") !!}</label>
            </td>
            <td>
                <input id="login" name="login" type="text" value="{!! $me->vars->login !!}"
                       @if ($force_install_noedit == 2 && $force_install_dolibarrlogin !== null) disabled
                       @endif autofocus>
            </td>
        </tr>
        <tr>
            <td>
                <label for="pass">{!! $me->langs->trans("Password") !!}</label>
            </td>
            <td>
                <input type="password" id="pass" name="pass" autocomplete="new-password" minlength="{!! $me->vars->minLen !!}" value="{!! $me->vars->pass !!}">
            </td>
        </tr>
        <tr>
            <td>
                <label for="pass_verif">{!! $me->langs->trans("PasswordRetype") !!}</label>
            </td>
            <td>
                <input type="password" id="pass_verif" name="pass_verif" autocomplete="new-password" minlength="{!! $me->vars->minLen !!}" value="{!! $me->vars->pass_verif !!}">
            </td>
        </tr>
    </table>
    </tbody>

    <script type="text/javascript">
        $(document).ready(function () {
            const passwordField = document.getElementById('pass');
            const passVerifyField = document.getElementById('pass_verif');
            const nextButton = document.querySelector('#nextbutton input[type="submit"]');

            passwordField.addEventListener('blur', validate_password);
            passVerifyField.addEventListener('blur', validate_password);

            validate_password();

            function validate_password() {
                const minLen = {!! $me->vars->minLen !!};

                const password = passwordField.value;
                const passVerify = passVerifyField.value;

                nextButton.disabled = password.length < minLen || password !== passVerify;
                console.log(nextButton.disabled);
            }
        });
    </script>
@endsection

@extends('master/install_layout')

@section('body')
    {!! $lang->trans("LastStepDesc") !!}<br><br>
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="step5">
    <tr>
        <table cellspacing="0" cellpadding="2">
            <tr>
                <td><label for="login">{!! $lang->trans("Login") !!}:</label></td>
                <td>
                    <input id="login" name="login" type="text" value="{!! $login_value !!}" autofocus>
                </td>
            </tr>
            <tr>
                <td><label for="pass">{!! $lang->trans("Password") !!}:</label></td>
                <td>
                    <input type="password" id="pass" name="pass" autocomplete="new-password" minlength="8">
                </td>
            </tr>
            <tr>
                <td><label for="pass_verif">{!! $lang->trans("PasswordRetype") !!}:</label></td>
                <td>
                    <input type="password" id="pass_verif" name="pass_verif" autocomplete="new-password" minlength="8">
                </td>
            </tr>
        </table>
    </tr>
    </tbody>
@endsection
@extends('install/master/install_layout')

@section('body')
    <tr>
        <td>
            <input type="hidden" name="testpost" value="ok">
            <input type="hidden" name="action" value="checked">
            <br><span class="opacitymedium">{!! $me->langs->trans('NoReadableConfFileSoStartInstall') !!}</span><br><br>
            <div class="center">
                <table>
                    <tr>
                        <td>{!! $me->langs->trans('DefaultLanguage') !!}:</td>
                        <td>{!! $me->htmlComboLanguages !!}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
@endsection

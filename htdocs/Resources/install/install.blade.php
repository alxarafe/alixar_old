@extends('master/install_layout')

@section('body')
    <tr>
        <td>
            <input type="hidden" name="testpost" value="ok">
            <input type="hidden" name="action" value="checked">
            <br><span class="opacitymedium">{!! $lang->trans('NoReadableConfFileSoStartInstall') !!}</span><br><br>
            <div class="center">
                <table>
                    <tr>
                        <td>{!! $lang->trans('DefaultLanguage') !!}:</td>
                        <td>{!! $htmlComboLanguages !!}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
@endsection
@extends('master/install_layout')

@section('body')
    <form name="forminstall" style="width: 100%" method="POST">
        <input type="hidden" name="testpost" value="ok">
        <input type="hidden" name="action" value="checked">
        <table class="main" width="100%">
            <tr>
                <td>
                    <table class="main-inside" width="100%">
                        <tr>
                            <td>
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
                    </table>
                </td>
            </tr>
        </table>
        <!-- pFooter -->
        <div class="nextbutton" id="nextbutton"><input type="submit" value="{!! $lang->trans('NextStep') !!} ->"></div>
    </form>
@endsection
@extends('master/install_layout')

@section('body')
    <tr>
        <td>
            <br><span class="opacitymedium">{!! $noReadableConfig !!}</span><br><br>
            <div class="center">
                <table>
                    <tr>
                        <td>{!! $defaultLanguage !!}:</td>
                        <td>{!! $htmlComboLanguages !!}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
@endsection
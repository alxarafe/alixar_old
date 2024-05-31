@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="step4">
    <input type="hidden" name="selectlang" value="{!! $me->selectLang !!}">
    <tr>

    </tr>
    </tbody>
    @endsection

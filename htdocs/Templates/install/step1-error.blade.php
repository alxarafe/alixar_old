@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="check">
    <input type="hidden" name="selectlang" value="{!! $me->selectLang !!}">
    <tr>
        <div class="error">{!! $errorMessage !!}</div>
    </tr>
@endsection

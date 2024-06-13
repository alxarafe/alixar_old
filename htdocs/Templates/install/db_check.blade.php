@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="language" value="{!! $me->config->main->language!!}">
    <tr>

    </tr>
    </tbody>
    @endsection

@extends('install/master/install_layout')

@section('body')
    <tbody>
        <input type="hidden" name="testpost" value="ok">
        <input type="hidden" name="action" value="alixar">
        <input type="hidden" name="language" value="{!! $me->config->main->language!!}">
        @foreach($me->vars->errors ?? [] as $error)
            <div class="error">{!! $error !!}</div>
        @endforeach
    </tbody>
@endsection

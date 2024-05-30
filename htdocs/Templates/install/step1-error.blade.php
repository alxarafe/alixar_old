@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="check">
    <tr>
        <div class="error">{!! $errorMessage !!}</div>
    </tr>
@endsection
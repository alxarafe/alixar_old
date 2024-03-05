@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="step2">
    <tr>
        @foreach($errors as $error)
            <div class="error">{!! $error !!}</div>
        @endforeach

        @if (count($errors)>0)
            <br>{!! $lang->trans('ErrorGoBackAndCorrectParameters') !!}
        @endif

            <script type="text/javascript">
                function jsinfo() {
                    ok = true;

                    //alert('<?php echo \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("NextStepMightLastALongTime")); ?>');

                    document.getElementById('nextbutton').style.visibility = "hidden";
                    document.getElementById('pleasewait').style.visibility = "visible";

                    return ok;
                }
            </script>

    </tr>
    </tbody>
    @endsection
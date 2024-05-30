@extends('master/install_layout')

@section('body')
    <tbody>
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="action" value="step2">
    <tr>
        @foreach($me->errors as $error)
            <div class="error">{!! $error !!}</div>
        @endforeach

        @if (count($me->errors)>0)
            <br>{!! $me->langs->trans('ErrorGoBackAndCorrectParameters') !!}
        @endif

            <script type="text/javascript">
                function jsinfo() {
                    ok = true;

                    //alert('<?php echo \Alxarafe\Lib\Functions::dol_escape_js($me->langs->transnoentities("NextStepMightLastALongTime")); ?>');

                    document.getElementById('nextbutton').style.visibility = "hidden";
                    document.getElementById('pleasewait').style.visibility = "visible";

                    return ok;
                }
            </script>

    </tr>
    </tbody>
    @endsection

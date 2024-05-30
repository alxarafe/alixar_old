<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Alixar installer">
    <link rel="stylesheet" type="text/css" href="{!! $me->config->file->main_url !!}/Templates/install/css/install_default.css">
    <!-- Includes CSS for JQuery -->
    <link rel="stylesheet" type="text/css" href="{!! $me->config->file->main_url !!}/Templates/Lib/jquery/css/base/jquery-ui.min.css"/>
    <!-- Includes JS for JQuery -->
    <script type="text/javascript" src="{!! $me->config->file->main_url !!}/Templates/Lib/jquery/js/jquery.min.js"></script>
    <script type="text/javascript" src="{!! $me->config->file->main_url !!}/Templates/Lib/jquery/js/jquery-ui.min.js"></script>
    <title>{!! $me->langs->trans('DolibarrSetup') !!}</title>
</head>
<body>
<div class="divlogoinstall" style="text-align:center">
    <img class="imglogoinstall" src="{!! $me->config->file->main_url !!}/Templates/common/img/alixar_rectangular_logo.svg" alt="Alixar logo" width="300px"><br>{!! DOL_VERSION !!}
</div>
<br><span class="titre">{!! $me->langs->trans('AlixarSetup') !!}
    @if(isset($subtitle))
        - {!! $subtitle !!}
    @endif
</span>
<br>
<form name="forminstall" style="width: 100%" method="POST">
    <table class="main" width="100%">
        <tr>
            <td>
                <table class="main-inside" width="100%">
                    @section('body')
                        <p>You need to define a body section in your template</p>
                    @show
                </table>
            </td>
        </tr>
    </table>
    @if(isset($me->nextButton) && ($me->nextButton))
        <!-- pFooter -->
        <div class="nextbutton" id="nextbutton">
            <input
                    type="submit"
                    value="{!! $me->langs->trans('NextStep') !!} ->"
                    @if(isset($me->nextButtonJs) && !empty($me->nextButtonJs))
                        onclick="{!! $me->nextButtonJs !!}"
                    @endif
            >
        </div>
    @endif
</form>
<br>
</body>
</html>

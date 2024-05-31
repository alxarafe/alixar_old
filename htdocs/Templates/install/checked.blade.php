@extends('install/master/install_layout')

@section('body')
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="selectlang" value="{!! $me->selectLang !!}">
    <tr>
        <td>
            <h3>
                <img class="valignmiddle inline-block paddingright" src="{!! $me->config->file->main_url !!}/Templates/common/octicons/build/svg/gear.svg" width="20" alt="Database">
                <span class="inline-block">{!! $me->langs->trans('MiscellaneousChecks') !!}</span></h3>
            @foreach($me->checks as $check)
                <img src="{!! $me->config->file->main_url !!}/Templates/theme/{!!  $me->config->main->theme !!}/img/{!! $check['icon'] !!}.png" alt="{!! ucfirst($check['icon']) !!}" class="valignmiddle"> {!! $check['text'] !!}
                <br>
            @endforeach
            <br>

            @if (!empty($me->errorBadMainDocumentRoot))
                <span class="error">{!! $me->langs->trans($me->errorBadMainDocumentRoot) !!}</span><br>
            @endif

            @if(isset($me->printVersion) && $me->printVersion)
                {!! $me->langs->trans("VersionLastUpgrade") !!}:
                <b><span class="ok">{!! (!\Alxarafe\Lib\Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE) !!}</span></b>
                -
                {!! $me->langs->trans("VersionProgram") !!}:
                <b><span class="ok">{!! DOL_VERSION !!}</span></b>
            @endif
            <h3><span class="soustitre">{!! $me->langs->trans("ChooseYourSetupMode") !!}</span></h3>

            @if ($me->errorMigrations !== false)
                <div class="error">{!! $me->errorMigrations !!}</div>
            @else
            @endif

            <form name="forminstall" style="width: 100%" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="selectlang" value="{!! $me->selectLang !!}">
                <table width="100%" class="listofchoices">
                    @foreach($me->availableChoices ?? [] as $choice)
                        <tr class="trlineforchoice{!! $choice['selected'] ? ' choiceselected' : '' !!}">
                            <td class="nowrap center"><b>{!! $choice['short'] !!}</b></td>
                            <td class="listofchoicesdesc">{!! $choice['long'] !!}</td>
                            <td class="center">{!! $choice['button'] !!}</td>
                        </tr>
                    @endforeach
                </table>

                @if(count($me->notAvailableChoices ?? []) > 0)
                    <br>
                    <div id="AShowChoices" style="opacity: 0.5">{!! $me->langs->trans('ShowNotAvailableOptions') !!}
                        ...
                    </div>
                    <div id="navail_choices" style="display:none"><br>
                        <table width="100%" class="listofchoices">
                            @foreach($me->notAvailableChoices ?? [] as $choice)
                                <tr class="trlineforchoice{!! $choice['selected'] ? ' choiceselected' : '' !!}">
                                    <td class="nowrap center"><b>{!! $choice['short'] !!}</b></td>
                                    <td class="listofchoicesdesc">{!! $choice['long'] !!}</td>
                                    <td class="center">{!! $choice['button'] !!}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </form>

            <!-- Añadir el script -->
            <script type="text/javascript">
                $("div#AShowChoices").click(function () {
                    $("div#navail_choices").toggle();
                    if ($("div#navail_choices").css("display") == "none") {
                        $(this).text("> Mostrar opciones no disponibles...");
                    } else {
                        $(this).text("> Ocultar opciones no disponibles...");
                    }
                });

                $(".runupgrade").click(function () {
                    return confirm("{!! dol_escape_js($me->langs->transnoentitiesnoconv('WarningUpdates'), 0, 1) !!}");
                });
            </script>
            <!-- pFooter -->
        </td>
    </tr>
@endsection

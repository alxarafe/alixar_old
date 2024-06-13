@extends('install/master/install_layout')

@section('body')
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="language" value="{!! $me->config->main->language!!}">
    <tr>
        <td>
            <h3>
                <div class="center">
                    <table>
                        <tr>
                            <td>{!! $me->langs->trans('ThemeCurrentlyActive') !!}:</td>
                            <td>{!! $me->selectThemes !!}</td>
                        </tr>
                        <tr>
                            <td>{!! $me->langs->trans('DefaultLanguage') !!}:</td>
                            <td>{!! $me->selectLanguages !!}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><input type="submit" name="action" value="refresh"></td>
                        </tr>
                    </table>
                </div>
                <hr>
                <img class="valignmiddle inline-block paddingright"
                     src="{!! $me->config->main->url !!}/Templates/common/octicons/build/svg/gear.svg"
                     width="20" alt="Database">
                <span class="inline-block">{!! $me->langs->trans('MiscellaneousChecks') !!}</span>
            </h3>
            @foreach($me->vars->checks as $check)
                <img
                    src="{!! $me->config->main->url !!}/Templates/theme/{!!  $me->config->main->theme !!}/img/{!! $check['icon'] !!}.png"
                    alt="{!! ucfirst($check['icon']) !!}" class="valignmiddle"> {!! $check['text'] !!}
                <br>
            @endforeach
            <br>

            @if (!empty($me->errorBadMainDocumentRoot))
                <span class="error">{!! $me->langs->trans($me->errorBadMainDocumentRoot) !!}</span><br>
            @endif

            @if(isset($me->vars->printVersion) && $me->vars->printVersion)
                {!! $me->langs->trans("VersionLastUpgrade") !!}:
                <b><span
                        class="ok">{!! (!\Alxarafe\Lib\Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE) !!}</span></b>
                -
                {!! $me->langs->trans("VersionProgram") !!}:
                <b><span class="ok">{!! DOL_VERSION !!}</span></b>
            @endif
            <h3><span class="soustitre">{!! $me->langs->trans("ChooseYourSetupMode") !!}</span></h3>

            @if ($me->vars->errorMigrations !== false)
                <div class="error">{!! $me->vars->errorMigrations !!}</div>
            @else
            @endif

            <table width="100%" class="listofchoices">
                @foreach($me->vars->availableChoices ?? [] as $choice)
                    <tr class="trlineforchoice{!! $choice['selected'] ? ' choiceselected' : '' !!}">
                        <td class="nowrap center"><b>{!! $choice['short'] !!}</b></td>
                        <td class="listofchoicesdesc">{!! $choice['long'] !!}</td>
                        <td class="center">{!! $choice['button'] !!}</td>
                    </tr>
                @endforeach
            </table>

            @if(count($me->vars->notAvailableChoices ?? []) > 0)
                <br>
                <div id="AShowChoices" style="opacity: 0.5">{!! $me->langs->trans('ShowNotAvailableOptions') !!}
                    ...
                </div>
                <div id="navail_choices" style="display:none"><br>
                    <table width="100%" class="listofchoices">
                        @foreach($me->vars->notAvailableChoices ?? [] as $choice)
                            <tr class="trlineforchoice{!! $choice['selected'] ? ' choiceselected' : '' !!}">
                                <td class="nowrap center"><b>{!! $choice['short'] !!}</b></td>
                                <td class="listofchoicesdesc">{!! $choice['long'] !!}</td>
                                <td class="center">{!! $choice['button'] !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

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

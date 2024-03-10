@extends('master/install_layout')

@section('body')
    <input type="hidden" name="testpost" value="ok">
    <input type="hidden" name="selectlang" value="{!! $selectLang !!}">
    <tr>
        <td>
            <h3>
                <img class="valignmiddle inline-block paddingright" src="Resources/img/gear.svg" width="20" alt="Database">
                <span class="inline-block">{!! $miscellaneousChecks !!}</span></h3>
            @foreach($checks as $check)
                <img src="{!! 'Resources/img/'.$check['icon'].'.png' !!}" alt="{!! ucfirst($check['icon']) !!}" class="valignmiddle"> {!! $check['text'] !!}
                <br>
            @endforeach
            <br>

            @if (!empty($badMainDocumentRoot))
                <span class="error">{!! $badMainDocumentRoot !!}</span><br>
            @endif

            @if(isset($printVersion) && $printVersion)
                {!! $versionLastUpgradeMessage !!}:
                <b><span class="ok">{!! $versionLastUpgrade !!}</span></b>
                -
                {!! $versionProgramMessage !!}:
                <b><span class="ok">{!! DOL_VERSION !!}</span></b>
            @endif
            <h3><span class="soustitre">{!! $chooseYourSetupMode !!}</span></h3>

            @if ($errorMigrations !== false)
                <div class="error">{!! $errorMigrations !!}</div>
            @else
            @endif

            <form name="forminstall" style="width: 100%" method="POST">
                <input type="hidden" name="action" value="update">
                <table width="100%" class="listofchoices">
                    @foreach($availableChoices as $choice)
                        <tr class="trlineforchoice{!! $choice['selected'] ? ' choiceselected' : '' !!}">
                            <td class="nowrap center"><b>{!! $choice['short'] !!}</b></td>
                            <td class="listofchoicesdesc">{!! $choice['long'] !!}</td>
                            <td class="center">{!! $choice['button'] !!}</td>
                        </tr>
                    @endforeach
                </table>

                @if(count($notAvailableChoices)>0)
                    <br>
                    <div id="AShowChoices" style="opacity: 0.5">{!! $showNotAvailableOptions !!}
                        ...
                    </div>
                    <div id="navail_choices" style="display:none"><br>
                        <table width="100%" class="listofchoices">
                            @foreach($notAvailableChoices as $choice)
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
                    return confirm("{!! $warningUpdates !!}");
                });
            </script>
            <!-- pFooter -->
        </td>
    </tr>
@endsection
@extends('master/install_layout')

@section('body')
    <form name="forminstall" style="width: 100%" method="POST">
        <input type="hidden" name="testpost" value="ok">
        <input type="hidden" name="action" value="set">
        <table class="main" width="100%">
            <tr>
                <td>
                    <table class="main-inside" width="100%">
                        <tr>
                            <td>
                                <h3>
                                    <img class="valignmiddle inline-block paddingright" src="Resources/img/gear.svg" width="20" alt="Database">
                                    <span class="inline-block">{!! $lang->trans('MiscellaneousChecks') !!}</span></h3>
                                @foreach($checks as $check)
                                    <img src="{!! 'Resources/img/'.$check['icon'].'.png' !!}" alt="{!! ucfirst($check['icon']) !!}" class="valignmiddle"> {!! $check['text'] !!}
                                    <br>
                                @endforeach
                                <br>

                                @if (!empty($errorBadMainDocumentRoot))
                                    <span class="error">{!! $lang->trans($errorBadMainDocumentRoot) !!}</span><br>
                                @endif

                                @if($printVersion)
                                    {!! $lang->trans("VersionLastUpgrade") !!}: <b><span class="ok">{!! (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE) !!}</span></b> -
                                    {!! $lang->trans("VersionProgram") !!}: <b><span class="ok">{!! DOL_VERSION !!}</span></b>
                                @endif
                                <h3><span class="soustitre">{!! $lang->trans("ChooseYourSetupMode") !!}</span></h3>

                                <!-- Añadir el script -->
                                <script type="text/javascript">

                                    $("div#AShowChoices").click(function () {

                                        $("div#navail_choices").toggle();

                                        if ($("div#navail_choices").css("display") == "none") {
                                            $(this).text("> Mostrar opciones no disponibles...");
                                        } else {
                                            $(this).text("Ocultar opciones no disponibles...");
                                        }

                                    });

                                    /*
                                    $(".runupgrade").click(function() {
                                        return confirm("Advertencia: \\
                                    ¿Ha realizado una copia de seguridad de su base de datos antes? \\
                                    Esto es altamente recomendado: por ejemplo, debido a algunos errores en los sistemas de bases de datos (por ejemplo MySQL versión 5.5.40/41/42/43), algunos datos o tablas pueden perderse durante este proceso, por lo que es altamente recomendado tener un volcado completo de la base de datos antes de iniciar la actualización.

                                    Haga clic en Aceptar para iniciar el proceso de actualización...");
                                    });
                                    */

                                </script>

                                <!-- pFooter -->
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <!-- pFooter -->
    </form>
@endsection
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
                                <br>
                                <h3><span class="soustitre">{!! $lang->trans('ChooseYourSetupMode') !!}</span></h3>
                                <table width="100%" class="listofchoices">
                                    <tr class="trlineforchoice choiceselected">
                                        <td class="nowrap center"><b>{!! $lang->trans("FreshInstall") !!}</b></td>
                                        <td class="listofchoicesdesc">Utilizar este m&eacute;todo si es su primera
                                            instalaci&oacute;n. Si no es el caso, este m&eacute;todo puede reparar una
                                            instalaci&oacute;n anterior incompleta, pero si quiere actualizar una versi&oacute;n
                                            anterior, escoja el m&eacute;todo "Actualizaci&oacute;n".<br>
                                            <div class="center">
                                                <div class="ok suggestedchoice"><b>Opci&oacute;n sugerida por el
                                                        instalador</b>.
                                                </div>
                                            </div>
                                        </td>
                                        <td class="center">
                                            <a class="button" href="fileconf.php?selectlang=auto">Empezar</a></td>
                                    </tr>
                                </table>
                                <br>
                                <div id="AShowChoices" style="opacity: 0.5">> Mostrar opciones no disponibles...</div>
                                <div id="navail_choices" style="display:none"><br>
                                    <table width="100%" class="listofchoices">
                                        <!-- choice 1 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.0.* -> 3.1.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 2 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.1.* -> 3.2.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 3 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.2.* -> 3.3.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 4 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.3.* -> 3.4.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 5 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.4.* -> 3.5.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 6 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.5.* -> 3.6.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 7 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.6.* -> 3.7.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 8 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.7.* -> 3.8.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 9 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.8.* -> 3.9.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 10 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>3.9.* -> 4.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 11 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>4.0.* -> 5.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 12 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>5.0.* -> 6.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 13 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>6.0.* -> 7.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 14 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>7.0.* -> 8.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 15 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>8.0.* -> 9.0.*</b></td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 16 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>9.0.* -> 10.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 17 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>10.0.* -> 11.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 18 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>11.0.* -> 12.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 19 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>12.0.* -> 13.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 20 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>13.0.* -> 14.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 21 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>14.0.* -> 15.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 22 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>15.0.* -> 16.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 23 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>16.0.* -> 17.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 24 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>17.0.* -> 18.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 25 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>18.0.* -> 19.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>

                                        <!-- choice 26 -->
                                        <tr>
                                            <td class="nowrap center"><b>Actualizaci&oacute;n<br>19.0.* -> 20.0.*</b>
                                            </td>
                                            <td class="listofchoicesdesc">Utilice este m&eacute;todo despu&eacute;s de
                                                haber actualizado los archivos de una instalaci&oacute;n Dolibarr
                                                antigua por los de una versi&oacute;n m&aacute;s reciente. Esta elecci&oacute;n
                                                permite poner al d&iacute;a la base de datos y sus datos para esta nueva
                                                versi&oacute;n.
                                            </td>
                                            <td class="center">No disponible</td>
                                        </tr>
                                    </table>
                                </div>
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
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <!-- pFooter -->
    </form>
@endsection
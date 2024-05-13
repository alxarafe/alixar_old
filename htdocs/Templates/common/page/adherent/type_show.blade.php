@extends('layout.main')

@section('content')
    <!-- Begin div class="fiche" -->
    <div class="fiche">

        <!-- dol_fiche_head - dol_get_fiche_head -->
        <div class="tabs" data-role="controlgroup" data-type="horizontal">
            <a class="tabTitle"><span class="fas fa-users imgTabTitle em092 infobox-adherent" style="" title="Tipo de miembro"></span>
                <span class="tabTitleText">Tipo de miembro</span></a>
            <div class="inline-block tabsElem tabsElemActive"><!-- id tab = card -->
                <div class="tab tabactive" style="margin: 0 !important">
                    <a id="card" class="tab inline-block valignmiddle" href="/adherents/type.php?rowid=1" title="Tipo de miembro">Tipo
                        de miembro</a>
                </div>
            </div>
        </div>

        <div id="dragDropAreaTabBar" class="tabBar">
            <div class="arearef heightref valignmiddle centpercent"><!-- Start banner content -->
                <div style="vertical-align: middle">
                    <div class="pagination paginationref">
                        <ul class="right"><!-- morehtml -->
                            <li class="noborder litext clearbothonsmartphone">
                                <a href="/adherents/type.php?restore_lastsearch_values=1">Volver al listado</a></li>
                            <li class="pagination">
                                <span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span></li>
                            <li class="pagination">
                                <span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span></li>
                        </ul>
                    </div>
                    <div class="statusref"><span class="badge  badge-status4 badge-status" title="Activo">Activo</span>
                    </div><!-- morehtmlleft -->
                    <div class="inline-block floatleft"><!-- No photo to show -->
                        <div class="floatleft inline-block valignmiddle divphotoref">
                            <div class="photoref">
                                <span class="fas fa-user-friends  em092 infobox-adherent" style="" title="No photo"></span>
                            </div>
                        </div>
                    </div>
                    <div class="inline-block floatleft valignmiddle maxwidth750 marginbottomonly refid refidpadding">
                        Estándar
                    </div>
                </div><!-- End banner content --></div>
            <div class="underrefbanner clearboth"></div>
            <div class="fichecenter">
                <div class="underbanner clearboth"></div>
                <table class="tableforfield border centpercent">
                    <tr>
                        <td>Naturaleza de los miembros</td>
                        <td class="valeur">Corporaci&oacute;n e Individuo</td>
                    </tr>
                    <tr>
                        <td>
                            <span style="padding: 0px; padding-right: 3px;">Sujeto a cotizaci&oacute;n</span><span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Si se requiere suscripci&oacute;n, se debe registrar una suscripci&oacute;n con una fecha de inicio o finalizaci&oacute;n para tener al miembro al d&iacute;a (cualquiera que sea el monto de la suscripci&oacute;n, incluso si la suscripci&oacute;n es gratuita)."><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>
                        </td>
                        <td>S&iacute;
                    </tr>
                    <tr>
                        <td class="titlefield">Importe</td>
                        <td><span class="amount">0,00</span>
                    </tr>
                    <tr>
                        <td>
                            <span style="padding: 0px; padding-right: 3px;">Cualquier importe</span><span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="El monto de la suscripci&oacute;n puede ser definido por el miembro"><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>
                        </td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td>Voto autorizado</td>
                        <td>S&iacute;
                    </tr>
                    <tr>
                        <td class="titlefield">Duraci&oacute;n</td>
                        <td colspan="2">0&nbsp;&nbsp;</td>
                    </tr>
                    <tr>
                        <td class="tdtop">Descripci&oacute;n</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="tdtop">Email de bienvenida</td>
                        <td>Email de bienvenida del miembro 1</td>
                    </tr><!-- BEGIN PHP TEMPLATE extrafields_view.tpl.php -->
                    <!-- END PHP TEMPLATE extrafields_view.tpl.php -->
                </table>
            </div>
        </div>
        <div class="tabsAction">
            <div class="inline-block divButAction">
                <a class="butAction" href="/adherents/type.php?action=edit&token=41537515f6ed0e366aa3af898a41c299&rowid=1">Modificar</a>
            </div>
            <div class="inline-block divButAction">
                <a class="butAction" href="card.php?action=create&token=41537515f6ed0e366aa3af898a41c299&typeid=1&backtopage=%2Fadherents%2Ftype.php%3Frowid%3D1">Crear
                    miembro</a></div>
            <div class="inline-block divButAction">
                <a class="butActionDelete" href="/adherents/type.php?action=delete&token=41537515f6ed0e366aa3af898a41c299&rowid=1">Eliminar</a>
            </div>
        </div>
        <form method="POST" id="searchFormList" action="/adherents/type.php" name="formfilter" autocomplete="off">
            <input type="hidden" name="token" value="41537515f6ed0e366aa3af898a41c299"><input class="flat" type="hidden" name="rowid" value="1"></td>
            <!-- Begin title -->
            <table class="centpercent notopnoleftnoright table-fiche-title">
                <tr>
                    <td class="nobordernopadding valignmiddle col-title">
                        <div class="titre inline-block"></div>
                    </td>
                    <td class="nobordernopadding valignmiddle right col-right">
                        <input type="hidden" name="pageplusoneold" value="1">
                        <div class="pagination">
                            <ul>
                                <li class="pagination">
                                    <select class="flat selectlimit" name="limit" title="N&ordm; m&aacute;ximo de registros por p&aacute;gina">
                                        <option name="10">10</option>
                                        <option name="15">15</option>
                                        <option name="20" selected="selected">20</option>
                                        <option name="30">30</option>
                                        <option name="40">40</option>
                                        <option name="50">50</option>
                                        <option name="100">100</option>
                                        <option name="250">250</option>
                                        <option name="500">500</option>
                                        <option name="1000">1000</option>
                                        <option name="5000">5000</option>
                                        <option name="10000">10000</option>
                                        <option name="20000">20000</option>
                                    </select><!-- JS CODE TO ENABLE select limit to launch submit of page -->
                                    <script>
                                        jQuery(document).ready(function () {
                                            jQuery(".selectlimit").change(function () {
                                                console.log("Change limit. Send submit");
                                                $(this).parents('form:first').submit();
                                            });
                                        });
                                    </script>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>
            <!-- End title -->

            <div class="div-table-responsive">
                <table class="tagtable liste">
                    <tr class="liste_titre_filter">
                        <td class="liste_titre left">
                            <input class="flat maxwidth100" type="text" name="search_ref" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth100" type="text" name="search_lastname" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth100" type="text" name="search_login" value=""></td>
                        <td class="liste_titre">&nbsp;</td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth100" type="text" name="search_email" value=""></td>
                        <td class="liste_titre">&nbsp;</td>
                        <td class="liste_titre">&nbsp;</td>
                        <td class="liste_titre center nowraponall">
                            <input type="image" class="liste_titre" src="/theme/eldy/img/search.png" name="button_search" value="Buscar" title="Buscar">&nbsp;
                            <input type="image" class="liste_titre" src="/theme/eldy/img/searchclear.png" name="button_removefilter" value="Eliminar filtro" title="Eliminar filtro">
                        </td>
                    </tr>
                    <tr class="liste_titre">
                        <th class="wrapcolumntitle liste_titre" title="Ref.">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.ref&sortorder=desc&begin=&rowid=1&contextpage=adherentstype&status=1&">Ref.</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre_sel" title="Nombre / Empresa">
                            <span class="nowrap"><span class="fas fa-caret-up imgup paddingright" style="" title="Z-A"></span></span><a class="reposition" href="/adherents/type.php?sortfield=d.lastname&sortorder=asc&begin=&rowid=1&contextpage=adherentstype&status=1&">Nombre
                                / Empresa</a></th>
                        <th class="wrapcolumntitle liste_titre" title="Login">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.login&sortorder=desc&begin=&rowid=1&contextpage=adherentstype&status=1&">Login</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Naturaleza del miembro">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.morphy&sortorder=desc&begin=&rowid=1&contextpage=adherentstype&status=1&">Naturaleza
                                del miembro</a></th>
                        <th class="wrapcolumntitle liste_titre" title="EMail">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.email&sortorder=desc&begin=&rowid=1&contextpage=adherentstype&status=1&">EMail</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Estado">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.statut,d.datefin&sortorder=desc,desc&begin=&rowid=1&contextpage=adherentstype&status=1&">Estado</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" align="center" title="Fin afiliaci&oacute;n">
                            <a class="reposition" href="/adherents/type.php?sortfield=d.datefin&sortorder=desc&begin=&rowid=1&contextpage=adherentstype&status=1&">Fin
                                afiliaci&oacute;n</a></th>
                        <th class="wrapcolumntitle liste_titre" width="60" align="center" title="Acci&oacute;n">Acci&oacute;n</th>
                    </tr>
                    <tr>
                        <td colspan="9"><span class="opacitymedium">Nada</span></td>
                    </tr>
                </table>
            </div>
        </form>
    </div> <!-- End div class="fiche" -->
@endsection

@push('scripts')
    {{-- <script src="https://alixar/Templates/Lib/additional-script.js"></script> --}}
@endpush

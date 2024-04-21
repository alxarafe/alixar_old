@extends('layout.main')

@section('content')
    <!-- Begin div class="fiche" -->
    <div class="fiche">
        <form method="POST" action="/adherents/type.php">
            <input type="hidden" name="token" value="620b623c33d2f0005e3bf280288fe1b7"><input type="hidden" name="formfilteraction" id="formfilteraction" value="list"><input type="hidden" name="action" value="list"><input type="hidden" name="sortfield" value="d.lastname"><input type="hidden" name="sortorder" value="DESC"><input type="hidden" name="mode" value="">
            <!-- Begin title -->
            <table class="centpercent notopnoleftnoright table-fiche-title">
                <tr>
                    <td class="nobordernopadding widthpictotitle valignmiddle col-picto">
                        <span class="fas fa-user-friends  em092 infobox-adherent valignmiddle pictotitle widthpictotitle" style=""></span>
                    </td>
                    <td class="nobordernopadding valignmiddle col-title">
                        <div class="titre inline-block">Tipos de
                            miembros<span class="opacitymedium colorblack paddingleft">(1)</span></div>
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
                                <li class="paginationafterarrows">
                                    <a class="btnTitle reposition btnTitleSelected" href="/adherents/type.php?mode=common&amp;contextpage=ControllerAdherentTypeController" title="Vista de listado"><span class="fa fa-bars imgforviewmode valignmiddle btnTitle-icon"></span></a><a class="btnTitle reposition" href="/adherents/type.php?mode=kanban&amp;contextpage=ControllerAdherentTypeController" title="Vista Kanban"><span class="fa fa-th-list imgforviewmode valignmiddle btnTitle-icon"></span></a><span class="button-title-separator "></span><a class="btnTitle btnTitlePlus" href="https://alixar/adherents/type.php?action=create" title="Nuevo tipo de miembro"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>
                                </li>
                            </ul>
                        </div>
                        <script nonce="97094dfb">
                            jQuery(document).ready(function () {
                                jQuery(".pageplusone").click(function () {
                                    jQuery(this).select();
                                });
                            });
                        </script>
                    </td>
                </tr>
            </table>
            <!-- End title -->

            <div class="div-table-responsive">
                <table class="tagtable liste">
                    <tr class="liste_titre">
                        <th>Ref.</th>
                        <th>Etiqueta</th>
                        <th class="center">Naturaleza de los miembros</th>
                        <th class="center">Duraci&oacute;n</th>
                        <th class="center">Sujeto a cotizaci&oacute;n</th>
                        <th class="center">Importe</th>
                        <th class="center">Cualquier importe</th>
                        <th class="center">Voto autorizado</th>
                        <th class="center">Estado</th>
                        <th>&nbsp;</th>
                    </tr>
                    <tr class="oddeven">
                        <td class="nowraponall">
                            <a href="https://alixar/adherents/type.php?rowid=7" title="&lt;span class=&quot;fas fa-user-friends  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Tipo de miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;Activo&quot;&gt;Activo&lt;/span&gt;&lt;br&gt;Etiqueta: 7&lt;br&gt;Sujeto a cotizaci&oacute;n: S&iacute;" class="classfortooltip"><span class="fas fa-user-friends  em092 infobox-adherent paddingright" style=""></span>7</a>
                        </td>
                        <td>Partner</td>
                        <td class="center">Corporaci&oacute;n e Individuo</td>
                        <td class="center nowrap">1 A&ntilde;o</td>
                        <td class="center">S&iacute;</td>
                        <td class="center"><span class="amount">50,00</span></td>
                        <td class="center">No</td>
                        <td class="center">S&iacute;</td>
                        <td class="center"><span class="badge  badge-status4 badge-status" title="Activo">Activo</span>
                        </td>
                        <td class="right">
                            <a class="editfielda" href="/adherents/type.php?action=edit&rowid=7"><span class="fas fa-pencil-alt" style=" color: #444;" title="Modificar"></span></a>
                        </td>
                    </tr>
                </table>
            </div>
        </form>

    </div> <!-- End div class="fiche" -->
@endsection

@push('scripts')
    <script src="https://alixar/Templates/Lib/additional-script.js"></script>
@endpush

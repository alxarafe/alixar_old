<!-- Begin side-nav id-left -->
<div class="side-nav">
    <div id="id-left">
        <!-- Begin left menu -->
        <div class="vmenu">


            <!-- Begin SearchForm -->
            <div id="blockvmenusearch" class="blockvmenusearch">
                <select type="text" title="Atajo de teclado ALT + s" id="searchselectcombo" class="searchselectcombo vmenusearchselectcombo" accesskey="s" name="searchselectcombo">
                    <option></option>
                </select>
                <script>
                    jQuery(document).keydown(function (e) {
                        if (e.which === 70 && e.ctrlKey && e.shiftKey) {
                            console.log('control + shift + f : trigger open global-search dropdown');
                            openGlobalSearchDropDown();
                        }
                        if ((e.which === 83 || e.which === 115) && e.altKey) {
                            console.log('alt + s : trigger open global-search dropdown');
                            openGlobalSearchDropDown();
                        }
                    });

                    var openGlobalSearchDropDown = function () {
                        jQuery("#searchselectcombo").select2('open');
                    }
                </script>
            </div>
            <!-- End SearchForm -->
            <div class="blockvmenu blockvmenupair blockvmenufirst">
                <!-- Process menu entry with mainmenu=members, leftmenu=members, level=0 enabled=1, position=0 -->
                <div class="menu_titre">
                    <a class="vmenu" title="Miembros" href="https://alixar/adherents/index.php?leftmenu=members&amp;mainmenu=members"><span class="fas fa-user-alt  em092 infobox-adherent paddingright pictofixedwidth" style=""></span>Miembros</a>
                </div>
                <div class="menu_top"></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_card">
                    <a class="vsmenu" title="Nuevo miembro" href="https://alixar/adherents/card.php?leftmenu=members&amp;action=create">Nuevo
                        miembro</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    <a class="vsmenu" title="Listado" href="https://alixar/adherents/list.php?leftmenu=members">Listado</a><br>
                </div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros borrador" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=-1">Miembros
                        borrador</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros validados" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=1">Miembros
                        validados</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Membres&iacute;a pendiente" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=waitingsubscription">Membres&iacute;a
                        pendiente</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="A hoy" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=uptodate">A
                        hoy</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Fuera de plazo" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=outofdate">Fuera
                        de plazo</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros de baja" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=0">Miembros
                        de baja</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros excluidos" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=-2">Miembros
                        excluidos</a><br></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_stats_index">
                    <a class="vsmenu" title="Estad&iacute;sticas" href="https://alixar/adherents/stats/index.php?leftmenu=members">Estad&iacute;sticas</a><br>
                </div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_cartes_carte">
                    <a class="vsmenu" title="Generaci&oacute;n de tarjetas para socios" href="https://alixar/adherents/cartes/carte.php?leftmenu=export">Generaci&oacute;n
                        de tarjetas para socios</a><br></div>
                <!-- Process menu entry with mainmenu=members, leftmenu=cat, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_categories_index">
                    <a class="vsmenu" title="Etiquetas/Categor&iacute;as" href="https://alixar/categories/index.php?leftmenu=cat&amp;type=3">Etiquetas/Categor&iacute;as</a><br>
                </div>
                <div class="menu_end"></div>
            </div>
            <div class="blockvmenu blockvmenuimpair">
                <!-- Process menu entry with mainmenu=members, leftmenu=members, level=0 enabled=1, position=0 -->
                <div class="menu_titre">
                    <a class="vmenu" title="Afiliaciones" href="https://alixar/adherents/index.php?leftmenu=members&amp;mainmenu=members"><span class="fas fa-money-check-alt  em080 infobox-bank_account paddingright pictofixedwidth" style=""></span>Afiliaciones</a>
                </div>
                <div class="menu_top"></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_list">
                    <a class="vsmenu" title="Registro" href="https://alixar/adherents/list.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members">Registro</a><br>
                </div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_subscription_list">
                    <a class="vsmenu" title="Listado" href="https://alixar/adherents/subscription/list.php?leftmenu=members">Listado</a><br>
                </div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_stats_index">
                    <a class="vsmenu" title="Estad&iacute;sticas" href="https://alixar/adherents/stats/index.php?leftmenu=members">Estad&iacute;sticas</a><br>
                </div>
                <div class="menu_end"></div>
            </div>
            <div class="blockvmenu blockvmenupair blockvmenulast">
                <!-- Process menu entry with mainmenu=members, leftmenu=setup, level=0 enabled=1, position=0 -->
                <div class="menu_titre">
                    <a class="vmenu" title="Tipos de miembros" href="https://alixar/adherents/type.php?leftmenu=setup&amp;mainmenu=members"><span class="fas fa-user-friends  em092 infobox-adherent paddingright pictofixedwidth" style=""></span>Tipos
                        de miembros</a></div>
                <div class="menu_top"></div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_type">
                    <a class="vsmenu" title="Nuevo" href="https://alixar/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create">Nuevo</a><br>
                </div>
                <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                <div class="menu_contenu menu_contenu_adherents_type">
                    <a class="vsmenu" title="Listado" href="https://alixar/adherents/type.php?leftmenu=setup&amp;mainmenu=members">Listado</a><br>
                </div>
                <div class="menu_end"></div>
            </div>
            <div class="blockvmenuend"></div>
            <!-- Begin Help Block-->
            <div id="blockvmenuhelp" class="blockvmenuhelp">
            </div>
            <!-- End Help Block-->

        </div>
        <!-- End left menu -->
    </div>
</div> <!-- End side-nav id-left -->

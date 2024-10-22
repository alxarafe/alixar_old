<!-- Start top horizontal -->
<header id="id-top" class="side-nav-vert">
    <div id="tmenu_tooltip" class="tmenu">
        <div class="tmenudiv">
            <ul role="navigation" class="tmenu">
                <li class="tmenu menuhider nohover" id="mainmenutd_menu">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu menuhider nohover" tabindex="-1" href="#" title="">
                            <div class="mainmenu menu topmenuimage"><span class="fas fa-bars size12x"></span></div>
                        </a><a class="tmenulabel tmenu menuhider nohover" id="mainmenua_menu" href="#" title=""><span class="mainmenuaspan"></span></a>
                    </div>
                </li>
                @foreach($me->menu as $menu)
                    @include('component/menu/top_menu_item', [
                        'name' => $menu['name'],
                        'href' => $menu['href'],
                        'prefix' => $menu['prefix'],
                        'title' => $menu['title'],
                        'selected' => $menu['selected'] ?? false,
                    ])
                @endforeach
                <li class="tmenuend" id="mainmenutd_">
                    <div class="tmenucenter"></div>
                </li>
            </ul>
        </div>
    </div>
    <div class="login_block usedropdown">
        <div class="login_block_other">
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="M&oacute;dulo Builder">
                    <a href="https://alixar/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="modulebuilder"><span class="fa fa-bug atoplogin valignmiddle"></span></a>
                </div>
            </div>
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Mostrar p&aacute;gina de impresi&oacute;n de la zona central">
                    <a href="/adherents/type.php?url_route=adherents&amp;url_filename=type&amp;url_route=adherents&amp;url_filename=type&amp;leftmenu=setup&amp;mainmenu=members&token=620b623c33d2f0005e3bf280288fe1b7&actionlogin=login&loginfunction=loginfunction&tz=1&tz_string=Europe%2FMadrid&dst_observed=1&dst_first=2024-03-31T01%3A59%3A00Z&dst_second=2024-10-27T02%3A59%3A00Z&screenwidth=1912&screenheight=918&dol_hide_topmenu=0&dol_hide_leftmenu=0&dol_optimize_smallscreen=0&dol_no_mouse_hover=0&dol_use_jmobile=0&username=rsanjose&optioncss=print" target="_blank" rel="noopener noreferrer"><span class="fa fa-print atoplogin valignmiddle"></span></a>
                </div>
            </div>
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Leer la ayuda en l&iacute;nea (es necesario acceso a Internet ), &lt;br&gt;&lt;span class=&quot;fas fa-external-link-alt pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;P&aacute;gina Wiki &quot;M&oacute;dulo Miembros&quot; &lt;span class=&quot;opacitymedium&quot;&gt;(P&aacute;gina de ayuda dedicada relacionada con su pantalla actual)&lt;/span&gt;">
                    <a class="help" target="_blank" rel="noopener noreferrer" href="http://wiki.dolibarr.org/index.php/M%C3%B3dulo_Miembros"><span class="fa fa-question-circle atoplogin valignmiddle helppresent"></span><span class="fa fa-long-arrow-alt-up helppresentcircle"></span></a>
                </div>
            </div>
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Dolibarr 20.0.0-alpha">
                    <span class="aversion"><span class="hideonsmartphone small">20.0.0-alpha</span></span></div>
            </div>
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem logout-btn inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Desconexi&oacute;n&lt;br&gt;">
                    <a accesskey="l" href="https://alixar/user/logout.php?token=620b623c33d2f0005e3bf280288fe1b7"><span class="fas fa-sign-out-alt atoplogin valignmiddle" style="" title="Desconexi&oacute;n (Atajo de teclado ALT + l)"></span></a>
                </div>
            </div>
        </div>
        <div class="login_block_user">
            <div class="inline-block login_block_elem login_block_elem_name nowrap centpercent" style="padding: 0px;">
                <!-- div for bookmark link -->
                <div id="topmenu-bookmark-dropdown" class="dropdown inline-block">
                    <a accesskey="b" class="dropdown-toggle login-dropdown-a nofocusvisible" data-toggle="dropdown" href="#" title="Marcadores (Atajo de teclado ALT + b)"><i class="fa fa-star"></i></a>
                    <div class="dropdown-menu">

                        <!-- search input -->
                        <div class="dropdown-header bookmark-header">
                            <!-- form with POST method by default, will be replaced with GET for external link by js -->
                            <form id="top-menu-action-bookmark" name="actionbookmark" method="POST" action="" onsubmit="return false">
                                <input type="hidden" name="token" value="620b623c33d2f0005e3bf280288fe1b7"><input name="bookmark" id="top-bookmark-search-input" class="dropdown-search-input" placeholder="Marcadores" autocomplete="off">
                            </form>
                        </div>

                        <!-- Menu bookmark tools-->
                        <div class="bookmark-footer">
                            <a class="top-menu-dropdown-link" title="A&ntilde;adir esta p&aacute;gina a los marcadores" href="https://alixar/bookmarks/card.php?action=create&amp;url=%2Fadherents%2Ftype.php%3Furl_route%3Dadherents%26url_filename%3Dtype%26leftmenu%3Dsetup%26mainmenu%3Dmembers"><span class="fas fa-plus-circle paddingright" style=""></span>A&ntilde;adir
                                esta p&aacute;gina a los
                                marcadores</a><a class="top-menu-dropdown-link" title="Marcadores" href="https://alixar/bookmarks/list.php"><span class="fas fa-pencil-alt paddingright opacitymedium" style=" color: #444;"></span>Listar/editar
                                marcadores</a>
                            <div class="clearboth"></div>
                        </div>

                        <!-- Menu Body bookmarks -->
                        <div class="bookmark-body dropdown-body">
                            <div id="dropdown-bookmarks-list"></div>
                            <span id="top-bookmark-search-nothing-found" class="opacitymedium">No se encontr&oacute; ning&uacute;n marcador</span>
                        </div>
                        <!-- script to open/close the popup -->
                        <script>
                            jQuery(document).on("keyup", "#top-bookmark-search-input", function () {
                                console.log("keyup in bookmark search input");

                                var filter = $(this).val(), count = 0;
                                jQuery("#dropdown-bookmarks-list .bookmark-item").each(function () {
                                    if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                                        $(this).addClass("hidden-search-result");
                                    } else {
                                        $(this).removeClass("hidden-search-result");
                                        count++;
                                    }
                                });
                                jQuery("#top-bookmark-search-filter-count").text(count);
                                if (count == 0) {
                                    jQuery("#top-bookmark-search-nothing-found").removeClass("hidden-search-result");
                                } else {
                                    jQuery("#top-bookmark-search-nothing-found").addClass("hidden-search-result");
                                }
                            });
                        </script>
                    </div>
                </div>
                <!-- Code to show/hide the bookmark drop-down -->
                <script>
                    jQuery(document).ready(function () {
                        jQuery(document).on("click", function (event) {
                            if (!$(event.target).closest("#topmenu-bookmark-dropdown").length) {
                                //console.log("close bookmark dropdown - we click outside");
                                // Hide the menus.
                                $("#topmenu-bookmark-dropdown").removeClass("open");
                            }
                        });

                        jQuery("#topmenu-bookmark-dropdown .dropdown-toggle").on("click", function (event) {
                            console.log("Click on #topmenu-bookmark-dropdown .dropdown-toggle");
                            openBookMarkDropDown(event);
                        });

                        // Key map shortcut
                        jQuery(document).keydown(function (event) {
                            if (event.which === 77 && event.ctrlKey && event.shiftKey) {
                                console.log("Click on control + shift + m : trigger open bookmark dropdown");
                                openBookMarkDropDown(event);
                            }
                        });

                        var openBookMarkDropDown = function (event) {
                            event.preventDefault();
                            jQuery("#topmenu-bookmark-dropdown").toggleClass("open");
                            jQuery("#top-bookmark-search-input").focus();
                        }

                    });
                </script>
                <!-- div for user link -->
                <div id="topmenu-login-dropdown" class="userimg atoplogin dropdown user user-menu inline-block">
                    <a href="https://alixar/user/card.php?id=1" class="dropdown-toggle login-dropdown-a" data-toggle="dropdown">
                        <img class="photo photouserphoto userphoto" alt="" src="https://alixar/public/theme/common/user_anonymous.png"><span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone paddingleft">rsanjose</span>
                    </a>
                    <div class="dropdown-menu">
                        <!-- User image -->
                        <div class="user-header">
                            <img class="photo dropdown-user-image" alt="" src="https://alixar/public/theme/common/user_anonymous.png">
                            <p>
                                <i class="far fa-star classfortooltip" title="Administrador de sistema"></i> SuperAdmin
                                (rsanjose)<br><small class="classfortooltip" title="Conectado desde : 21/04/2024 09:28&lt;br&gt;Conexi&oacute;n anterior : 20/04/2024 18:21"><i class="fa fa-user-clock"></i>
                                    21/04/2024
                                    09:28</small><br><small class="classfortooltip" title="Conectado desde : 21/04/2024 09:28&lt;br&gt;Conexi&oacute;n anterior : 20/04/2024 18:21"><i class="fa fa-user-clock opacitymedium"></i>
                                    20/04/2024 18:21</small><br>
                            </p>
                        </div>

                        <!-- Menu Body user-->
                        <div class="user-body"><span id="topmenulogincompanyinfo-btn"><i class="fa fa-caret-right"></i> Mostrar informaci&oacute;n de la empresa</span>
                            <div id="topmenulogincompanyinfo"><br><b>Empresa</b>:
                                <span>rSanjoSEO</span><br><b>CIF/NIF</b>: <span></span><br><b>N&uacute;m. seguridad
                                    social</b>: <span></span><br><b>CNAE</b>: <span></span><br><b>N&uacute;m.
                                    colegiado</b>: <span></span><br><b>Prof Id 5 (n&uacute;mero EORI)</b>: <span></span><br><b>CIF
                                    intra.</b>: <span></span><br><b>Pa&iacute;s</b>: <span>Espa&ntilde;a</span><br><b>Divisa</b>:
                                <span>EUR</span></div>
                            <br><span id="topmenuloginmoreinfo-btn"><i class="fa fa-caret-right"></i> Mostrar m&aacute;s informaci&oacute;n</span>
                            <div id="topmenuloginmoreinfo"><br><b>Administrador de sistema</b>:
                                S&iacute;<br><b>Tipo:</b> Interno<br><b>Estado</b>:
                                Activado<br><br><u>Session</u><br><b>Direcci&oacute;n IP</b>: 127.0.0.1<br><b>Modo de
                                    autentificaci&oacute;n:</b> dolibarr<br><b>Conectado desde:</b> 21/04/2024 09:28<br><b>Conexi&oacute;n
                                    anterior:</b> 20/04/2024 18:21<br><b>Tema actual:</b> eldy<br><b>Gestor men&uacute;
                                    actual:</b> eldy<br><b>Idioma actual:</b>
                                <span class="flag-sprite es" title="es_ES"></span> es_ES<br><b>Zona horaria cliente
                                    (usuario):</b> +2 (Europe/Madrid)<br><b>Navegador:</b> chrome 123.0.0.0
                                <small class="opacitymedium">(Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML,
                                    like Gecko) Chrome/123.0.0.0 Safari/537.36)</small><br><b>Presentaci&oacute;n:</b>
                                classic<br><b>Pantalla:</b> 1912 x 918
                            </div>
                        </div>

                        <!-- Menu Footer-->
                        <div class="user-footer">
                            <div class="pull-left">
                                <a accesskey="u" href="https://alixar/user/card.php?id=1" class="button-top-menu-dropdown" title="Su ficha de usuario (Atajo de teclado ALT + u)"><i class="fa fa-user"></i>
                                    Ficha</a>
                            </div>
                            <div class="pull-left">
                                <!-- a link for button to open url into a dialog popup with backtopagejsfields =  --><a accesskey="v" class="cursorpointer reposition button_publicvirtualcardmenu button-top-menu-dropdown marginleftonly nohover" title="URL de la p&aacute;gina de la tarjeta de visita virtual - SuperAdmin (Atajo de teclado ALT + v)" href="#" onclick="closeTopMenuLoginDropdown()"><span class="far fa-address-card" style="" title="URL de la p&aacute;gina de la tarjeta de visita virtual (Atajo de teclado ALT + v)"></span></a>
                                <!-- code to open popup and variables to retrieve returned variables -->
                                <div id="idfordialogpublicvirtualcardmenu" class="hidden">div for dialog</div>
                                <div id="varforreturndialogidpublicvirtualcardmenu" class="hidden">div for returned id
                                </div>
                                <div id="varforreturndialoglabelpublicvirtualcardmenu" class="hidden">div for returned
                                    label
                                </div><!-- Add js code to open dialog popup on dialog -->
                                <script nonce="97094dfb" type="text/javascript">
                                    jQuery(document).ready(function () {
                                        jQuery(".button_publicvirtualcardmenu").click(function () {
                                            console.log('Open popup with jQuery(...).dialog() on URL https://alixar/user/virtualcard.php?id=1&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=publicvirtualcardmenu');
                                            var $tmpdialog = $('#idfordialogpublicvirtualcardmenu');
                                            $tmpdialog.html('<iframe class="iframedialog" id="iframedialogpublicvirtualcardmenu" style="border: 0px;" src="https://alixar/user/virtualcard.php?id=1&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=publicvirtualcardmenu" width="100%" height="98%"></iframe>');
                                            $tmpdialog.dialog({
                                                autoOpen: false,
                                                modal: true,
                                                height: (window.innerHeight - 150),
                                                width: '80%',
                                                title: 'URL de la página de la tarjeta de visita virtual - SuperAdmin (Atajo de teclado ALT + v)',
                                                open: function (event, ui) {
                                                    console.log("open popup name=publicvirtualcardmenu, backtopagejsfields=");
                                                },
                                                close: function (event, ui) {
                                                    var returnedid = jQuery("#varforreturndialogidpublicvirtualcardmenu").text();
                                                    var returnedlabel = jQuery("#varforreturndialoglabelpublicvirtualcardmenu").text();
                                                    console.log("popup has been closed. returnedid (js var defined into parent page)=" + returnedid + " returnedlabel=" + returnedlabel);
                                                    if (returnedid != "" && returnedid != "div for returned id") {
                                                        jQuery("#none").val(returnedid);
                                                    }
                                                    if (returnedlabel != "" && returnedlabel != "div for returned label") {
                                                        jQuery("#none").val(returnedlabel);
                                                    }
                                                }
                                            });

                                            $tmpdialog.dialog('open');
                                            return false;
                                        });
                                    });
                                </script>
                            </div>
                            <div class="pull-right">
                                <a accesskey="l" href="https://alixar/user/logout.php?token=620b623c33d2f0005e3bf280288fe1b7" class="button-top-menu-dropdown" title="Desconexi&oacute;n (Atajo de teclado ALT + l)"><i class="fa fa-sign-out-alt padingright"></i><span class="hideonsmartphone">Desconexi&oacute;n</span></a>
                            </div>
                            <div class="clearboth"></div>
                        </div>

                    </div>
                </div>
                <!-- Code to show/hide the user drop-down -->
                <script>
                    function closeTopMenuLoginDropdown() {
                        //console.log("close login dropdown");	// This is call at each click on page, so we disable the log
                        // Hide the menus.
                        jQuery("#topmenu-login-dropdown").removeClass("open");
                    }

                    jQuery(document).ready(function () {
                        jQuery(document).on("click", function (event) {
                            // console.log("Click somewhere on screen");
                            if (!$(event.target).closest("#topmenu-login-dropdown").length) {
                                closeTopMenuLoginDropdown();
                            }
                        });

                        jQuery("#topmenu-login-dropdown .dropdown-toggle").on("click", function (event) {
                            console.log("Click on #topmenu-login-dropdown .dropdown-toggle");
                            event.preventDefault();
                            jQuery("#topmenu-login-dropdown").toggleClass("open");
                        });

                        jQuery("#topmenulogincompanyinfo-btn").on("click", function () {
                            console.log("Click on #topmenulogincompanyinfo-btn");
                            jQuery("#topmenulogincompanyinfo").slideToggle();
                        });

                        jQuery("#topmenuloginmoreinfo-btn").on("click", function () {
                            console.log("Click on #topmenuloginmoreinfo-btn");
                            jQuery("#topmenuloginmoreinfo").slideToggle();
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</header>
<div style="clear: both;"></div>
<!-- End top horizontal menu -->

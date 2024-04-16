<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Dolibarr Development Team">
    <meta name="anti-csrf-newtoken" content="ae9fe71b773b59934fcfc74d3caa2256">
    <meta name="anti-csrf-currenttoken" content="ae9fe71b773b59934fcfc74d3caa2256">
    <link rel="shortcut icon" type="image/x-icon" href="/theme/dolibarr_256x256_color.png"/>
    <link rel="manifest" href="/theme/eldy/manifest.json.php"/>
    <title>Dolibarr - Configuraci&oacute;n de los tipos de miembros</title>
    <!-- Includes CSS for JQuery (Ajax library) -->
    <link rel="stylesheet" type="text/css" href="/includes/jquery/css/base/jquery-ui.css?layout=classic&amp;version=20.0.0-alpha">
    <link rel="stylesheet" type="text/css" href="/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css?layout=classic&amp;version=20.0.0-alpha">
    <link rel="stylesheet" type="text/css" href="/includes/jquery/plugins/select2/dist/css/select2.css?layout=classic&amp;version=20.0.0-alpha">
    <!-- Includes CSS for font awesome -->
    <link rel="stylesheet" type="text/css" href="/theme/common/fontawesome-5/css/all.min.css?layout=classic&amp;version=20.0.0-alpha">
    <!-- Includes CSS for Dolibarr theme -->
    <link rel="stylesheet" type="text/css" href="/theme/eldy/style.css.php?lang=es_ES&amp;theme=eldy&amp;userid=1&amp;entity=1&amp;layout=classic&amp;version=20.0.0-alpha&amp;revision=78">
    <!-- Includes JS for JQuery -->
    <script nonce="fde194ed" src="/includes/jquery/js/jquery.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/jquery/js/jquery-ui.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/jquery/plugins/jnotify/jquery.jnotify.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/jquery/plugins/tablednd/jquery.tablednd.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/nnnick/chartjs/dist/chart.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/jquery/plugins/select2/dist/js/select2.full.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="fde194ed" src="/includes/jquery/plugins/multiselect/jquery.multi-select.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <!-- Includes JS for CKEditor -->
    <script nonce="fde194ed">/* enable ckeditor by main.inc.php */
        var CKEDITOR_BASEPATH = '/includes/ckeditor/ckeditor/';
        var ckeditorConfig = '/theme/eldy/ckeditor/config.js?layout=classic&amp;version=20.0.0-alpha';
        var ckeditorFilebrowserBrowseUrl = '/core/filemanagerdol/browser/default/browser.php?Connector=/core/filemanagerdol/connectors/php/connector.php';
        var ckeditorFilebrowserImageBrowseUrl = '/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector=/core/filemanagerdol/connectors/php/connector.php';
    </script>
    <script src="/includes/ckeditor/ckeditor/ckeditor.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script>CKEDITOR.disableAutoInline = true;
    </script>
    <!-- Includes JS of Dolibarr -->
    <script nonce="fde194ed" src="/core/js/lib_head.js.php?lang=es_ES&amp;layout=classic&amp;version=20.0.0-alpha"></script>
    <link rel="stylesheet" type="text/css" href="/includes/maximebf/debugbar/src/DebugBar/Resources/debugbar.css">
    <link rel="stylesheet" type="text/css" href="/includes/maximebf/debugbar/src/DebugBar/Resources/widgets.css">
    <link rel="stylesheet" type="text/css" href="/includes/maximebf/debugbar/src/DebugBar/Resources/openhandler.css">
    <link rel="stylesheet" type="text/css" href="/includes/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.css">
    <script type="text/javascript" src="/includes/maximebf/debugbar/src/DebugBar/Resources/debugbar.js"></script>
    <script type="text/javascript" src="/includes/maximebf/debugbar/src/DebugBar/Resources/widgets.js"></script>
    <script type="text/javascript" src="/includes/maximebf/debugbar/src/DebugBar/Resources/openhandler.js"></script>
    <script type="text/javascript" src="/includes/maximebf/debugbar/src/DebugBar/Resources/widgets/sqlqueries/widget.js"></script>
    <script type="text/javascript" src="/debugbar/js/widgets.js"></script>

</head>

<body id="mainbody" class="sidebar-collapse">

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
                <li class="tmenu" id="mainmenutd_home">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/index.php?mainmenu=home&amp;leftmenu=home" title="Inicio">
                            <div class="mainmenu home topmenuimage"><span class="fas fa-home fa-fw"></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_home" href="/index.php?mainmenu=home&amp;leftmenu=home" title="Inicio"><span class="mainmenuaspan">Inicio</span></a>
                    </div>
                </li>
                <li class="tmenusel" id="mainmenutd_members">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenusel" tabindex="-1" href="/adherents/index.php?mainmenu=members&amp;leftmenu=" title="Miembros">
                            <div class="mainmenu members topmenuimage">
                                <span class="fas fa-user-alt  em092 infobox-adherent fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenusel" id="mainmenua_members" href="/adherents/index.php?mainmenu=members&amp;leftmenu=" title="Miembros"><span class="mainmenuaspan">Miembros</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_companies">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/societe/index.php?mainmenu=companies&amp;leftmenu=" title="Terceros">
                            <div class="mainmenu companies topmenuimage">
                                <span class="fas fa-building fa-fw pictofixedwidth" style=" color: #6c6aa8;"></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_companies" href="/societe/index.php?mainmenu=companies&amp;leftmenu=" title="Terceros"><span class="mainmenuaspan">Terceros</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_products">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/product/index.php?mainmenu=products&amp;leftmenu=" title="Productos | Servicios">
                            <div class="mainmenu products topmenuimage">
                                <span class="fas fa-cube fa-fw pictofixedwidth" style=" color: #a69944;"></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_products" href="/product/index.php?mainmenu=products&amp;leftmenu=" title="Productos | Servicios"><span class="mainmenuaspan">Productos | Servicios</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_mrp">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/mrp/index.php?mainmenu=mrp&amp;leftmenu=" title="MRP">
                            <div class="mainmenu mrp topmenuimage">
                                <span class="fas fa-cubes fa-fw pictofixedwidth" style=" color: #a69944;"></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_mrp" href="/mrp/index.php?mainmenu=mrp&amp;leftmenu=" title="MRP"><span class="mainmenuaspan">MRP</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_project">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/projet/index.php?mainmenu=project&amp;leftmenu=" title="Proyectos">
                            <div class="mainmenu project topmenuimage">
                                <span class="fas fa-project-diagram  em088 infobox-project fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_project" href="/projet/index.php?mainmenu=project&amp;leftmenu=" title="Proyectos"><span class="mainmenuaspan">Proyectos</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_commercial">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/comm/index.php?mainmenu=commercial&amp;leftmenu=" title="Comercial">
                            <div class="mainmenu commercial topmenuimage">
                                <span class="fas fa-suitcase  em092 infobox-contrat fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_commercial" href="/comm/index.php?mainmenu=commercial&amp;leftmenu=" title="Comercial"><span class="mainmenuaspan">Comercial</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_billing">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/compta/index.php?mainmenu=billing&amp;leftmenu=" title="Financiera">
                            <div class="mainmenu billing topmenuimage">
                                <span class="fas fa-file-invoice-dollar infobox-commande fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_billing" href="/compta/index.php?mainmenu=billing&amp;leftmenu=" title="Financiera"><span class="mainmenuaspan">Financiera</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_bank">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/compta/bank/list.php?mainmenu=bank&amp;leftmenu=" title="Bancos | Cajas">
                            <div class="mainmenu bank topmenuimage">
                                <span class="fas fa-university infobox-bank_account fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_bank" href="/compta/bank/list.php?mainmenu=bank&amp;leftmenu=" title="Bancos | Cajas"><span class="mainmenuaspan">Bancos | Cajas</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_accountancy">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/accountancy/index.php?mainmenu=accountancy&amp;leftmenu=" title="Contabilidad">
                            <div class="mainmenu accountancy topmenuimage">
                                <span class="fas fa-search-dollar infobox-bank_account fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_accountancy" href="/accountancy/index.php?mainmenu=accountancy&amp;leftmenu=" title="Contabilidad"><span class="mainmenuaspan">Contabilidad</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_hrm">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/hrm/index.php?mainmenu=hrm&amp;leftmenu=" title="RRHH">
                            <div class="mainmenu hrm topmenuimage">
                                <span class="fas fa-user-tie infobox-adherent fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_hrm" href="/hrm/index.php?mainmenu=hrm&amp;leftmenu=" title="RRHH"><span class="mainmenuaspan">RRHH</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_ecm">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/ecm/index.php?idmenu=47&mainmenu=ecm&leftmenu=" title="Documentos">
                            <div class="mainmenu ecm topmenuimage">
                                <span class="fas fa-folder-open pictofixedwidth" style=""></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_ecm" href="/ecm/index.php?idmenu=47&mainmenu=ecm&leftmenu=" title="Documentos"><span class="mainmenuaspan">Documentos</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_agenda">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/comm/action/index.php?idmenu=12&mainmenu=agenda&leftmenu=" title="Agenda">
                            <div class="mainmenu agenda topmenuimage">
                                <span class="fas fa-calendar-alt infobox-action pictofixedwidth" style=""></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_agenda" href="/comm/action/index.php?idmenu=12&mainmenu=agenda&leftmenu=" title="Agenda"><span class="mainmenuaspan">Agenda</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_ticket">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/ticket/index.php?mainmenu=ticket&amp;leftmenu=" title="Tickets">
                            <div class="mainmenu ticket topmenuimage">
                                <span class="fas fa-ticket-alt infobox-contrat fa-fw pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_ticket" href="/ticket/index.php?mainmenu=ticket&amp;leftmenu=" title="Tickets"><span class="mainmenuaspan">Tickets</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_tools">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/core/tools.php?mainmenu=tools&amp;leftmenu=" title="Utilidades">
                            <div class="mainmenu tools topmenuimage">
                                <span class="fas fa-tools fa-fw pictofixedwidth" style=""></span></div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_tools" href="/core/tools.php?mainmenu=tools&amp;leftmenu=" title="Utilidades"><span class="mainmenuaspan">Utilidades</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_website">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/website/index.php?idmenu=60&mainmenu=website&leftmenu=" title="Sitios web">
                            <div class="mainmenu website topmenuimage">
                                <span class="fas fa-globe-americas pictofixedwidth em092" style=" color: #304;"></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_website" href="/website/index.php?idmenu=60&mainmenu=website&leftmenu=" title="Sitios web"><span class="mainmenuaspan">Sitios web</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_externalsite">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/externalsite/frames.php?idmenu=64&mainmenu=externalsite&leftmenu=" title="ExternalSite">
                            <div class="mainmenu externalsite topmenuimage">
                                <span class="fas fa-globe-americas pictofixedwidth em092" style=" color: #304;"></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_externalsite" href="/externalsite/frames.php?idmenu=64&mainmenu=externalsite&leftmenu=" title="ExternalSite"><span class="mainmenuaspan">ExternalSite</span></a>
                    </div>
                </li>
                <li class="tmenu" id="mainmenutd_takepos">
                    <div class="tmenucenter">
                        <a class="tmenuimage tmenu" tabindex="-1" href="/takepos/index.php?idmenu=58&mainmenu=takepos&leftmenu=" target="takepos" title="TPV">
                            <div class="mainmenu takepos topmenuimage">
                                <span class="fas fa-cash-register infobox-bank_account pictofixedwidth" style=""></span>
                            </div>
                        </a><a class="tmenulabel tmenu" id="mainmenua_takepos" href="/takepos/index.php?idmenu=58&mainmenu=takepos&leftmenu=" target="takepos" title="TPV"><span class="mainmenuaspan">TPV</span></a>
                    </div>
                </li>
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
                    <a href="/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="modulebuilder"><span class="fa fa-bug atoplogin valignmiddle"></span></a>
                </div>
            </div>
            <div class="inline-block">
                <div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Mostrar p&aacute;gina de impresi&oacute;n de la zona central">
                    <a href="/adherents/type.php?optioncss=print" target="_blank" rel="noopener noreferrer"><span class="fa fa-print atoplogin valignmiddle"></span></a>
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
                    <a accesskey="l" href="/user/logout.php?token=ae9fe71b773b59934fcfc74d3caa2256"><span class="fas fa-sign-out-alt atoplogin valignmiddle" style="" title="Desconexi&oacute;n (Atajo de teclado ALT + l)"></span></a>
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
                                <input type="hidden" name="token" value="ae9fe71b773b59934fcfc74d3caa2256"><input name="bookmark" id="top-bookmark-search-input" class="dropdown-search-input" placeholder="Marcadores" autocomplete="off">
                            </form>
                        </div>

                        <!-- Menu bookmark tools-->
                        <div class="bookmark-footer">
                            <a class="top-menu-dropdown-link" title="A&ntilde;adir esta p&aacute;gina a los marcadores" href="/bookmarks/card.php?action=create&amp;url=%2Fadherents%2Ftype.php%3Fsortfield%3Dd.lastname%26sortorder%3DDESC"><span class="fas fa-plus-circle paddingright" style=""></span>A&ntilde;adir
                                esta p&aacute;gina a los
                                marcadores</a><a class="top-menu-dropdown-link" title="Marcadores" href="/bookmarks/list.php"><span class="fas fa-pencil-alt paddingright opacitymedium" style=" color: #444;"></span>Listar/editar
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
                    <a href="/user/card.php?id=1" class="dropdown-toggle login-dropdown-a" data-toggle="dropdown">
                        <img class="photo photouserphoto userphoto" alt="" src="/public/theme/common/user_anonymous.png"><span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone paddingleft">rsanjose</span>
                    </a>
                    <div class="dropdown-menu">
                        <!-- User image -->
                        <div class="user-header">
                            <img class="photo dropdown-user-image" alt="" src="/public/theme/common/user_anonymous.png">
                            <p>
                                <i class="far fa-star classfortooltip" title="Administrador de sistema"></i> SuperAdmin
                                (rsanjose)<br><small class="classfortooltip" title="Conectado desde : 16/04/2024 11:02&lt;br&gt;Conexi&oacute;n anterior : 16/04/2024 10:59"><i class="fa fa-user-clock"></i>
                                    16/04/2024
                                    11:02</small><br><small class="classfortooltip" title="Conectado desde : 16/04/2024 11:02&lt;br&gt;Conexi&oacute;n anterior : 16/04/2024 10:59"><i class="fa fa-user-clock opacitymedium"></i>
                                    16/04/2024 10:59</small><br>
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
                                    autentificaci&oacute;n:</b> dolibarr<br><b>Conectado desde:</b> 16/04/2024 11:02<br><b>Conexi&oacute;n
                                    anterior:</b> 16/04/2024 10:59<br><b>Tema actual:</b> eldy<br><b>Gestor men&uacute;
                                    actual:</b> eldy<br><b>Idioma actual:</b>
                                <span class="flag-sprite es" title="es_ES"></span> es_ES<br><b>Zona horaria cliente
                                    (usuario):</b> +2 (Europe/Madrid)<br><b>Navegador:</b> chrome 123.0.0.0
                                <small class="opacitymedium">(Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML,
                                    like Gecko) Chrome/123.0.0.0 Safari/537.36)</small><br><b>Presentaci&oacute;n:</b>
                                classic<br><b>Pantalla:</b> 1920 x 922
                            </div>
                        </div>

                        <!-- Menu Footer-->
                        <div class="user-footer">
                            <div class="pull-left">
                                <a accesskey="u" href="/user/card.php?id=1" class="button-top-menu-dropdown" title="Su ficha de usuario (Atajo de teclado ALT + u)"><i class="fa fa-user"></i>
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
                                <script nonce="fde194ed" type="text/javascript">
                                    jQuery(document).ready(function () {
                                        jQuery(".button_publicvirtualcardmenu").click(function () {
                                            console.log('Open popup with jQuery(...).dialog() on URL /user/virtualcard.php?id=1&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=publicvirtualcardmenu');
                                            var $tmpdialog = $('#idfordialogpublicvirtualcardmenu');
                                            $tmpdialog.html('<iframe class="iframedialog" id="iframedialogpublicvirtualcardmenu" style="border: 0px;" src="/user/virtualcard.php?id=1&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=publicvirtualcardmenu" width="100%" height="98%"></iframe>');
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
                                <a accesskey="l" href="/user/logout.php?token=ae9fe71b773b59934fcfc74d3caa2256" class="button-top-menu-dropdown" title="Desconexi&oacute;n (Atajo de teclado ALT + l)"><i class="fa fa-sign-out-alt padingright"></i><span class="hideonsmartphone">Desconexi&oacute;n</span></a>
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
<div style="clear: both;"></div><!-- End top horizontal menu -->

<!-- Begin div id-container -->
<div id="id-container" class="id-container">
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
                        <a class="vmenu" title="Miembros" href="/adherents/index.php?leftmenu=members&amp;mainmenu=members"><span class="fas fa-user-alt  em092 infobox-adherent paddingright pictofixedwidth" style=""></span>Miembros</a>
                    </div>
                    <div class="menu_top"></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_card">
                        <a class="vsmenu" title="Nuevo miembro" href="/adherents/card.php?leftmenu=members&amp;action=create">Nuevo
                            miembro</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        <a class="vsmenu" title="Listado" href="/adherents/list.php?leftmenu=members">Listado</a><br>
                    </div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros borrador" href="/adherents/list.php?leftmenu=members&amp;statut=-1">Miembros
                            borrador</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros validados" href="/adherents/list.php?leftmenu=members&amp;statut=1">Miembros
                            validados</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Membres&iacute;a pendiente" href="/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=waitingsubscription">Membres&iacute;a
                            pendiente</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="A hoy" href="/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=uptodate">A
                            hoy</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=3 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Fuera de plazo" href="/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=outofdate">Fuera
                            de plazo</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros de baja" href="/adherents/list.php?leftmenu=members&amp;statut=0">Miembros
                            de baja</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=2 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        &nbsp;&nbsp;&nbsp;<a class="vsmenu" title="Miembros excluidos" href="/adherents/list.php?leftmenu=members&amp;statut=-2">Miembros
                            excluidos</a><br></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_stats_index">
                        <a class="vsmenu" title="Estad&iacute;sticas" href="/adherents/stats/index.php?leftmenu=members">Estad&iacute;sticas</a><br>
                    </div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_cartes_carte">
                        <a class="vsmenu" title="Generaci&oacute;n de tarjetas para socios" href="/adherents/cartes/carte.php?leftmenu=export">Generaci&oacute;n
                            de tarjetas para socios</a><br></div>
                    <!-- Process menu entry with mainmenu=members, leftmenu=cat, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_categories_index">
                        <a class="vsmenu" title="Etiquetas/Categor&iacute;as" href="/categories/index.php?leftmenu=cat&amp;type=3">Etiquetas/Categor&iacute;as</a><br>
                    </div>
                    <div class="menu_end"></div>
                </div>
                <div class="blockvmenu blockvmenuimpair">
                    <!-- Process menu entry with mainmenu=members, leftmenu=members, level=0 enabled=1, position=0 -->
                    <div class="menu_titre">
                        <a class="vmenu" title="Afiliaciones" href="/adherents/index.php?leftmenu=members&amp;mainmenu=members"><span class="fas fa-money-check-alt  em080 infobox-bank_account paddingright pictofixedwidth" style=""></span>Afiliaciones</a>
                    </div>
                    <div class="menu_top"></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_list">
                        <a class="vsmenu" title="Registro" href="/adherents/list.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members">Registro</a><br>
                    </div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_subscription_list">
                        <a class="vsmenu" title="Listado" href="/adherents/subscription/list.php?leftmenu=members">Listado</a><br>
                    </div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_stats_index">
                        <a class="vsmenu" title="Estad&iacute;sticas" href="/adherents/stats/index.php?leftmenu=members">Estad&iacute;sticas</a><br>
                    </div>
                    <div class="menu_end"></div>
                </div>
                <div class="blockvmenu blockvmenupair blockvmenulast">
                    <!-- Process menu entry with mainmenu=members, leftmenu=setup, level=0 enabled=1, position=0 -->
                    <div class="menu_titre">
                        <a class="vmenu" title="Tipos de miembros" href="/adherents/type.php?leftmenu=setup&amp;mainmenu=members"><span class="fas fa-user-friends  em092 infobox-adherent paddingright pictofixedwidth" style=""></span>Tipos
                            de miembros</a></div>
                    <div class="menu_top"></div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_type">
                        <a class="vsmenu" title="Nuevo" href="/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create">Nuevo</a><br>
                    </div>
                    <!-- Process menu entry with mainmenu=, leftmenu=, level=1 enabled=1, position=0 -->
                    <div class="menu_contenu menu_contenu_adherents_type">
                        <a class="vsmenu" title="Listado" href="/adherents/type.php?leftmenu=setup&amp;mainmenu=members">Listado</a><br>
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
    <!-- Begin right area -->
    <div id="id-right">
        <!-- Begin div class="fiche" -->
        <div class="fiche">
            <form method="POST" action="/adherents/type.php">
                <input type="hidden" name="token" value="ae9fe71b773b59934fcfc74d3caa2256"><input type="hidden" name="formfilteraction" id="formfilteraction" value="list"><input type="hidden" name="action" value="list"><input type="hidden" name="sortfield" value="d.lastname"><input type="hidden" name="sortorder" value="DESC"><input type="hidden" name="mode" value="">
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
                                        <a class="btnTitle reposition btnTitleSelected" href="/adherents/type.php?mode=common&amp;contextpage=adherentstype" title="Vista de listado"><span class="fa fa-bars imgforviewmode valignmiddle btnTitle-icon"></span></a><a class="btnTitle reposition" href="/adherents/type.php?mode=kanban&amp;contextpage=adherentstype" title="Vista Kanban"><span class="fa fa-th-list imgforviewmode valignmiddle btnTitle-icon"></span></a><span class="button-title-separator "></span><a class="btnTitle btnTitlePlus" href="/adherents/type.php?action=create" title="Nuevo tipo de miembro"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>
                                    </li>
                                </ul>
                            </div>
                            <script nonce="fde194ed">
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
                                <a href="/adherents/type.php?rowid=1" title="&lt;span class=&quot;fas fa-user-friends  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Tipo de miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;Activo&quot;&gt;Activo&lt;/span&gt;&lt;br&gt;Etiqueta: 1&lt;br&gt;Sujeto a cotizaci&oacute;n: S&iacute;" class="classfortooltip"><span class="fas fa-user-friends  em092 infobox-adherent paddingright" style=""></span>1</a>
                            </td>
                            <td>Est&aacute;ndar</td>
                            <td class="center">Corporaci&oacute;n e Individuo</td>
                            <td class="center nowrap">1 A&ntilde;o</td>
                            <td class="center">S&iacute;</td>
                            <td class="center"><span class="amount">0,00</span></td>
                            <td class="center">No</td>
                            <td class="center">S&iacute;</td>
                            <td class="center">
                                <span class="badge  badge-status4 badge-status" title="Activo">Activo</span></td>
                            <td class="right">
                                <a class="editfielda" href="/adherents/type.php?action=edit&rowid=1"><span class="fas fa-pencil-alt" style=" color: #444;" title="Modificar"></span></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </form>

        </div> <!-- End div class="fiche" -->
    </div> <!-- End div id-right -->
</div> <!-- End div id-container -->


<!-- Common footer for private page -->

<!-- A div to store page_y POST parameter -->
<div id="page_y" style="display: none;"></div>


<!-- A script section to add menuhider handler on backoffice, manage focus and mandatory fields, tuning info, ... -->
<script>
    jQuery(document).ready(function () {

        /* JS CODE TO ENABLE to manage handler to switch left menu page (menuhider) */
        jQuery("li.menuhider").click(function (event) {
            if (!$("body").hasClass("sidebar-collapse")) {
                event.preventDefault();
            }
            console.log("We click on .menuhider");
            $("body").toggleClass("sidebar-collapse")
        });
    });

</script>
<!-- Output debugbar data -->
<script type="text/javascript">
    var phpdebugbar = new PhpDebugBar.DebugBar();
    phpdebugbar.addTab("Variables", new PhpDebugBar.DebugBar.Tab({
        "icon": "tags",
        "title": "Variables",
        "widget": new PhpDebugBar.Widgets.VariableListWidget()
    }));
    phpdebugbar.addIndicator("time", new PhpDebugBar.DebugBar.Indicator({
        "icon": "clock-o",
        "tooltip": "Duraci\u00f3n de la solicitud"
    }), "right");
    phpdebugbar.addTab("Timeline", new PhpDebugBar.DebugBar.Tab({
        "icon": "tasks",
        "title": "Timeline",
        "widget": new PhpDebugBar.Widgets.TimelineWidget()
    }));
    phpdebugbar.addTab("Error handler", new PhpDebugBar.DebugBar.Tab({
        "icon": "list",
        "title": "Error handler",
        "widget": new PhpDebugBar.Widgets.MessagesWidget()
    }));
    phpdebugbar.addIndicator("memory", new PhpDebugBar.DebugBar.Indicator({
        "icon": "cogs",
        "tooltip": "Uso de memoria"
    }), "right");
    phpdebugbar.addTab("Database", new PhpDebugBar.DebugBar.Tab({
        "icon": "arrow-right",
        "title": "Database",
        "widget": new PhpDebugBar.Widgets.SQLQueriesWidget()
    }));
    phpdebugbar.addIndicator("database_info", new PhpDebugBar.DebugBar.TooltipIndicator({
        "icon": "database",
        "tooltip": {
            "html": "Host: <strong>localhost<\/strong><br>Port: <strong>3306<\/strong><br>Nombre: <strong>dolibarr<\/strong><br>Usuario: <strong>root<\/strong><br>Tipo: <strong>mysqli<\/strong><br>Prefijo: <strong>llx_<\/strong><br>Charset: <strong>utf8<\/strong>",
            "class": "tooltip-wide"
        }
    }), "right");
    phpdebugbar.addIndicator("dolibarr_info", new PhpDebugBar.DebugBar.TooltipIndicator({
        "icon": "desktop",
        "tooltip": {
            "html": "Version: <strong>20.0.0-alpha<\/strong><br>Theme: <strong>eldy<\/strong><br>Locale: <strong>es_ES<\/strong><br>Divisa: <strong>EUR<\/strong><br>Entidad: <strong>1<\/strong><br>MaxSizeList: <strong>20<\/strong><br>MaxSizeForUploadedFiles: <strong>2048<\/strong><br>$dolibarr_main_prod = <strong>0<\/strong><br>$dolibarr_nocsrfcheck = <strong>0<\/strong><br>MAIN_SECURITY_CSRF_WITH_TOKEN = <strong>2<\/strong><br>MAIN_FEATURES_LEVEL = <strong>0<\/strong><br>",
            "class": "tooltip-wide"
        }
    }), "right");
    phpdebugbar.addIndicator("mail_info", new PhpDebugBar.DebugBar.TooltipIndicator({
        "icon": "envelope",
        "tooltip": {
            "html": "M&eacute;todo: <strong><\/strong><br>Server: <strong><\/strong><br>Port: <strong><\/strong><br>ID: <strong><\/strong><br>Pwd: <strong><\/strong><br>TLS\/STARTTLS: <strong><\/strong> \/ <strong><\/strong><br>MAIN_DISABLE_ALL_MAILS: <strong>No<\/strong><br>dolibarr_mailing_limit_sendbyweb = <strong>0<\/strong><br>dolibarr_mailing_limit_sendbycli = <strong>0<\/strong><br>dolibarr_mailing_limit_sendbyday = <strong>0<\/strong><br>",
            "class": "tooltip-extra-wide"
        }
    }), "right");
    phpdebugbar.addTab("Logs", new PhpDebugBar.DebugBar.Tab({
        "icon": "list-alt",
        "title": "Logs",
        "widget": new PhpDebugBar.Widgets.MessagesWidget()
    }));
    phpdebugbar.setDataMap({
        "Variables": ["request", {}],
        "time": ["time.duration_str", '0ms'],
        "Timeline": ["time", {}],
        "Error handler": ["Error handler.messages", []],
        "Error handler:badge": ["Error handler.count", null],
        "memory": ["memory.peak_usage_str", '0B'],
        "Database": ["query", []],
        "Database:badge": ["query.nb_statements", 0],
        "database_info": ["",],
        "dolibarr_info": ["",],
        "mail_info": ["",],
        "Logs": ["logs.messages", []],
        "Logs:badge": ["logs.count", null]
    });
    phpdebugbar.restoreState();
    phpdebugbar.ajaxHandler = new PhpDebugBar.AjaxHandler(phpdebugbar, undefined, true);
    if (jQuery) phpdebugbar.ajaxHandler.bindToJquery(jQuery);
    phpdebugbar.addDataSet({
        "__meta": {
            "id": "Xd1fb3b85467b913628519667a8a33f4f",
            "datetime": "2024-04-16 09:08:46",
            "utime": 1713258526.138461,
            "method": "GET",
            "uri": "\/adherents\/type.php",
            "ip": "127.0.0.1"
        },
        "request": {
            "$_GET": "[]",
            "$_POST": "[]",
            "$_SESSION": "array:21 [\n  \"newtoken\" => \"ae9fe71b773b59934fcfc74d3caa2256\"\n  \"dol_loginmesg\" => \"\"\n  \"idmenu\" => \"\"\n  \"token\" => \"ae9fe71b773b59934fcfc74d3caa2256\"\n  \"dol_login\" => \"rsanjose\"\n  \"dol_logindate\" => 1713258121\n  \"dol_authmode\" => \"dolibarr\"\n  \"dol_tz\" => \"1\"\n  \"dol_tz_string\" => \"Europe\/Madrid\"\n  \"dol_dst\" => 1\n  \"dol_dst_observed\" => 1\n  \"dol_dst_first\" => \"2024-03-31T01:59:00Z\"\n  \"dol_dst_second\" => \"2024-10-27T02:59:00Z\"\n  \"dol_screenwidth\" => \"1920\"\n  \"dol_screenheight\" => \"922\"\n  \"dol_company\" => \"rSanjoSEO\"\n  \"dol_entity\" => 1\n  \"mainmenu\" => \"members\"\n  \"leftmenuopened\" => \"setup\"\n  \"PHPDEBUGBAR_STACK_DATA\" => []\n  \"leftmenu\" => \"setup\"\n]",
            "$_COOKIE": "array:5 [\n  \"DOLSESSID_2811f5c4ae891578bbae4f4c3a606fd56687557f\" => \"*****hidden*****\"\n  \"PHPSESSID\" => \"lddcrmjtre1hn53f6ddj3n6lue\"\n  \"DOLINSTALLNOPING_b4db654357c08b2e57cfea5a5311a9742ab9ae77c24c11a52c61b46a189664c4\" => \"0\"\n  \"DOLSESSID_85f3df31515619e11ebbf28fced3174cb49cd80c\" => \"*****hidden*****\"\n  \"DOLUSERCOOKIE_boxfilter_task\" => \"all\"\n]",
            "$_SERVER": "array:44 [\n  \"USER\" => \"http\"\n  \"HOME\" => \"\/srv\/http\"\n  \"SCRIPT_NAME\" => \"\/adherents\/type.php\"\n  \"REQUEST_URI\" => \"\/adherents\/type.php\"\n  \"QUERY_STRING\" => \"\"\n  \"REQUEST_METHOD\" => \"GET\"\n  \"SERVER_PROTOCOL\" => \"HTTP\/1.1\"\n  \"GATEWAY_INTERFACE\" => \"CGI\/1.1\"\n  \"REMOTE_PORT\" => \"39222\"\n  \"SCRIPT_FILENAME\" => \"\/srv\/http\/dolibarr\/htdocs\/adherents\/type.php\"\n  \"SERVER_ADMIN\" => \"rsanjose@alxarafe.com\"\n  \"CONTEXT_DOCUMENT_ROOT\" => \"\/srv\/http\/dolibarr\/htdocs\"\n  \"CONTEXT_PREFIX\" => \"\"\n  \"REQUEST_SCHEME\" => \"https\"\n  \"DOCUMENT_ROOT\" => \"\/srv\/http\/dolibarr\/htdocs\"\n  \"REMOTE_ADDR\" => \"127.0.0.1\"\n  \"SERVER_PORT\" => \"443\"\n  \"SERVER_ADDR\" => \"127.0.0.1\"\n  \"SERVER_NAME\" => \"dolibarr\"\n  \"SERVER_SOFTWARE\" => \"Apache\/2.4.58 (Unix) OpenSSL\/3.2.1 PHP\/7.4.33\"\n  \"SERVER_SIGNATURE\" => \"\"\n  \"PATH\" => \"\/usr\/local\/sbin:\/usr\/local\/bin:\/usr\/bin:\/var\/lib\/snapd\/snap\/bin\"\n  \"HTTP_COOKIE\" => \"DOLSESSID_2811f5c4ae891578bbae4f4c3a606fd56687557f=dqaq43cr1iing8flujii8nbins; PHPSESSID=lddcrmjtre1hn53f6ddj3n6lue; DOLINSTALLNOPING_b4db654357c08b2e57cfea5a5311a9742ab9ae77c24c11a52c61b46a189664c4=0; DOLSESSID_85f3df31515619e11ebbf28fced3174cb49cd80c=fpft622m031c740thc55sk2gu6; DOLUSERCOOKIE_boxfilter_task=all\"\n  \"HTTP_ACCEPT_LANGUAGE\" => \"es-ES,es;q=0.9\"\n  \"HTTP_ACCEPT_ENCODING\" => \"gzip, deflate, br, zstd\"\n  \"HTTP_SEC_FETCH_DEST\" => \"document\"\n  \"HTTP_SEC_FETCH_USER\" => \"?1\"\n  \"HTTP_SEC_FETCH_MODE\" => \"navigate\"\n  \"HTTP_SEC_FETCH_SITE\" => \"none\"\n  \"HTTP_ACCEPT\" => \"text\/html,application\/xhtml+xml,application\/xml;q=0.9,image\/avif,image\/webp,image\/apng,*\/*;q=0.8,application\/signed-exchange;v=b3;q=0.7\"\n  \"HTTP_USER_AGENT\" => \"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/123.0.0.0 Safari\/537.36\"\n  \"HTTP_UPGRADE_INSECURE_REQUESTS\" => \"1\"\n  \"HTTP_SEC_CH_UA_PLATFORM\" => \"\"Linux\"\"\n  \"HTTP_SEC_CH_UA_MOBILE\" => \"?0\"\n  \"HTTP_SEC_CH_UA\" => \"\"Chromium\";v=\"123\", \"Not:A-Brand\";v=\"8\"\"\n  \"HTTP_CONNECTION\" => \"keep-alive\"\n  \"HTTP_HOST\" => \"dolibarr\"\n  \"proxy-nokeepalive\" => \"1\"\n  \"SSL_TLS_SNI\" => \"dolibarr\"\n  \"HTTPS\" => \"on\"\n  \"FCGI_ROLE\" => \"RESPONDER\"\n  \"PHP_SELF\" => \"\/adherents\/type.php\"\n  \"REQUEST_TIME_FLOAT\" => 1713258526.0213\n  \"REQUEST_TIME\" => 1713258526\n]"
        },
        "time": {
            "start": 1713258526.021255,
            "end": 1713258526.139854,
            "duration": 0.11859893798828125,
            "duration_str": "119ms",
            "measures": [{
                "label": "Page generation (after environment init)",
                "start": 1713258526.061055,
                "relative_start": 0.03979992866516113,
                "end": 1713258526.136105,
                "relative_end": 1713258526.136105,
                "duration": 0.07505011558532715,
                "duration_str": "75.05ms",
                "params": [],
                "collector": null
            }]
        },
        "Error handler": {"count": 0, "messages": []},
        "memory": {"peak_usage": 20980736, "peak_usage_str": "20980736 Bytes"},
        "query": {
            "nb_statements": 5,
            "nb_failed_statements": 0,
            "accumulated_duration": 0.0022699832916259766,
            "memory_usage": 0,
            "statements": [{
                "sql": "SELECT transkey, transvalue FROM llx_overwrite_trans where (lang='es_ES' OR lang IS NULL) AND entity IN (0, 0,1) ORDER BY lang DESC",
                "duration": 0.00026488304138183594,
                "duration_str": 0.26,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre, m.prefix, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position FROM llx_menu as m WHERE m.entity IN (0,1) AND m.menu_handler IN ('eldy','all') AND m.usertype IN (0,2) ORDER BY m.type DESC, m.position, m.rowid",
                "duration": 0.000453948974609375,
                "duration_str": 0.45,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help, css, cssview, csslist FROM llx_extrafields WHERE elementtype = 'adherent_type' ORDER BY pos",
                "duration": 0.0004971027374267578,
                "duration_str": 0.5,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid, title, url, target FROM llx_bookmark WHERE (fk_user = 1 OR fk_user is NULL OR fk_user = 0) AND entity IN (1) ORDER BY position",
                "duration": 0.00045800209045410156,
                "duration_str": 0.46,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration FROM llx_adherent_type as d WHERE d.entity IN (1)",
                "duration": 0.0005960464477539062,
                "duration_str": 0.6,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }]
        },
        "dolibarr": [],
        "logs": {
            "count": 7,
            "messages": [{
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT DISTINCT r.module, r.perms, r.subperms FROM llx_usergroup_rights as gr, llx_usergroup_user as gu, llx_rights_def as r WHERE r.id = gr.fk_id AND gr.entity = 1 AND gu.entity IN (0,1) AND r.entity = 1 AND gr.fk_usergroup = gu.fk_usergroup AND gu.fk_user = 1 AND r.perms IS NOT NULL",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139947
            }, {
                "message": "2024-04-16 09:08:46 NOTICE --- Access to GET \/adherents\/type.php - action=, massaction=",
                "message_html": null,
                "is_string": false,
                "label": "notice",
                "time": 1713258526.139954
            }, {
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT transkey, transvalue FROM llx_overwrite_trans where (lang='es_ES' OR lang IS NULL) AND entity IN (0, 0,1) ORDER BY lang DESC",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139962
            }, {
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre, m.prefix, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position FROM llx_menu as m WHERE m.entity IN (0,1) AND m.menu_handler IN ('eldy','all') AND m.usertype IN (0,2) ORDER BY m.type DESC, m.position, m.rowid",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139969
            }, {
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help, css, cssview, csslist FROM llx_extrafields WHERE elementtype = 'adherent_type' ORDER BY pos",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139977
            }, {
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT rowid, title, url, target FROM llx_bookmark WHERE (fk_user = 1 OR fk_user is NULL OR fk_user = 0) AND entity IN (1) ORDER BY position",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139984
            }, {
                "message": "2024-04-16 09:08:46 DEBUG sql=SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration FROM llx_adherent_type as d WHERE d.entity IN (1)",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713258526.139991
            }]
        }
    }, "Xd1fb3b85467b913628519667a8a33f4f");

</script>

<!-- JS CODE TO ENABLE select2 for id searchselectcombo -->
<script nonce="fde194ed">
    $(document).ready(function () {
        var data = [{
            "id": "searchintomember",
            "text": "<span class=\"fas fa-user-alt  em092 infobox-adherent pictofixedwidth\" style=\"\"><\/span> Miembros",
            "url": "\/adherents\/list.php"
        }, {
            "id": "searchintothirdparty",
            "text": "<span class=\"fas fa-building pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Terceros",
            "url": "\/societe\/list.php"
        }, {
            "id": "searchintocontact",
            "text": "<span class=\"fas fa-address-book pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Contactos",
            "url": "\/contact\/list.php"
        }, {
            "id": "searchintoproduct",
            "text": "<span class=\"fas fa-cube pictofixedwidth\" style=\" color: #a69944;\"><\/span> Productos o servicios",
            "url": "\/product\/list.php"
        }, {
            "id": "searchintobatch",
            "text": "<span class=\"fas fa-barcode pictofixedwidth\" style=\" color: #a69944;\"><\/span> Lotes \/ Series",
            "url": "\/product\/stock\/productlot_list.php"
        }, {
            "id": "searchintomo",
            "text": "<span class=\"fas fa-cubes pictofixedwidth\" style=\" color: #a69944;\"><\/span> &Oacute;rdenes de fabricaci&oacute;n",
            "url": "\/mrp\/mo_list.php"
        }, {
            "id": "searchintoprojects",
            "text": "<span class=\"fas fa-project-diagram  em088 infobox-project pictofixedwidth\" style=\"\"><\/span> Proyectos",
            "url": "\/projet\/list.php"
        }, {
            "id": "searchintotasks",
            "text": "<span class=\"fas fa-tasks infobox-project pictofixedwidth\" style=\"\"><\/span> Tareas",
            "url": "\/projet\/tasks\/list.php"
        }, {
            "id": "searchintopropal",
            "text": "<span class=\"fas fa-file-signature infobox-propal pictofixedwidth\" style=\"\"><\/span> Presupuestos",
            "url": "\/comm\/propal\/list.php"
        }, {
            "id": "searchintoorder",
            "text": "<span class=\"fas fa-file-invoice infobox-commande pictofixedwidth\" style=\"\"><\/span> Pedidos",
            "url": "\/commande\/list.php"
        }, {
            "id": "searchintoshipment",
            "text": "<span class=\"fas fa-dolly  em092 infobox-commande pictofixedwidth\" style=\"\"><\/span> Env&iacute;os a clientes",
            "url": "\/expedition\/list.php"
        }, {
            "id": "searchintoinvoice",
            "text": "<span class=\"fas fa-file-invoice-dollar infobox-commande pictofixedwidth\" style=\"\"><\/span> Facturas a clientes",
            "url": "\/compta\/facture\/list.php"
        }, {
            "id": "searchintosupplierpropal",
            "text": "<span class=\"fas fa-file-signature infobox-supplier_proposal pictofixedwidth\" style=\"\"><\/span> Presupuestos de proveedor",
            "url": "\/supplier_proposal\/list.php"
        }, {
            "id": "searchintosupplierorder",
            "text": "<span class=\"fas fa-dol-order_supplier infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Pedidos a proveedor",
            "url": "\/fourn\/commande\/list.php"
        }, {
            "id": "searchintosupplierinvoice",
            "text": "<span class=\"fas fa-file-invoice-dollar infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Facturas proveedor",
            "url": "\/fourn\/facture\/list.php"
        }, {
            "id": "searchintocontract",
            "text": "<span class=\"fas fa-suitcase  em092 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Contratos",
            "url": "\/contrat\/list.php"
        }, {
            "id": "searchintointervention",
            "text": "<span class=\"fas fa-ambulance  em080 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Intervenciones",
            "url": "\/fichinter\/list.php"
        }, {
            "id": "searchintoknowledgemanagement",
            "text": "<span class=\"fas fa-ticket-alt infobox-contrat rotate90 pictofixedwidth\" style=\"\"><\/span> Base de Conocimientos",
            "url": "\/knowledgemanagement\/knowledgerecord_list.php?mainmenu=ticket"
        }, {
            "id": "searchintotickets",
            "text": "<span class=\"fas fa-ticket-alt infobox-contrat pictofixedwidth\" style=\"\"><\/span> Tickets",
            "url": "\/ticket\/list.php?mainmenu=ticket"
        }, {
            "id": "searchintocustomerpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos de clientes",
            "url": "\/compta\/paiement\/list.php?leftmenu=customers_bills_payment"
        }, {
            "id": "searchintovendorpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos a proveedor",
            "url": "\/fourn\/paiement\/list.php?leftmenu=suppliers_bills_payment"
        }, {
            "id": "searchintomiscpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos varios",
            "url": "\/compta\/bank\/various_payment\/list.php?leftmenu=tax_various"
        }, {
            "id": "searchintouser",
            "text": "<span class=\"fas fa-user infobox-adherent pictofixedwidth\" style=\"\"><\/span> Usuarios",
            "url": "\/user\/list.php"
        }, {
            "id": "searchintoexpensereport",
            "text": "<span class=\"fas fa-wallet infobox-expensereport pictofixedwidth\" style=\"\"><\/span> Informes de gastos",
            "url": "\/expensereport\/list.php?mainmenu=hrm"
        }, {
            "id": "searchintoleaves",
            "text": "<span class=\"fas fa-umbrella-beach  em088 infobox-holiday pictofixedwidth\" style=\"\"><\/span> D&iacute;a libre",
            "url": "\/holiday\/list.php?mainmenu=hrm"
        }];

        var saveRemoteData = {
            "searchintomember": {
                "position": 8,
                "shortcut": "M",
                "img": "object_member",
                "label": "Miembros",
                "text": "<span class=\"fas fa-user-alt  em092 infobox-adherent pictofixedwidth\" style=\"\"><\/span> Miembros",
                "url": "\/adherents\/list.php"
            },
            "searchintothirdparty": {
                "position": 10,
                "shortcut": "T",
                "img": "object_company",
                "label": "Terceros",
                "text": "<span class=\"fas fa-building pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Terceros",
                "url": "\/societe\/list.php"
            },
            "searchintocontact": {
                "position": 15,
                "shortcut": "A",
                "img": "object_contact",
                "label": "Contactos",
                "text": "<span class=\"fas fa-address-book pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Contactos",
                "url": "\/contact\/list.php"
            },
            "searchintoproduct": {
                "position": 30,
                "shortcut": "P",
                "img": "object_product",
                "label": "Productos o servicios",
                "text": "<span class=\"fas fa-cube pictofixedwidth\" style=\" color: #a69944;\"><\/span> Productos o servicios",
                "url": "\/product\/list.php"
            },
            "searchintobatch": {
                "position": 32,
                "shortcut": "B",
                "img": "object_lot",
                "label": "Lotes \/ Series",
                "text": "<span class=\"fas fa-barcode pictofixedwidth\" style=\" color: #a69944;\"><\/span> Lotes \/ Series",
                "url": "\/product\/stock\/productlot_list.php"
            },
            "searchintomo": {
                "position": 35,
                "shortcut": "",
                "img": "object_mrp",
                "label": "&Oacute;rdenes de fabricaci&oacute;n",
                "text": "<span class=\"fas fa-cubes pictofixedwidth\" style=\" color: #a69944;\"><\/span> &Oacute;rdenes de fabricaci&oacute;n",
                "url": "\/mrp\/mo_list.php"
            },
            "searchintoprojects": {
                "position": 40,
                "shortcut": "Q",
                "img": "object_project",
                "label": "Proyectos",
                "text": "<span class=\"fas fa-project-diagram  em088 infobox-project pictofixedwidth\" style=\"\"><\/span> Proyectos",
                "url": "\/projet\/list.php"
            },
            "searchintotasks": {
                "position": 45,
                "img": "object_projecttask",
                "label": "Tareas",
                "text": "<span class=\"fas fa-tasks infobox-project pictofixedwidth\" style=\"\"><\/span> Tareas",
                "url": "\/projet\/tasks\/list.php"
            },
            "searchintopropal": {
                "position": 60,
                "img": "object_propal",
                "label": "Presupuestos",
                "text": "<span class=\"fas fa-file-signature infobox-propal pictofixedwidth\" style=\"\"><\/span> Presupuestos",
                "url": "\/comm\/propal\/list.php"
            },
            "searchintoorder": {
                "position": 70,
                "img": "object_order",
                "label": "Pedidos",
                "text": "<span class=\"fas fa-file-invoice infobox-commande pictofixedwidth\" style=\"\"><\/span> Pedidos",
                "url": "\/commande\/list.php"
            },
            "searchintoshipment": {
                "position": 80,
                "img": "object_shipment",
                "label": "Env&iacute;os a clientes",
                "text": "<span class=\"fas fa-dolly  em092 infobox-commande pictofixedwidth\" style=\"\"><\/span> Env&iacute;os a clientes",
                "url": "\/expedition\/list.php"
            },
            "searchintoinvoice": {
                "position": 90,
                "img": "object_bill",
                "label": "Facturas a clientes",
                "text": "<span class=\"fas fa-file-invoice-dollar infobox-commande pictofixedwidth\" style=\"\"><\/span> Facturas a clientes",
                "url": "\/compta\/facture\/list.php"
            },
            "searchintosupplierpropal": {
                "position": 100,
                "img": "object_supplier_proposal",
                "label": "Presupuestos de proveedor",
                "text": "<span class=\"fas fa-file-signature infobox-supplier_proposal pictofixedwidth\" style=\"\"><\/span> Presupuestos de proveedor",
                "url": "\/supplier_proposal\/list.php"
            },
            "searchintosupplierorder": {
                "position": 110,
                "img": "object_supplier_order",
                "label": "Pedidos a proveedor",
                "text": "<span class=\"fas fa-dol-order_supplier infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Pedidos a proveedor",
                "url": "\/fourn\/commande\/list.php"
            },
            "searchintosupplierinvoice": {
                "position": 120,
                "img": "object_supplier_invoice",
                "label": "Facturas proveedor",
                "text": "<span class=\"fas fa-file-invoice-dollar infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Facturas proveedor",
                "url": "\/fourn\/facture\/list.php"
            },
            "searchintocontract": {
                "position": 130,
                "img": "object_contract",
                "label": "Contratos",
                "text": "<span class=\"fas fa-suitcase  em092 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Contratos",
                "url": "\/contrat\/list.php"
            },
            "searchintointervention": {
                "position": 140,
                "img": "object_intervention",
                "label": "Intervenciones",
                "text": "<span class=\"fas fa-ambulance  em080 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Intervenciones",
                "url": "\/fichinter\/list.php"
            },
            "searchintoknowledgemanagement": {
                "position": 145,
                "img": "object_knowledgemanagement",
                "label": "Base de Conocimientos",
                "text": "<span class=\"fas fa-ticket-alt infobox-contrat rotate90 pictofixedwidth\" style=\"\"><\/span> Base de Conocimientos",
                "url": "\/knowledgemanagement\/knowledgerecord_list.php?mainmenu=ticket"
            },
            "searchintotickets": {
                "position": 146,
                "img": "object_ticket",
                "label": "Tickets",
                "text": "<span class=\"fas fa-ticket-alt infobox-contrat pictofixedwidth\" style=\"\"><\/span> Tickets",
                "url": "\/ticket\/list.php?mainmenu=ticket"
            },
            "searchintocustomerpayments": {
                "position": 170,
                "img": "object_payment",
                "label": "Pagos de clientes",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos de clientes",
                "url": "\/compta\/paiement\/list.php?leftmenu=customers_bills_payment"
            },
            "searchintovendorpayments": {
                "position": 175,
                "img": "object_payment",
                "label": "Pagos a proveedor",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos a proveedor",
                "url": "\/fourn\/paiement\/list.php?leftmenu=suppliers_bills_payment"
            },
            "searchintomiscpayments": {
                "position": 180,
                "img": "object_payment",
                "label": "Pagos varios",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos varios",
                "url": "\/compta\/bank\/various_payment\/list.php?leftmenu=tax_various"
            },
            "searchintouser": {
                "position": 200,
                "shortcut": "U",
                "img": "object_user",
                "label": "Usuarios",
                "text": "<span class=\"fas fa-user infobox-adherent pictofixedwidth\" style=\"\"><\/span> Usuarios",
                "url": "\/user\/list.php"
            },
            "searchintoexpensereport": {
                "position": 210,
                "img": "object_trip",
                "label": "Informes de gastos",
                "text": "<span class=\"fas fa-wallet infobox-expensereport pictofixedwidth\" style=\"\"><\/span> Informes de gastos",
                "url": "\/expensereport\/list.php?mainmenu=hrm"
            },
            "searchintoleaves": {
                "position": 220,
                "img": "object_holiday",
                "label": "D&iacute;a libre",
                "text": "<span class=\"fas fa-umbrella-beach  em088 infobox-holiday pictofixedwidth\" style=\"\"><\/span> D&iacute;a libre",
                "url": "\/holiday\/list.php?mainmenu=hrm"
            }
        };

        $(".searchselectcombo").select2({
            data: data,
            language: select2arrayoflanguage,
            containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
            placeholder: "Buscar",
            escapeMarkup: function (markup) {
                return markup;
            }, 	// let our custom formatter work
            minimumInputLength: 1,
            formatResult: function (result, container, query, escapeMarkup) {
                return escapeMarkup(result.text);
            },
            matcher: function (params, data) {

                if (!data.id) return null;

                var urlBase = data.url;
                var separ = urlBase.indexOf("?") >= 0 ? "&" : "?";
                /* console.log("params.term="+params.term); */
                /* console.log("params.term encoded="+encodeURIComponent(params.term)); */
                saveRemoteData[data.id].url = urlBase + separ + "search_all=" + encodeURIComponent(params.term.replace(/\"/g, ""));

                return data;
            }
        });


        /* Code to execute a GET when we select a value */
        $(".searchselectcombo").change(function () {
            var selected = $(".searchselectcombo").val();
            console.log("We select " + selected)

            $(".searchselectcombo").val("");  /* reset visible combo value */
            $.each(saveRemoteData, function (key, value) {
                if (key == selected) {
                    console.log("selectArrayFilter - Do a redirect to " + value.url)
                    location.assign(value.url);
                }
            });
        });

    });
</script>
<!-- Includes JS Footer of Dolibarr -->
<script src="/core/js/lib_foot.js.php?lang=es_ES&layout=classic&version=20.0.0-alpha"></script>

<!-- A div to allow dialog popup by jQuery('#dialogforpopup').dialog() -->
<div id="dialogforpopup" style="display: none;"></div>
</body>
</html>

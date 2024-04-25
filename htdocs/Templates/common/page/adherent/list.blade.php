@extends('layout.main')

@section('content')
    <!-- Begin div class="fiche" -->
    <div class="fiche">
        <form method="POST" id="searchFormList" action="/adherents/list.php">
            <input type="hidden" name="token" value="cfae8a162d1c8caf8dda987fb1399f30">
            <input type="hidden" name="formfilteraction" id="formfilteraction" value="list">
            <input type="hidden" name="action" value="list">
            <input type="hidden" name="sortfield" value="d.lastname">
            <input type="hidden" name="sortorder" value="ASC">
            <input type="hidden" name="page" value="0">
            <input type="hidden" name="contextpage" value="memberslist">
            <input type="hidden" name="page_y" value="">
            <input type="hidden" name="mode" value="common">

            <!-- Begin title -->
            <table class="centpercent notopnoleftnoright table-fiche-title">
                <tr>
                    <td class="nobordernopadding widthpictotitle valignmiddle col-picto">
                        <span class="fas fa-user-alt  em092 infobox-adherent valignmiddle pictotitle widthpictotitle" style=""></span>
                    </td>
                    <td class="nobordernopadding valignmiddle col-title">
                        <div class="titre inline-block">Miembros -
                            Listado<span class="opacitymedium colorblack paddingleft">(2)</span></div>
                    </td>
                    <td class="nobordernopadding center valignmiddle col-center">
                        <div class="centpercent center">
                            <select class="flat hideobject massaction massactionselect valignmiddle alignstart" id="massaction" name="massaction">
                                <option value="0">-- Seleccione acci&oacute;n --</option>
                                <option value="close" data-html="&lt;span class=&quot;fas fa-times pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;Cancelar">
                                    <span class="fas fa-times pictofixedwidth" style=""></span>Cancelar
                                </option>
                                <option value="predelete" data-html="&lt;span class=&quot;fas fa-trash pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;Eliminar">
                                    <span class="fas fa-trash pictofixedwidth" style=""></span>Eliminar
                                </option>
                                <option value="preaffecttag" data-html="&lt;span class=&quot;fas fa-tag pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;Asignar una etiqueta">
                                    <span class="fas fa-tag pictofixedwidth" style=""></span>Asignar una etiqueta
                                </option>
                                <option value="createexternaluser" data-html="&lt;span class=&quot;fas fa-user infobox-adherent pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;Crear usuario externo">
                                    <span class="fas fa-user infobox-adherent pictofixedwidth" style=""></span>Crear
                                    usuario externo
                                </option>
                                <option value="createsubscription" data-html="&lt;span class=&quot;fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth&quot; style=&quot;&quot;&gt;&lt;/span&gt;Crear afiliaci&oacute;n">
                                    <span class="fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth" style=""></span>Crear
                                    afiliaci&oacute;n
                                </option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = .massactionselect -->
                            <script>
                                $(document).ready(function () {
                                    $('.massactionselect').select2({
                                        dir: 'ltr', width: 'resolve',		/* off or resolve */
                                        minimumInputLength: 0,
                                        language: select2arrayoflanguage,
                                        matcher: function (params, data) {
                                            if ($.trim(params.term) === "") {
                                                return data;
                                            }
                                            keywords = (params.term).split(" ");
                                            for (var i = 0; i < keywords.length; i++) {
                                                if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                                    return null;
                                                }
                                            }
                                            return data;
                                        },
                                        theme: 'default',		/* to add css on generated html components */
                                        containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        dropdownCssClass: 'ui-dialog',
                                        templateResult: function (data, container) {	/* Format visible output into combo list */
                                            /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                            if (data.element) {
                                                $(container).addClass($(data.element).attr("class"));
                                            }
                                            //console.log("data html is "+$(data.element).attr("data-html"));
                                            if (data.id == -1 && $(data.element).attr("data-html") == undefined) {
                                                return '&nbsp;';
                                            }
                                            if ($(data.element).attr("data-html") != undefined) {
                                                /* If property html set, we decode html entities and use this. */
                                                /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                                if (typeof htmlEntityDecodeJs === "function") {
                                                    return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                                }
                                            }
                                            return data.text;
                                        },
                                        templateSelection: function (selection) {		/* Format visible output of selected value */
                                            if (selection.id == -1) return '<span class="placeholder">' + selection.text + '</span>';
                                            return selection.text;
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup;
                                        }
                                    });
                                });
                            </script>
                            <input type="submit" name="confirmmassactioninvisible" style="display: none" tabindex="-1"><input type="submit" disabled name="confirmmassaction" style="display: none" class="reposition button smallpaddingimp hideobject massaction massactionconfirmed" value="Confirmar">
                        </div><!-- JS CODE TO ENABLE mass action select -->
                        <script nonce="fcf200e3">
                            function initCheckForSelect(mode, name, cssclass)	/* mode is 0 during init of page or click all, 1 when we click on 1 checkboxi, "name" refers to the class of the massaction button, "cssclass" to the class of the checkfor select boxes */ {
                                atleastoneselected = 0;
                                jQuery("." + cssclass).each(function (index) {
                                    /* console.log( index + ": " + $( this ).text() ); */
                                    if ($(this).is(':checked')) atleastoneselected++;
                                });

                                console.log("initCheckForSelect mode=" + mode + " name=" + name + " cssclass=" + cssclass + " atleastoneselected=" + atleastoneselected);

                                if (atleastoneselected || 0) {
                                    jQuery("." + name).show();


                                } else {
                                    jQuery("." + name).hide();
                                    jQuery("." + name + "other").hide();
                                }
                            }

                            jQuery(document).ready(function () {
                                initCheckForSelect(0, "massaction", "checkforselect");
                                jQuery(".checkforselect").click(function () {
                                    initCheckForSelect(1, "massaction", "checkforselect");
                                });
                                jQuery(".massactionselect").change(function () {
                                    var massaction = $(this).val();
                                    var urlform = $(this).closest("form").attr("action").replace("#show_files", "");
                                    if (massaction == "builddoc") {
                                        urlform = urlform + "#show_files";
                                    }
                                    $(this).closest("form").attr("action", urlform);
                                    console.log("we select a mass action name=massaction massaction=" + massaction + " - " + urlform);
                                    /* Warning: if you set submit button to disabled, post using Enter will no more work if there is no other button */
                                    if ($(this).val() != '0') {
                                        jQuery(".massactionconfirmed").prop('disabled', false);
                                        jQuery(".massactionother").hide();	/* To disable if another div was open */
                                        jQuery(".massaction" + massaction).show();
                                    } else {
                                        jQuery(".massactionconfirmed").prop('disabled', true);
                                        jQuery(".massactionother").hide();	/* To disable any div open */
                                    }
                                });
                            });
                        </script>
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
                                    <a class="btnTitle reposition btnTitleSelected" href="/adherents/list.php?mode=common&amp;contextpage=memberslist" title="Vista de listado"><span class="fa fa-bars imgforviewmode valignmiddle btnTitle-icon"></span></a><a class="btnTitle reposition" href="/adherents/list.php?mode=kanban&amp;contextpage=memberslist" title="Vista Kanban"><span class="fa fa-th-list imgforviewmode valignmiddle btnTitle-icon"></span></a><span class="button-title-separator "></span><a class="btnTitle btnTitlePlus" href="https://alixar/adherents/card.php?action=create" title="Nuevo miembro"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>
                                </li>
                            </ul>
                        </div>
                        <script nonce="fcf200e3">
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

            <div class="liste_titre liste_titre_bydiv centpercent">
                <div class="divsearchfield">
                    <span class="fas fa-tag pictofixedwidth" style="" title="Etiquetas/Categor&iacute;as"></span><select class="flat minwidth100" id="select_categ_search_categ" name="search_categ">
                        <option class="optiongrey" value="-1">Etiquetas/categor&iacute;as de miembros</option>
                        <option value="-2">- Sin etiqueta/categor&iacute;a -</option>
                    </select>
                    <!-- JS CODE TO ENABLE select2 for id = select_categ_search_categ -->
                    <script>
                        $(document).ready(function () {
                            $('#select_categ_search_categ').select2({
                                dir: 'ltr', width: 'resolve',		/* off or resolve */
                                minimumInputLength: 0,
                                language: select2arrayoflanguage,
                                matcher: function (params, data) {
                                    if ($.trim(params.term) === "") {
                                        return data;
                                    }
                                    keywords = (params.term).split(" ");
                                    for (var i = 0; i < keywords.length; i++) {
                                        if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                            return null;
                                        }
                                    }
                                    return data;
                                },
                                theme: 'default',		/* to add css on generated html components */
                                containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                dropdownCssClass: 'ui-dialog',
                                templateResult: function (data, container) {	/* Format visible output into combo list */
                                    /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                    if (data.element) {
                                        $(container).addClass($(data.element).attr("class"));
                                    }
                                    //console.log("data html is "+$(data.element).attr("data-html"));
                                    if (data.id == -1 && $(data.element).attr("data-html") == undefined) {
                                        return '&nbsp;';
                                    }
                                    if ($(data.element).attr("data-html") != undefined) {
                                        /* If property html set, we decode html entities and use this. */
                                        /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                        if (typeof htmlEntityDecodeJs === "function") {
                                            return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                        }
                                    }
                                    return data.text;
                                },
                                templateSelection: function (selection) {		/* Format visible output of selected value */
                                    if (selection.id == -1) return '<span class="placeholder">' + selection.text + '</span>';
                                    return selection.text;
                                },
                                escapeMarkup: function (markup) {
                                    return markup;
                                }
                            });
                        });
                    </script>
                </div>
            </div>
            <div class="div-table-responsive">
                <table class="tagtable nobottomiftotal liste listwithfilterbefore">
                    <tr class="liste_titre_filter">
                        <td class="liste_titre">
                            <input type="text" class="flat maxwidth75imp" name="search_ref" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth75imp" type="text" name="search_firstname" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth75imp" type="text" name="search_lastname" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth75imp" type="text" name="search_company" value=""></td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth75imp" type="text" name="search_login" value=""></td>
                        <td class="liste_titre center">
                            <select id="search_morphy" class="flat search_morphy maxwidth100 selectformat" name="search_morphy">
                                <option class="optiongrey" value="-1">&nbsp;</option>
                                <option value="mor">Corporaci&oacute;n</option>
                                <option value="phy">Individual</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = search_morphy -->
                            <script>
                                $(document).ready(function () {
                                    $('#search_morphy').select2({
                                        dir: 'ltr', width: 'resolve',		/* off or resolve */
                                        minimumInputLength: 0,
                                        language: select2arrayoflanguage,
                                        matcher: function (params, data) {
                                            if ($.trim(params.term) === "") {
                                                return data;
                                            }
                                            keywords = (params.term).split(" ");
                                            for (var i = 0; i < keywords.length; i++) {
                                                if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                                    return null;
                                                }
                                            }
                                            return data;
                                        },
                                        theme: 'default maxwidth100',		/* to add css on generated html components */
                                        containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        dropdownCssClass: 'ui-dialog',
                                        templateResult: function (data, container) {	/* Format visible output into combo list */
                                            /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                            if (data.element) {
                                                $(container).addClass($(data.element).attr("class"));
                                            }
                                            //console.log("data html is "+$(data.element).attr("data-html"));
                                            if (data.id == -1 && $(data.element).attr("data-html") == undefined) {
                                                return '&nbsp;';
                                            }
                                            if ($(data.element).attr("data-html") != undefined) {
                                                /* If property html set, we decode html entities and use this. */
                                                /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                                if (typeof htmlEntityDecodeJs === "function") {
                                                    return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                                }
                                            }
                                            return data.text;
                                        },
                                        templateSelection: function (selection) {		/* Format visible output of selected value */
                                            if (selection.id == -1) return '<span class="placeholder">' + selection.text + '</span>';
                                            return selection.text;
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup;
                                        }
                                    });
                                });
                            </script>
                        </td>
                        </td>
                        <td class="liste_titre">
                            <select id="search_type" class="flat search_type minwidth75 selectformat" name="search_type">
                                <option class="optiongrey" value="-1">&nbsp;</option>
                                <option value="7">Partner</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = search_type -->
                            <script>
                                $(document).ready(function () {
                                    $('#search_type').select2({
                                        dir: 'ltr', width: 'resolve',		/* off or resolve */
                                        minimumInputLength: 0,
                                        language: select2arrayoflanguage,
                                        matcher: function (params, data) {
                                            if ($.trim(params.term) === "") {
                                                return data;
                                            }
                                            keywords = (params.term).split(" ");
                                            for (var i = 0; i < keywords.length; i++) {
                                                if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                                    return null;
                                                }
                                            }
                                            return data;
                                        },
                                        theme: 'default minwidth75',		/* to add css on generated html components */
                                        containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        dropdownCssClass: 'ui-dialog',
                                        templateResult: function (data, container) {	/* Format visible output into combo list */
                                            /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                            if (data.element) {
                                                $(container).addClass($(data.element).attr("class"));
                                            }
                                            //console.log("data html is "+$(data.element).attr("data-html"));
                                            if (data.id == -1 && $(data.element).attr("data-html") == undefined) {
                                                return '&nbsp;';
                                            }
                                            if ($(data.element).attr("data-html") != undefined) {
                                                /* If property html set, we decode html entities and use this. */
                                                /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                                if (typeof htmlEntityDecodeJs === "function") {
                                                    return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                                }
                                            }
                                            return data.text;
                                        },
                                        templateSelection: function (selection) {		/* Format visible output of selected value */
                                            if (selection.id == -1) return '<span class="placeholder">' + selection.text + '</span>';
                                            return selection.text;
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup;
                                        }
                                    });
                                });
                            </script>
                        </td>
                        <td class="liste_titre left">
                            <input class="flat maxwidth75imp" type="text" name="search_email" value=""></td>
                        <td class="liste_titre center">
                            <select id="search_filter" class="flat search_filter minwidth75 selectformat" name="search_filter">
                                <option value="-1">&nbsp;</option>
                                <option value="waitingsubscription">Membres&iacute;a pendiente</option>
                                <option value="uptodate">A hoy</option>
                                <option value="outofdate">Fuera de plazo</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = search_filter -->
                            <script>
                                $(document).ready(function () {
                                    $('#search_filter').select2({
                                        dir: 'ltr', width: 'resolve',		/* off or resolve */
                                        minimumInputLength: 0,
                                        language: select2arrayoflanguage,
                                        matcher: function (params, data) {
                                            if ($.trim(params.term) === "") {
                                                return data;
                                            }
                                            keywords = (params.term).split(" ");
                                            for (var i = 0; i < keywords.length; i++) {
                                                if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                                    return null;
                                                }
                                            }
                                            return data;
                                        },
                                        theme: 'default minwidth75',		/* to add css on generated html components */
                                        containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        dropdownCssClass: 'ui-dialog',
                                        templateResult: function (data, container) {	/* Format visible output into combo list */
                                            /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                            if (data.element) {
                                                $(container).addClass($(data.element).attr("class"));
                                            }
                                            //console.log("data html is "+$(data.element).attr("data-html"));
                                            if (data.id == -1 && $(data.element).attr("data-html") == undefined) {
                                                return '&nbsp;';
                                            }
                                            if ($(data.element).attr("data-html") != undefined) {
                                                /* If property html set, we decode html entities and use this. */
                                                /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                                if (typeof htmlEntityDecodeJs === "function") {
                                                    return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                                }
                                            }
                                            return data.text;
                                        },
                                        templateSelection: function (selection) {		/* Format visible output of selected value */
                                            if (selection.id == -1) return '<span class="placeholder">' + selection.text + '</span>';
                                            return selection.text;
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup;
                                        }
                                    });
                                });
                            </script>
                        </td><!-- extrafields_list_search_input.tpl.php -->
                        <td class="liste_titre center parentonrightofpage">
                            <select id="search_status" class="flat search_status search_status width100 onrightofpage selectformat" name="search_status">
                                <option class="optiongrey" value="-3">&nbsp;</option>
                                <option value="-1">Borrador</option>
                                <option value="1">Validado</option>
                                <option value="0">De baja</option>
                                <option value="-2">Excluido</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = search_status -->
                            <script>
                                $(document).ready(function () {
                                    $('#search_status').select2({
                                        dir: 'ltr',
                                        dropdownAutoWidth: true,
                                        dropdownParent: $('#search_status').parent(),
                                        width: 'resolve',		/* off or resolve */
                                        minimumInputLength: 0,
                                        language: select2arrayoflanguage,
                                        matcher: function (params, data) {
                                            if ($.trim(params.term) === "") {
                                                return data;
                                            }
                                            keywords = (params.term).split(" ");
                                            for (var i = 0; i < keywords.length; i++) {
                                                if (((data.text).toUpperCase()).indexOf((keywords[i]).toUpperCase()) == -1) {
                                                    return null;
                                                }
                                            }
                                            return data;
                                        },
                                        theme: 'default search_status width100 onrightofpage',		/* to add css on generated html components */
                                        containerCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        selectionCssClass: ':all:',					/* Line to add class of origin SELECT propagated to the new <span class="select2-selection...> tag */
                                        dropdownCssClass: 'ui-dialog',
                                        templateResult: function (data, container) {	/* Format visible output into combo list */
                                            /* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                                            if (data.element) {
                                                $(container).addClass($(data.element).attr("class"));
                                            }
                                            //console.log("data html is "+$(data.element).attr("data-html"));
                                            if (data.id == -3 && $(data.element).attr("data-html") == undefined) {
                                                return '&nbsp;';
                                            }
                                            if ($(data.element).attr("data-html") != undefined) {
                                                /* If property html set, we decode html entities and use this. */
                                                /* Note that HTML content must have been sanitized from js with dol_escape_htmltag(xxx, 0, 0, '', 0, 1) when building the select option. */
                                                if (typeof htmlEntityDecodeJs === "function") {
                                                    return htmlEntityDecodeJs($(data.element).attr("data-html"));
                                                }
                                            }
                                            return data.text;
                                        },
                                        templateSelection: function (selection) {		/* Format visible output of selected value */
                                            if (selection.id == -3) return '<span class="placeholder">' + selection.text + '</span>';
                                            return selection.text;
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup;
                                        }
                                    });
                                });
                            </script>
                        </td>
                        <td class="liste_titre center maxwidthsearch">
                            <div class="nowraponall">
                                <button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x">
                                    <span class="fas fa-search"></span></button>
                                <button type="submit" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x">
                                    <span class="fas fa-times"></span></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="liste_titre">
                        <th class="wrapcolumntitle liste_titre" title="Ref.">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.ref&sortorder=asc&begin=&mode=common&contextpage=memberslist">Ref.</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Nombre">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.firstname&sortorder=asc&begin=&mode=common&contextpage=memberslist">Nombre</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre_sel" title="Apellidos">
                            <span class="nowrap"><span class="fas fa-caret-down imgdown paddingright" style="" title="A-Z"></span></span><a class="reposition" href="/adherents/list.php?sortfield=d.lastname&sortorder=desc&begin=&mode=common&contextpage=memberslist">Apellidos</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Empresa">
                            <a class="reposition" href="/adherents/list.php?sortfield=companyname&sortorder=asc&begin=&mode=common&contextpage=memberslist">Empresa</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Login">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.login&sortorder=asc&begin=&mode=common&contextpage=memberslist">Login</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Naturaleza del miembro">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.morphy&sortorder=asc&begin=&mode=common&contextpage=memberslist">Naturaleza
                                del miembro</a></th>
                        <th class="wrapcolumntitle liste_titre" title="Tipo">
                            <a class="reposition" href="/adherents/list.php?sortfield=t.libelle&sortorder=asc&begin=&mode=common&contextpage=memberslist">Tipo</a>
                        </th>
                        <th class="wrapcolumntitle liste_titre" title="Correo">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.email&sortorder=asc&begin=&mode=common&contextpage=memberslist">Correo</a>
                        </th>
                        <th class="wrapcolumntitle center liste_titre" title="Fecha de fin">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.datefin,t.subscription&sortorder=asc,asc&begin=&mode=common&contextpage=memberslist">Fecha
                                de fin</a></th>
                        <th class="wrapcolumntitle center liste_titre" title="Statut">
                            <a class="reposition" href="/adherents/list.php?sortfield=d.statut,t.subscription,d.datefin&sortorder=asc,asc,asc&begin=&mode=common&contextpage=memberslist">Statut</a>
                        </th>
                        <th class="wrapcolumntitle maxwidthsearch center liste_titre">
                            <!-- Component multiSelectArrayWithCheckbox selectedfields -->

                            <dl class="dropdown">
                                <dt>
                                    <a href="#selectedfields">
                                        <span class="fas fa-list" style=""></span>
                                    </a>
                                    <input type="hidden" class="selectedfields" name="selectedfields" value="d.ref,d.lastname,d.firstname,d.login,d.morphy,t.libelle,d.company,d.email,d.datefin,d.statut,">
                                </dt>
                                <dd class="dropdowndd">
                                    <div class="multiselectcheckboxselectedfields">
                                        <ul class="selectedfields">
                                            <li>
                                                <input class="inputsearch_dropdownselectedfields width90p minwidth200imp" style="width:90%;" type="text" placeholder="Buscar">
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.ref" value="d.ref" checked="checked"/><label for="checkboxd.ref">Ref.</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.civility" value="d.civility"/><label for="checkboxd.civility">T&iacute;tulo
                                                    Cortes&iacute;a</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.lastname" value="d.lastname" checked="checked"/><label for="checkboxd.lastname">Apellidos</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.firstname" value="d.firstname" checked="checked"/><label for="checkboxd.firstname">Nombre</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.login" value="d.login" checked="checked"/><label for="checkboxd.login">Login</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.morphy" value="d.morphy" checked="checked"/><label for="checkboxd.morphy">Naturaleza
                                                    del miembro</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxt.libelle" value="t.libelle" checked="checked"/><label for="checkboxt.libelle">Tipo</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.company" value="d.company" checked="checked"/><label for="checkboxd.company">Empresa</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.address" value="d.address"/><label for="checkboxd.address">Direcci&oacute;n</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.zip" value="d.zip"/><label for="checkboxd.zip">C&oacute;digo
                                                    postal</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.town" value="d.town"/><label for="checkboxd.town">Poblaci&oacute;n</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxstate.nom" value="state.nom"/><label for="checkboxstate.nom">Provincia</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxcountry.code_iso" value="country.code_iso"/><label for="checkboxcountry.code_iso">Pa&iacute;s</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.phone" value="d.phone"/><label for="checkboxd.phone">Tel&eacute;fono</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.phone_perso" value="d.phone_perso"/><label for="checkboxd.phone_perso">Phone
                                                    perso</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.phone_mobile" value="d.phone_mobile"/><label for="checkboxd.phone_mobile">Phone
                                                    mobile</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.email" value="d.email" checked="checked"/><label for="checkboxd.email">Correo</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.birth" value="d.birth"/><label for="checkboxd.birth">Fecha
                                                    de nacimiento</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.gender" value="d.gender"/><label for="checkboxd.gender">Sexo</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.datefin" value="d.datefin" checked="checked"/><label for="checkboxd.datefin">Fecha
                                                    de fin</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.datec" value="d.datec"/><label for="checkboxd.datec">Fecha
                                                    de creaci&oacute;n</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.tms" value="d.tms"/><label for="checkboxd.tms">Fecha
                                                    de modificaci&oacute;n</label></li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.statut" value="d.statut" checked="checked"/><label for="checkboxd.statut">Statut</label>
                                            </li>
                                            <li>
                                                <input type="checkbox" id="checkboxd.import_key" value="d.import_key"/><label for="checkboxd.import_key">ID
                                                    de importaci&oacute;n</label></li>
                                        </ul>
                                    </div>
                                </dd>
                            </dl>

                            <script nonce="fcf200e3" type="text/javascript">
                                jQuery(document).ready(function () {
                                    $('.multiselectcheckboxselectedfields input[type="checkbox"]').on('click', function () {
                                        console.log("A new field was added/removed, we edit field input[name=formfilteraction]");

                                        $("input:hidden[name=formfilteraction]").val('listafterchangingselectedfields');	// Update field so we know we changed something on selected fields after POST

                                        var title = $(this).val() + ",";
                                        if ($(this).is(':checked')) {
                                            $('.selectedfields').val(title + $('.selectedfields').val());
                                        } else {
                                            $('.selectedfields').val($('.selectedfields').val().replace(title, ''))
                                        }
                                        // Now, we submit page
                                        //$(this).parents('form:first').submit();
                                    });
                                    $("input.inputsearch_dropdownselectedfields").on("keyup", function () {
                                        var value = $(this).val().toLowerCase();
                                        $('.multiselectcheckboxselectedfields li > label').filter(function () {
                                            $(this).parent().toggle($(this).text().toLowerCase().indexOf(value) > -1)
                                        });
                                    });


                                });
                            </script>

                            <div class="inline-block checkallactions">
                                <input type="checkbox" id="checkforselects" name="checkforselects" class="checkallactions">
                            </div>
                            <script nonce="fcf200e3">
                                $(document).ready(function () {
                                    $("#checkforselects").click(function () {
                                        if ($(this).is(':checked')) {
                                            console.log("We check all checkforselect and trigger the change method");
                                            $(".checkforselect").prop('checked', true).trigger('change');
                                        } else {
                                            console.log("We uncheck all");
                                            $(".checkforselect").prop('checked', false).trigger('change');
                                        }
                                        if (typeof initCheckForSelect == 'function') {
                                            initCheckForSelect(0, "massaction", "checkforselect");
                                        } else {
                                            console.log("No function initCheckForSelect found. Call won't be done.");
                                        }
                                    });
                                    $(".checkforselect").change(function () {
                                        $(this).closest("tr").toggleClass("highlight", this.checked);
                                    });
                                });
                            </script>
                        </th>
                    </tr>
                    <tr data-rowid="3" class="oddeven">
                        <td>
                            <a href="https://alixar/adherents/card.php?rowid=3&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 3&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Jer&oacute;nimo L&oacute;pez Bazt&aacute;n&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;EMail:&lt;/b&gt; jlopez@seur.net&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip">
                                <div class="inline-block nopadding valignmiddle">
                                    <span class="nopadding userimg paddingrightonly"><!-- Put link to gravatar --><img class="photomemberphoto userphoto" alt="" title="jlopez@seur.net Gravatar avatar" src="https://www.gravatar.com/avatar/4c6c50a0515dc59f31fe33f89db83f6b2d1bed85f85698ebf12aab1f247c056e?s=0&d=identicon"></span><span class="nopadding valignmiddle usertext paddingrightonly">3</span>
                                </div>
                            </a></td>
                        <td class="tdoverflowmax150" title="Jer&oacute;nimo">
                            <a href="https://alixar/adherents/card.php?rowid=3&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 3&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Jer&oacute;nimo L&oacute;pez Bazt&aacute;n&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;EMail:&lt;/b&gt; jlopez@seur.net&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip"><span class="nopadding valignmiddle">Jerónimo</span></a>
                        </td>
                        <td class="tdoverflowmax150" title="L&oacute;pez Bazt&aacute;n">
                            <a href="https://alixar/adherents/card.php?rowid=3&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 3&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Jer&oacute;nimo L&oacute;pez Bazt&aacute;n&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;EMail:&lt;/b&gt; jlopez@seur.net&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip"><span class="nopadding valignmiddle">López Baztán</span></a>
                        </td>
                        <td class="tdoverflowmax150" title="SEUR">SEUR</td>
                        <td class="tdoverflowmax150" title=""></td>
                        <td class="center">
                            <span class="member-company-back paddingleftimp paddingrightimp" title="Corporaci&oacute;n">C</span>
                        </td>
                        <td class="nowraponall">
                            <a href="https://alixar/adherents/type.php?rowid=7&save_lastsearch_values=1" title="&lt;span class=&quot;fas fa-user-friends  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Tipo de miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;Activo&quot;&gt;Activo&lt;/span&gt;&lt;br&gt;Etiqueta: Partner" class="classfortooltip"><span class="fas fa-user-friends  em092 infobox-adherent paddingright" style=""></span>Partner</a>
                        </td>
                        <td class="tdoverflowmax150" title="jlopez@seur.net">
                            <a class="paddingrightonly" style="text-overflow: ellipsis;" href="mailto:jlopez@seur.net"><span class="fas fa-at paddingrightonly" style="" title="EMail : jlopez@seur.net"></span>jlopez@seur.net</a>
                        </td>
                        <td class="nowraponall center"><span class="opacitymedium">Afiliaci&oacute;n no recibida</span>
                        </td>
                        <td class="nowrap center">
                            <span class="badge  badge-status0 badge-status" title="Borrador (a validar)">Borrador</span>
                        </td>
                        <td class="center">
                            <input id="cb3" class="flat checkforselect" type="checkbox" name="toselect[]" value="3">
                        </td>
                    </tr>
                    <tr data-rowid="4" class="oddeven">
                        <td>
                            <a href="https://alixar/adherents/card.php?rowid=4&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 4&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Juana P&eacute;rez Garc&iacute;a&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip">
                                <div class="inline-block nopadding valignmiddle">
                                    <span class="nopadding userimg paddingrightonly"><img class="photomemberphoto userphoto" alt="" src="https://alixar/public/theme/common/user_woman.png"></span><span class="nopadding valignmiddle usertext paddingrightonly">4</span>
                                </div>
                            </a></td>
                        <td class="tdoverflowmax150" title="Juana">
                            <a href="https://alixar/adherents/card.php?rowid=4&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 4&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Juana P&eacute;rez Garc&iacute;a&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip"><span class="nopadding valignmiddle">Juana</span></a>
                        </td>
                        <td class="tdoverflowmax150" title="P&eacute;rez Garc&iacute;a">
                            <a href="https://alixar/adherents/card.php?rowid=4&save_lastsearch_values=1" title="&lt;div class=&quot;centpercent&quot;&gt;&lt;span class=&quot;fas fa-user-alt  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status0 badge-status&quot; title=&quot;Borrador (a validar)&quot;&gt;Borrador (a validar)&lt;/span&gt;&nbsp;&lt;span class=&quot;member-company-back paddingleftimp paddingrightimp&quot; title=&quot;Corporaci&oacute;n&quot;&gt;Corporaci&oacute;n&lt;/span&gt;&lt;br&gt;&lt;b&gt;Ref.:&lt;/b&gt; 4&lt;br&gt;&lt;b&gt;Nombre:&lt;/b&gt; Juana P&eacute;rez Garc&iacute;a&lt;br&gt;&lt;b&gt;Empresa:&lt;/b&gt; SEUR&lt;br&gt;&lt;b&gt;Direcci&oacute;n:&lt;/b&gt; &lt;/div&gt;" class="classfortooltip"><span class="nopadding valignmiddle">Pérez García</span></a>
                        </td>
                        <td class="tdoverflowmax150" title="SEUR">SEUR</td>
                        <td class="tdoverflowmax150" title=""></td>
                        <td class="center">
                            <span class="member-company-back paddingleftimp paddingrightimp" title="Corporaci&oacute;n">C</span>
                        </td>
                        <td class="nowraponall">
                            <a href="https://alixar/adherents/type.php?rowid=7&save_lastsearch_values=1" title="&lt;span class=&quot;fas fa-user-friends  em092 infobox-adherent&quot; style=&quot;&quot;&gt;&lt;/span&gt; &lt;u class=&quot;paddingrightonly&quot;&gt;Tipo de miembro&lt;/u&gt; &lt;span class=&quot;badge  badge-status4 badge-status&quot; title=&quot;Activo&quot;&gt;Activo&lt;/span&gt;&lt;br&gt;Etiqueta: Partner" class="classfortooltip"><span class="fas fa-user-friends  em092 infobox-adherent paddingright" style=""></span>Partner</a>
                        </td>
                        <td class="tdoverflowmax150" title="">&nbsp;</td>
                        <td class="nowraponall center"><span class="opacitymedium">Afiliaci&oacute;n no recibida</span>
                        </td>
                        <td class="nowrap center">
                            <span class="badge  badge-status0 badge-status" title="Borrador (a validar)">Borrador</span>
                        </td>
                        <td class="center">
                            <input id="cb4" class="flat checkforselect" type="checkbox" name="toselect[]" value="4">
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

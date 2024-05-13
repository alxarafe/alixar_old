@extends('layout.main')

@section('content')
    <!-- Begin div class="fiche" -->
    <div class="fiche" xmlns="http://www.w3.org/1999/html">
        <table class="centpercent notopnoleftnoright table-fiche-title">
            <tr class="titre">
                <td class="nobordernopadding widthpictotitle valignmiddle col-picto">
                    <span class="fas fa-user-friends  em092 infobox-adherent valignmiddle widthpictotitle pictotitle" style=""></span>
                </td>
                <td class="nobordernopadding valignmiddle col-title">
                    <div class="titre inline-block">Nuevo tipo de miembro</div>
                </td>
            </tr>
        </table>
        <form href="/adherents/type.php" method="POST">
            <input type="hidden" name="token" value="{!! newToken() !!}">
            <!-- dol_fiche_head - dol_get_fiche_head -->
            <div id="dragDropAreaTabBar" class="tabBar tabBarWithBottom">
                <table class="border centpercent">
                    <tbody>
                    <tr>
                        <td class="titlefieldcreate fieldrequired">Etiqueta</td>
                        <td>
                            <input type="text" class="minwidth200" name="label" autofocus="autofocus" value="{!! $me->object->label !!}">
                        </td>
                    </tr>
                    <tr>
                        <td>Estado</td>
                        <td><select id="status" class="flat status minwidth100 selectformat" name="status">
                                <option value="0" @if($me->object->status === "0") selected @endif >Cerrado</option>
                                <option value="1" @if($me->object->status === "1") selected @endif >Activo</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = status -->
                            <script>
                                $(document).ready(function () {
                                    $('#status').select2({
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
                                        theme: 'default minwidth100',		/* to add css on generated html components */
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
                    </tr>
                    <tr>
                        <td><span>Naturaleza de los miembros</span></td>
                        <td><select id="morphy" class="flat morphy minwidth75 selectformat" name="morphy">
                                <option value="" @if($me->object->morphy === "") selected @endif >Corporaci&oacute;n e
                                    Individuo
                                </option>
                                <option value="phy" @if($me->object->morphy === "phy") selected @endif >Individual
                                </option>
                                <option value="mor" @if($me->object->morphy === "mor") selected @endif >Corporaci&oacute;n</option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = morphy -->
                            <script>
                                $(document).ready(function () {
                                    $('#morphy').select2({
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
                    </tr>
                    <tr>
                        <td>
                            <span style="padding: 0px; padding-right: 3px;">Sujeto a cotizaci&oacute;n</span><span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="Si se requiere suscripci&oacute;n, se debe registrar una suscripci&oacute;n con una fecha de inicio o finalizaci&oacute;n para tener al miembro al d&iacute;a (cualquiera que sea el monto de la suscripci&oacute;n, incluso si la suscripci&oacute;n es gratuita)."><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>
                        </td>
                        <td><select class="flat width75" id="subscription" name="subscription">
                                <option value="1" @if($me->object->subscription === "1") selected @endif >S&iacute;
                                </option>
                                <option value="0" @if($me->object->subscription === "0") selected @endif >No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Importe</td>
                        <td><input name="amount" size="5" value="{!! $me->object->amount !!}"></td>
                    </tr>
                    <tr>
                        <td>
                            <span style="padding: 0px; padding-right: 3px;">Cualquier importe</span><span class="classfortooltip" style="padding: 0px; padding: 0px; padding-right: 3px;" title="El monto de la suscripci&oacute;n puede ser definido por el miembro"><span class="fas fa-info-circle  em088 opacityhigh" style=" vertical-align: middle; cursor: help"></span></span>
                        </td>
                        <td><select class="flat width75" id="caneditamount" name="caneditamount">
                                <option value="1" @if($me->object->caneditamount === "1") selected @endif >S&iacute;
                                </option>
                                <option value="0" @if($me->object->caneditamount === "0") selected @endif >No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Voto autorizado</td>
                        <td><select class="flat width75" id="vote" name="vote">
                                <option value="1" @if($me->object->vote === "1") selected @endif >S&iacute;</option>
                                <option value="0" @if($me->object->vote === "0") selected @endif >No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Duraci&oacute;n</td>
                        <td colspan="3">
                            <input name="duration_value" size="5" value="{!! $me->object->duration_value !!}">
                            <select class="flat maxwidth125" name="duration_unit" id="duration_unit">
                                <option value="s" @if($me->object->duration_unit === "s") selected @endif >Segundo
                                </option>
                                <option value="i" @if($me->object->duration_unit === "i") selected @endif >Minuto
                                </option>
                                <option value="h" @if($me->object->duration_unit === "h") selected @endif >Hora</option>
                                <option value="d" @if($me->object->duration_unit === "d") selected @endif >D&iacute;a
                                </option>
                                <option value="w" @if($me->object->duration_unit === "w") selected @endif >Semana
                                </option>
                                <option value="m" @if($me->object->duration_unit === "m") selected @endif >Mes</option>
                                <option value="y" @if($me->object->duration_unit === "y") selected @endif >A&ntilde;o
                                </option>
                            </select>
                            <!-- JS CODE TO ENABLE select2 for id = duration_unit -->
                            <script>
                                $(document).ready(function () {
                                    $('#duration_unit').select2({
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
                        </td>
                    </tr>
                    <tr>
                        <td class="tdtop">Descripci&oacute;n</td>
                        <td>
                            <textarea id="comment" name="comment" rows="15" style="margin-top: 5px; width: 90%" class="flat ">{!! $me->object->note !!}</textarea>
                            <!-- Output ckeditor $disallowAnyContent=1 toolbarname=dolibarr_notes -->
                            <script nonce="da1ab1e8" type="text/javascript">
                                $(document).ready(function () {
                                    /* console.log("Run ckeditor"); */
                                    /* if (CKEDITOR.loadFullCore) CKEDITOR.loadFullCore(); */
                                    /* should be editor=CKEDITOR.replace but what if there is several editors ? */
                                    tmpeditor = CKEDITOR.replace('comment',
                                        {
                                            /* property:xxx is same than CKEDITOR.config.property = xxx */
                                            customConfig: ckeditorConfig,
                                            removePlugins: 'elementspath,save,flash,div,anchor,specialchar,wsc,exportpdf,scayt',
                                            versionCheck: false,
                                            readOnly: false,
                                            htmlEncodeOutput: false,
                                            allowedContent: false,		/* Advanced Content Filter (ACF) is own when allowedContent is false */
                                            extraAllowedContent: 'a[target];div{float,display}',				/* Add the style float and display into div to default other allowed tags */
                                            disallowedContent: '',		/* Tags that are not allowed */
                                            fullPage: false,						/* if true, the html, header and body tags are kept */
                                            toolbar: 'dolibarr_notes',
                                            toolbarStartupExpanded: false,
                                            width: '',
                                            height: 200,
                                            skin: 'moono-lisa',

                                            language: 'es_ES',
                                            textDirection: 'ltr',
                                            on: {
                                                instanceReady: function (ev) {
                                                    console.log("ckeditor instanceReady");
                                                    // Output paragraphs as <p>Text</p>.
                                                    this.dataProcessor.writer.setRules('p', {
                                                        indent: false,
                                                        breakBeforeOpen: true,
                                                        breakAfterOpen: false,
                                                        breakBeforeClose: false,
                                                        breakAfterClose: true
                                                    });
                                                },
                                                /* This is to remove the tab Link on image popup. Does not work, so commented */
                                                /*
                                                dialogDefinition: function (event) {
                                                    var dialogName = event.data.name;
                                                    var dialogDefinition = event.data.definition;
                                                    if (dialogName == 'image') {
                                                        dialogDefinition.removeContents('Link');
                                                    }
                                                }
                                                */
                                            },
                                            disableNativeSpellChecker: true,
                                            filebrowserBrowseUrl: ckeditorFilebrowserBrowseUrl,
                                            filebrowserImageBrowseUrl: ckeditorFilebrowserImageBrowseUrl,
                                            filebrowserWindowWidth: '900',
                                            filebrowserWindowHeight: '500',
                                            filebrowserImageWindowWidth: '900',
                                            filebrowserImageWindowHeight: '500'
                                        })
                                });
                            </script>
                    <tr>
                        <td class="tdtop">Email de bienvenida</td>
                        <td>
                            <textarea id="mail_valid" name="mail_valid" rows="15" style="margin-top: 5px; width: 90%" class="flat ">{!! $me->object->mail_valid !!}</textarea>
                            <!-- Output ckeditor $disallowAnyContent=1 toolbarname=dolibarr_notes -->
                            <script nonce="da1ab1e8" type="text/javascript">
                                $(document).ready(function () {
                                    /* console.log("Run ckeditor"); */
                                    /* if (CKEDITOR.loadFullCore) CKEDITOR.loadFullCore(); */
                                    /* should be editor=CKEDITOR.replace but what if there is several editors ? */
                                    tmpeditor = CKEDITOR.replace('mail_valid',
                                        {
                                            /* property:xxx is same than CKEDITOR.config.property = xxx */
                                            customConfig: ckeditorConfig,
                                            removePlugins: 'elementspath,save,flash,div,anchor,specialchar,wsc,exportpdf,scayt',
                                            versionCheck: false,
                                            readOnly: false,
                                            htmlEncodeOutput: false,
                                            allowedContent: false,		/* Advanced Content Filter (ACF) is own when allowedContent is false */
                                            extraAllowedContent: 'a[target];div{float,display}',				/* Add the style float and display into div to default other allowed tags */
                                            disallowedContent: '',		/* Tags that are not allowed */
                                            fullPage: false,						/* if true, the html, header and body tags are kept */
                                            toolbar: 'dolibarr_notes',
                                            toolbarStartupExpanded: false,
                                            width: '',
                                            height: 250,
                                            skin: 'moono-lisa',

                                            language: 'es_ES',
                                            textDirection: 'ltr',
                                            on: {
                                                instanceReady: function (ev) {
                                                    console.log("ckeditor instanceReady");
                                                    // Output paragraphs as <p>Text</p>.
                                                    this.dataProcessor.writer.setRules('p', {
                                                        indent: false,
                                                        breakBeforeOpen: true,
                                                        breakAfterOpen: false,
                                                        breakBeforeClose: false,
                                                        breakAfterClose: true
                                                    });
                                                },
                                                /* This is to remove the tab Link on image popup. Does not work, so commented */
                                                /*
                                                dialogDefinition: function (event) {
                                                    var dialogName = event.data.name;
                                                    var dialogDefinition = event.data.definition;
                                                    if (dialogName == 'image') {
                                                        dialogDefinition.removeContents('Link');
                                                    }
                                                }
                                                */
                                            },
                                            disableNativeSpellChecker: true,
                                            filebrowserBrowseUrl: ckeditorFilebrowserBrowseUrl,
                                            filebrowserImageBrowseUrl: ckeditorFilebrowserImageBrowseUrl,
                                            filebrowserWindowWidth: '900',
                                            filebrowserWindowHeight: '500',
                                            filebrowserImageWindowWidth: '900',
                                            filebrowserImageWindowHeight: '500'
                                        })
                                });
                            </script>
                        </td>
                    </tr><!-- BEGIN PHP TEMPLATE extrafields_add.tpl.php -->
                    <!-- END PHP TEMPLATE extrafields_add.tpl.php -->
                    <tbody>
                </table>
            </div>
            <div class="center">
                <button type="submit" class="button button-save" name="action" value="save">Grabar</button>
                <button type="submit" class="button button-cancel" name="action" value="cancel">Anular</button>
            </div>
        </form>
    </div> <!-- End div class="fiche" -->
@endsection

@push('scripts')
    {{-- <script src="https://alixar/Templates/Lib/additional-script.js"></script> --}}
@endpush

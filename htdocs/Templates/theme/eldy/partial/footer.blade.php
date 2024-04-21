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
    phpdebugbar.addIndicator("php_version", new PhpDebugBar.DebugBar.Indicator({
        "icon": "code",
        "tooltip": "PHP Version"
    }), "right");
    phpdebugbar.addTab("messages", new PhpDebugBar.DebugBar.Tab({
        "icon": "list-alt",
        "title": "Messages",
        "widget": new PhpDebugBar.Widgets.MessagesWidget()
    }));
    phpdebugbar.addTab("request", new PhpDebugBar.DebugBar.Tab({
        "icon": "tags",
        "title": "Request",
        "widget": new PhpDebugBar.Widgets.VariableListWidget()
    }));
    phpdebugbar.addIndicator("time", new PhpDebugBar.DebugBar.Indicator({
        "icon": "clock-o",
        "tooltip": "Request Duration"
    }), "right");
    phpdebugbar.addTab("timeline", new PhpDebugBar.DebugBar.Tab({
        "icon": "tasks",
        "title": "Timeline",
        "widget": new PhpDebugBar.Widgets.TimelineWidget()
    }));
    phpdebugbar.addIndicator("memory", new PhpDebugBar.DebugBar.Indicator({
        "icon": "cogs",
        "tooltip": "Memory Usage"
    }), "right");
    phpdebugbar.addTab("exceptions", new PhpDebugBar.DebugBar.Tab({
        "icon": "bug",
        "title": "Exceptions",
        "widget": new PhpDebugBar.Widgets.ExceptionsWidget()
    }));
    phpdebugbar.addTab("Error handler", new PhpDebugBar.DebugBar.Tab({
        "icon": "list",
        "title": "Error handler",
        "widget": new PhpDebugBar.Widgets.MessagesWidget()
    }));
    phpdebugbar.addTab("DolSQL", new PhpDebugBar.DebugBar.Tab({
        "icon": "arrow-right",
        "title": "DolSQL",
        "widget": new PhpDebugBar.Widgets.SQLQueriesWidget()
    }));
    phpdebugbar.addTab("DolLogs", new PhpDebugBar.DebugBar.Tab({
        "icon": "list-alt",
        "title": "DolLogs",
        "widget": new PhpDebugBar.Widgets.MessagesWidget()
    }));
    phpdebugbar.addTab("database", new PhpDebugBar.DebugBar.Tab({
        "icon": "database",
        "title": "Database",
        "widget": new PhpDebugBar.Widgets.SQLQueriesWidget()
    }));
    phpdebugbar.setDataMap({
        "php_version": ["php.version",],
        "messages": ["messages.messages", []],
        "messages:badge": ["messages.count", null],
        "request": ["request", {}],
        "time": ["time.duration_str", '0ms'],
        "timeline": ["time", {}],
        "memory": ["memory.peak_usage_str", '0B'],
        "exceptions": ["exceptions.exceptions", []],
        "exceptions:badge": ["exceptions.count", null],
        "Error handler": ["Error handler.messages", []],
        "Error handler:badge": ["Error handler.count", null],
        "DolSQL": ["query", []],
        "DolSQL:badge": ["query.nb_statements", 0],
        "DolLogs": ["logs.messages", []],
        "DolLogs:badge": ["logs.count", null],
        "database": ["pdo", []],
        "database:badge": ["pdo.nb_statements", 0]
    });
    phpdebugbar.restoreState();
    phpdebugbar.ajaxHandler = new PhpDebugBar.AjaxHandler(phpdebugbar, undefined, true);
    phpdebugbar.ajaxHandler.bindToXHR();
    phpdebugbar.addDataSet({
        "__meta": {
            "id": "X6fdc61053c01bd8030d358bcad3b7129",
            "datetime": "2024-04-21 07:47:29",
            "utime": 1713685649.46548,
            "method": "POST",
            "uri": "\/adherents\/type.php?url_route=adherents&url_filename=type&leftmenu=setup&mainmenu=members",
            "ip": "127.0.0.1"
        },
        "php": {"version": "8.2.18", "interface": "fpm-fcgi"},
        "messages": {"count": 0, "messages": []},
        "request": {
            "$_GET": "array:4 [\n  \"url_route\" => \"adherents\"\n  \"url_filename\" => \"type\"\n  \"leftmenu\" => \"setup\"\n  \"mainmenu\" => \"members\"\n]",
            "$_POST": "array:18 [\n  \"token\" => \"620b623c33d2f0005e3bf280288fe1b7\"\n  \"actionlogin\" => \"login\"\n  \"loginfunction\" => \"loginfunction\"\n  \"backtopage\" => \"\"\n  \"tz\" => \"1\"\n  \"tz_string\" => \"Europe\/Madrid\"\n  \"dst_observed\" => \"1\"\n  \"dst_first\" => \"2024-03-31T01:59:00Z\"\n  \"dst_second\" => \"2024-10-27T02:59:00Z\"\n  \"screenwidth\" => \"1912\"\n  \"screenheight\" => \"918\"\n  \"dol_hide_topmenu\" => \"0\"\n  \"dol_hide_leftmenu\" => \"0\"\n  \"dol_optimize_smallscreen\" => \"0\"\n  \"dol_no_mouse_hover\" => \"0\"\n  \"dol_use_jmobile\" => \"0\"\n  \"username\" => \"rsanjose\"\n  \"password\" => \"dFelguera*!\"\n]",
            "$_SESSION": "array:21 [\n  \"newtoken\" => \"620b623c33d2f0005e3bf280288fe1b7\"\n  \"dol_loginmesg\" => \"\"\n  \"idmenu\" => \"\"\n  \"token\" => \"620b623c33d2f0005e3bf280288fe1b7\"\n  \"dol_login\" => \"rsanjose\"\n  \"dol_logindate\" => 1713684527\n  \"dol_authmode\" => \"dolibarr\"\n  \"dol_tz\" => \"1\"\n  \"dol_tz_string\" => \"Europe\/Madrid\"\n  \"dol_dst\" => 1\n  \"dol_dst_observed\" => 1\n  \"dol_dst_first\" => \"2024-03-31T01:59:00Z\"\n  \"dol_dst_second\" => \"2024-10-27T02:59:00Z\"\n  \"dol_screenwidth\" => \"1912\"\n  \"dol_screenheight\" => \"918\"\n  \"dol_company\" => \"rSanjoSEO\"\n  \"dol_entity\" => 1\n  \"mainmenu\" => \"members\"\n  \"leftmenuopened\" => \"setup\"\n  \"leftmenu\" => \"setup\"\n  \"PHPDEBUGBAR_STACK_DATA\" => []\n]",
            "$_COOKIE": "array:1 [\n  \"DOLSESSID_d99c606e02ffcf8ae7835b2e67d2f38efee0afc5\" => \"io3bmh4vhfe8p7jri6ho35e4jp\"\n]"
        },
        "time": {
            "start": 1713685649.336035,
            "end": 1713685649.466831,
            "duration": 0.13079595565795898,
            "duration_str": "131ms",
            "measures": [{
                "label": "Debug DebugTool Constructor",
                "start": 1713685649.384491,
                "relative_start": 0.04845595359802246,
                "end": 1713685649.385163,
                "relative_end": 1713685649.385163,
                "duration": 0.0006721019744873047,
                "duration_str": "672\u03bcs",
                "memory": 0,
                "memory_str": "0B",
                "params": [],
                "collector": null
            }, {
                "label": "Page generation (after environment init)",
                "start": 1713685649.409184,
                "relative_start": 0.07314896583557129,
                "end": 1713685649.465108,
                "relative_end": 1713685649.465108,
                "duration": 0.0559239387512207,
                "duration_str": "55.92ms",
                "memory": 0,
                "memory_str": "0B",
                "params": [],
                "collector": null
            }]
        },
        "memory": {"peak_usage": 20910672, "peak_usage_str": "20MB"},
        "exceptions": {"count": 0, "exceptions": []},
        "Error handler": {
            "count": 2,
            "messages": [{
                "message": "explode(): Passing null to parameter #2 ($string) of type string is deprecated (\/srv\/http\/alixar\/htdocs\/core\/class\/translate.class.php:313)",
                "message_html": null,
                "is_string": true,
                "label": "DEPRECATED",
                "time": 1713685649.433929
            }, {
                "message": "strtolower(): Passing null to parameter #1 ($string) of type string is deprecated (\/srv\/http\/alixar\/htdocs\/core\/class\/translate.class.php:317)",
                "message_html": null,
                "is_string": true,
                "label": "DEPRECATED",
                "time": 1713685649.433935
            }]
        },
        "query": {
            "nb_statements": 12,
            "nb_failed_statements": 0,
            "accumulated_duration": 0.005349874496459961,
            "memory_usage": 0,
            "statements": [{
                "sql": "SELECT name as name, value as value, entity FROM alx_const WHERE entity IN (0,1) ORDER BY entity",
                "duration": 0.0007641315460205078,
                "duration_str": 0.76,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT u.rowid, u.lastname, u.firstname, u.employee, u.gender, u.civility as civility_code, u.birth, u.job, u.email, u.email_oauth2, u.personal_email, u.socialnetworks, u.signature, u.office_phone, u.office_fax, u.user_mobile, u.personal_mobile, u.address, u.zip, u.town, u.fk_state as state_id, u.fk_country as country_id, u.admin, u.login, u.note_private, u.note_public, u.pass, u.pass_crypted, u.pass_temp, u.api_key, u.fk_soc, u.fk_socpeople, u.fk_member, u.fk_user, u.ldap_sid, u.fk_user_expense_validator, u.fk_user_holiday_validator, u.statut as status, u.lang, u.entity, u.datec as datec, u.tms as datem, u.datelastlogin as datel, u.datepreviouslogin as datep, u.flagdelsessionsbefore, u.iplastlogin, u.ippreviouslogin, u.datelastpassvalidation, u.datestartvalidity, u.dateendvalidity, u.photo as photo, u.openid as openid, u.accountancy_code, u.thm, u.tjm, u.salary, u.salaryextra, u.weeklyhours, u.color, u.dateemployment, u.dateemploymentend, u.fk_warehouse, u.ref_ext, u.default_range, u.default_c_exp_tax_cat, u.national_registration_number, u.ref_employee, c.code as country_code, c.label as country, d.code_departement as state_code, d.nom as state FROM alx_user as u LEFT JOIN alx_c_country as c ON u.fk_country = c.rowid LEFT JOIN alx_c_departements as d ON u.fk_state = d.rowid WHERE u.entity IN (0, 1) AND u.login = 'rsanjose' ORDER BY u.entity ASC",
                "duration": 0.0004968643188476562,
                "duration_str": 0.5,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help, css, cssview, csslist FROM alx_extrafields WHERE elementtype = 'user' ORDER BY pos",
                "duration": 0.0002810955047607422,
                "duration_str": 0.28,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT param, value FROM alx_user_param WHERE fk_user = 1 AND entity = 1",
                "duration": 0.0001900196075439453,
                "duration_str": 0.19,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid,type,user_id,page,param,value FROM alx_default_values as t WHERE 1 = 1 AND ((t.user_id IN (0,1)) AND (entity IN (0,1)))",
                "duration": 0.0001881122589111328,
                "duration_str": 0.19,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT DISTINCT r.module, r.perms, r.subperms FROM alx_user_rights as ur, alx_rights_def as r WHERE r.id = ur.fk_id AND r.entity = 1 AND ur.entity = 1 AND ur.fk_user= 1 AND r.perms IS NOT NULL AND r.perms NOT LIKE '%_advance'",
                "duration": 0.0007817745208740234,
                "duration_str": 0.78,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT DISTINCT r.module, r.perms, r.subperms FROM alx_usergroup_rights as gr, alx_usergroup_user as gu, alx_rights_def as r WHERE r.id = gr.fk_id AND gr.entity = 1 AND gu.entity IN (0,1) AND r.entity = 1 AND gr.fk_usergroup = gu.fk_usergroup AND gu.fk_user = 1 AND r.perms IS NOT NULL",
                "duration": 0.00030803680419921875,
                "duration_str": 0.31,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT transkey, transvalue FROM alx_overwrite_trans where (lang='es_ES' OR lang IS NULL) AND entity IN (0, 0,1) ORDER BY lang DESC",
                "duration": 0.00025391578674316406,
                "duration_str": 0.25,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre, m.prefix, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position FROM alx_menu as m WHERE m.entity IN (0,1) AND m.menu_handler IN ('eldy','all') AND m.usertype IN (0,2) ORDER BY m.type DESC, m.position, m.rowid",
                "duration": 0.0007028579711914062,
                "duration_str": 0.7,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help, css, cssview, csslist FROM alx_extrafields WHERE elementtype = 'adherent_type' ORDER BY pos",
                "duration": 0.0004420280456542969,
                "duration_str": 0.44,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT rowid, title, url, target FROM alx_bookmark WHERE (fk_user = 1 OR fk_user is NULL OR fk_user = 0) AND entity IN (1) ORDER BY position",
                "duration": 0.0004279613494873047,
                "duration_str": 0.43,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }, {
                "sql": "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration FROM alx_adherent_type as d WHERE d.entity IN (1)",
                "duration": 0.0005130767822265625,
                "duration_str": 0.51,
                "memory": 0,
                "is_success": true,
                "error_code": null,
                "error_message": null
            }]
        },
        "logs": {
            "count": 9,
            "messages": [{
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT DISTINCT r.module, r.perms, r.subperms FROM alx_usergroup_rights as gr, alx_usergroup_user as gu, alx_rights_def as r WHERE r.id = gr.fk_id AND gr.entity = 1 AND gu.entity IN (0,1) AND r.entity = 1 AND gr.fk_usergroup = gu.fk_usergroup AND gu.fk_user = 1 AND r.perms IS NOT NULL",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.466963,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 NOTICE --- Access to POST \/adherents\/type.php - action=, massaction=",
                "message_html": null,
                "is_string": false,
                "label": "notice",
                "time": 1713685649.466971,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT transkey, transvalue FROM alx_overwrite_trans where (lang='es_ES' OR lang IS NULL) AND entity IN (0, 0,1) ORDER BY lang DESC",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.46698,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre, m.prefix, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position FROM alx_menu as m WHERE m.entity IN (0,1) AND m.menu_handler IN ('eldy','all') AND m.usertype IN (0,2) ORDER BY m.type DESC, m.position, m.rowid",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.466989,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 WARN Error: Translate::loadFromDatabase was called but language was not set yet with langs->setDefaultLang(). Nothing will be loaded.",
                "message_html": null,
                "is_string": false,
                "label": "warning",
                "time": 1713685649.466998,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 WARN Error: Translate::load was called for domain=members but language was not set yet with langs->setDefaultLang(). Nothing will be loaded.",
                "message_html": null,
                "is_string": false,
                "label": "warning",
                "time": 1713685649.467005,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help, css, cssview, csslist FROM alx_extrafields WHERE elementtype = 'adherent_type' ORDER BY pos",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.467012,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT rowid, title, url, target FROM alx_bookmark WHERE (fk_user = 1 OR fk_user is NULL OR fk_user = 0) AND entity IN (1) ORDER BY position",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.46702,
                "xdebug_link": null
            }, {
                "message": "2024-04-21 07:47:29 DEBUG sql=SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration FROM alx_adherent_type as d WHERE d.entity IN (1)",
                "message_html": null,
                "is_string": false,
                "label": "debug",
                "time": 1713685649.467028,
                "xdebug_link": null
            }]
        },
        "pdo": {
            "nb_statements": 0,
            "nb_failed_statements": 0,
            "accumulated_duration": 0,
            "memory_usage": 0,
            "peak_memory_usage": 0,
            "statements": [],
            "accumulated_duration_str": "0\u03bcs",
            "memory_usage_str": "0B",
            "peak_memory_usage_str": "0B"
        }
    }, "X6fdc61053c01bd8030d358bcad3b7129");

</script>

<!-- JS CODE TO ENABLE select2 for id searchselectcombo -->
<script nonce="97094dfb">
    $(document).ready(function () {
        var data = [{
            "id": "searchintomember",
            "text": "<span class=\"fas fa-user-alt  em092 infobox-adherent pictofixedwidth\" style=\"\"><\/span> Miembros",
            "url": "https:\/\/alixar\/adherents\/list.php"
        }, {
            "id": "searchintothirdparty",
            "text": "<span class=\"fas fa-building pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Terceros",
            "url": "https:\/\/alixar\/societe\/list.php"
        }, {
            "id": "searchintocontact",
            "text": "<span class=\"fas fa-address-book pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Contactos",
            "url": "https:\/\/alixar\/contact\/list.php"
        }, {
            "id": "searchintoproduct",
            "text": "<span class=\"fas fa-cube pictofixedwidth\" style=\" color: #a69944;\"><\/span> Productos o servicios",
            "url": "https:\/\/alixar\/product\/list.php"
        }, {
            "id": "searchintobatch",
            "text": "<span class=\"fas fa-barcode pictofixedwidth\" style=\" color: #a69944;\"><\/span> Lotes \/ Series",
            "url": "https:\/\/alixar\/product\/stock\/productlot_list.php"
        }, {
            "id": "searchintomo",
            "text": "<span class=\"fas fa-cubes pictofixedwidth\" style=\" color: #a69944;\"><\/span> &Oacute;rdenes de fabricaci&oacute;n",
            "url": "https:\/\/alixar\/mrp\/mo_list.php"
        }, {
            "id": "searchintoprojects",
            "text": "<span class=\"fas fa-project-diagram  em088 infobox-project pictofixedwidth\" style=\"\"><\/span> Proyectos",
            "url": "https:\/\/alixar\/projet\/list.php"
        }, {
            "id": "searchintotasks",
            "text": "<span class=\"fas fa-tasks infobox-project pictofixedwidth\" style=\"\"><\/span> Tareas",
            "url": "https:\/\/alixar\/projet\/tasks\/list.php"
        }, {
            "id": "searchintopropal",
            "text": "<span class=\"fas fa-file-signature infobox-propal pictofixedwidth\" style=\"\"><\/span> Presupuestos",
            "url": "https:\/\/alixar\/comm\/propal\/list.php"
        }, {
            "id": "searchintoorder",
            "text": "<span class=\"fas fa-file-invoice infobox-commande pictofixedwidth\" style=\"\"><\/span> Pedidos",
            "url": "https:\/\/alixar\/commande\/list.php"
        }, {
            "id": "searchintoshipment",
            "text": "<span class=\"fas fa-dolly  em092 infobox-commande pictofixedwidth\" style=\"\"><\/span> Env&iacute;os a clientes",
            "url": "https:\/\/alixar\/expedition\/list.php"
        }, {
            "id": "searchintoinvoice",
            "text": "<span class=\"fas fa-file-invoice-dollar infobox-commande pictofixedwidth\" style=\"\"><\/span> Facturas a clientes",
            "url": "https:\/\/alixar\/compta\/facture\/list.php"
        }, {
            "id": "searchintosupplierpropal",
            "text": "<span class=\"fas fa-file-signature infobox-supplier_proposal pictofixedwidth\" style=\"\"><\/span> Presupuestos de proveedor",
            "url": "https:\/\/alixar\/supplier_proposal\/list.php"
        }, {
            "id": "searchintosupplierorder",
            "text": "<span class=\"fas fa-dol-order_supplier infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Pedidos a proveedor",
            "url": "https:\/\/alixar\/fourn\/commande\/list.php"
        }, {
            "id": "searchintosupplierinvoice",
            "text": "<span class=\"fas fa-file-invoice-dollar infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Facturas proveedor",
            "url": "https:\/\/alixar\/fourn\/facture\/list.php"
        }, {
            "id": "searchintocontract",
            "text": "<span class=\"fas fa-suitcase  em092 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Contratos",
            "url": "https:\/\/alixar\/contrat\/list.php"
        }, {
            "id": "searchintointervention",
            "text": "<span class=\"fas fa-ambulance  em080 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Intervenciones",
            "url": "https:\/\/alixar\/fichinter\/list.php"
        }, {
            "id": "searchintoknowledgemanagement",
            "text": "<span class=\"fas fa-ticket-alt infobox-contrat rotate90 pictofixedwidth\" style=\"\"><\/span> Base de Conocimientos",
            "url": "https:\/\/alixar\/knowledgemanagement\/knowledgerecord_list.php?mainmenu=ticket"
        }, {
            "id": "searchintotickets",
            "text": "<span class=\"fas fa-ticket-alt infobox-contrat pictofixedwidth\" style=\"\"><\/span> Tickets",
            "url": "https:\/\/alixar\/ticket\/list.php?mainmenu=ticket"
        }, {
            "id": "searchintocustomerpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos de clientes",
            "url": "https:\/\/alixar\/compta\/paiement\/list.php?leftmenu=customers_bills_payment"
        }, {
            "id": "searchintovendorpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos a proveedor",
            "url": "https:\/\/alixar\/fourn\/paiement\/list.php?leftmenu=suppliers_bills_payment"
        }, {
            "id": "searchintomiscpayments",
            "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos varios",
            "url": "https:\/\/alixar\/compta\/bank\/various_payment\/list.php?leftmenu=tax_various"
        }, {
            "id": "searchintouser",
            "text": "<span class=\"fas fa-user infobox-adherent pictofixedwidth\" style=\"\"><\/span> Usuarios",
            "url": "https:\/\/alixar\/user\/list.php"
        }, {
            "id": "searchintoexpensereport",
            "text": "<span class=\"fas fa-wallet infobox-expensereport pictofixedwidth\" style=\"\"><\/span> Informes de gastos",
            "url": "https:\/\/alixar\/expensereport\/list.php?mainmenu=hrm"
        }, {
            "id": "searchintoleaves",
            "text": "<span class=\"fas fa-umbrella-beach  em088 infobox-holiday pictofixedwidth\" style=\"\"><\/span> D&iacute;a libre",
            "url": "https:\/\/alixar\/holiday\/list.php?mainmenu=hrm"
        }];

        var saveRemoteData = {
            "searchintomember": {
                "position": 8,
                "shortcut": "M",
                "img": "object_member",
                "label": "Miembros",
                "text": "<span class=\"fas fa-user-alt  em092 infobox-adherent pictofixedwidth\" style=\"\"><\/span> Miembros",
                "url": "https:\/\/alixar\/adherents\/list.php"
            },
            "searchintothirdparty": {
                "position": 10,
                "shortcut": "T",
                "img": "object_company",
                "label": "Terceros",
                "text": "<span class=\"fas fa-building pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Terceros",
                "url": "https:\/\/alixar\/societe\/list.php"
            },
            "searchintocontact": {
                "position": 15,
                "shortcut": "A",
                "img": "object_contact",
                "label": "Contactos",
                "text": "<span class=\"fas fa-address-book pictofixedwidth\" style=\" color: #6c6aa8;\"><\/span> Contactos",
                "url": "https:\/\/alixar\/contact\/list.php"
            },
            "searchintoproduct": {
                "position": 30,
                "shortcut": "P",
                "img": "object_product",
                "label": "Productos o servicios",
                "text": "<span class=\"fas fa-cube pictofixedwidth\" style=\" color: #a69944;\"><\/span> Productos o servicios",
                "url": "https:\/\/alixar\/product\/list.php"
            },
            "searchintobatch": {
                "position": 32,
                "shortcut": "B",
                "img": "object_lot",
                "label": "Lotes \/ Series",
                "text": "<span class=\"fas fa-barcode pictofixedwidth\" style=\" color: #a69944;\"><\/span> Lotes \/ Series",
                "url": "https:\/\/alixar\/product\/stock\/productlot_list.php"
            },
            "searchintomo": {
                "position": 35,
                "shortcut": "",
                "img": "object_mrp",
                "label": "&Oacute;rdenes de fabricaci&oacute;n",
                "text": "<span class=\"fas fa-cubes pictofixedwidth\" style=\" color: #a69944;\"><\/span> &Oacute;rdenes de fabricaci&oacute;n",
                "url": "https:\/\/alixar\/mrp\/mo_list.php"
            },
            "searchintoprojects": {
                "position": 40,
                "shortcut": "Q",
                "img": "object_project",
                "label": "Proyectos",
                "text": "<span class=\"fas fa-project-diagram  em088 infobox-project pictofixedwidth\" style=\"\"><\/span> Proyectos",
                "url": "https:\/\/alixar\/projet\/list.php"
            },
            "searchintotasks": {
                "position": 45,
                "img": "object_projecttask",
                "label": "Tareas",
                "text": "<span class=\"fas fa-tasks infobox-project pictofixedwidth\" style=\"\"><\/span> Tareas",
                "url": "https:\/\/alixar\/projet\/tasks\/list.php"
            },
            "searchintopropal": {
                "position": 60,
                "img": "object_propal",
                "label": "Presupuestos",
                "text": "<span class=\"fas fa-file-signature infobox-propal pictofixedwidth\" style=\"\"><\/span> Presupuestos",
                "url": "https:\/\/alixar\/comm\/propal\/list.php"
            },
            "searchintoorder": {
                "position": 70,
                "img": "object_order",
                "label": "Pedidos",
                "text": "<span class=\"fas fa-file-invoice infobox-commande pictofixedwidth\" style=\"\"><\/span> Pedidos",
                "url": "https:\/\/alixar\/commande\/list.php"
            },
            "searchintoshipment": {
                "position": 80,
                "img": "object_shipment",
                "label": "Env&iacute;os a clientes",
                "text": "<span class=\"fas fa-dolly  em092 infobox-commande pictofixedwidth\" style=\"\"><\/span> Env&iacute;os a clientes",
                "url": "https:\/\/alixar\/expedition\/list.php"
            },
            "searchintoinvoice": {
                "position": 90,
                "img": "object_bill",
                "label": "Facturas a clientes",
                "text": "<span class=\"fas fa-file-invoice-dollar infobox-commande pictofixedwidth\" style=\"\"><\/span> Facturas a clientes",
                "url": "https:\/\/alixar\/compta\/facture\/list.php"
            },
            "searchintosupplierpropal": {
                "position": 100,
                "img": "object_supplier_proposal",
                "label": "Presupuestos de proveedor",
                "text": "<span class=\"fas fa-file-signature infobox-supplier_proposal pictofixedwidth\" style=\"\"><\/span> Presupuestos de proveedor",
                "url": "https:\/\/alixar\/supplier_proposal\/list.php"
            },
            "searchintosupplierorder": {
                "position": 110,
                "img": "object_supplier_order",
                "label": "Pedidos a proveedor",
                "text": "<span class=\"fas fa-dol-order_supplier infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Pedidos a proveedor",
                "url": "https:\/\/alixar\/fourn\/commande\/list.php"
            },
            "searchintosupplierinvoice": {
                "position": 120,
                "img": "object_supplier_invoice",
                "label": "Facturas proveedor",
                "text": "<span class=\"fas fa-file-invoice-dollar infobox-order_supplier pictofixedwidth\" style=\"\"><\/span> Facturas proveedor",
                "url": "https:\/\/alixar\/fourn\/facture\/list.php"
            },
            "searchintocontract": {
                "position": 130,
                "img": "object_contract",
                "label": "Contratos",
                "text": "<span class=\"fas fa-suitcase  em092 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Contratos",
                "url": "https:\/\/alixar\/contrat\/list.php"
            },
            "searchintointervention": {
                "position": 140,
                "img": "object_intervention",
                "label": "Intervenciones",
                "text": "<span class=\"fas fa-ambulance  em080 infobox-contrat pictofixedwidth\" style=\"\"><\/span> Intervenciones",
                "url": "https:\/\/alixar\/fichinter\/list.php"
            },
            "searchintoknowledgemanagement": {
                "position": 145,
                "img": "object_knowledgemanagement",
                "label": "Base de Conocimientos",
                "text": "<span class=\"fas fa-ticket-alt infobox-contrat rotate90 pictofixedwidth\" style=\"\"><\/span> Base de Conocimientos",
                "url": "https:\/\/alixar\/knowledgemanagement\/knowledgerecord_list.php?mainmenu=ticket"
            },
            "searchintotickets": {
                "position": 146,
                "img": "object_ticket",
                "label": "Tickets",
                "text": "<span class=\"fas fa-ticket-alt infobox-contrat pictofixedwidth\" style=\"\"><\/span> Tickets",
                "url": "https:\/\/alixar\/ticket\/list.php?mainmenu=ticket"
            },
            "searchintocustomerpayments": {
                "position": 170,
                "img": "object_payment",
                "label": "Pagos de clientes",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos de clientes",
                "url": "https:\/\/alixar\/compta\/paiement\/list.php?leftmenu=customers_bills_payment"
            },
            "searchintovendorpayments": {
                "position": 175,
                "img": "object_payment",
                "label": "Pagos a proveedor",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos a proveedor",
                "url": "https:\/\/alixar\/fourn\/paiement\/list.php?leftmenu=suppliers_bills_payment"
            },
            "searchintomiscpayments": {
                "position": 180,
                "img": "object_payment",
                "label": "Pagos varios",
                "text": "<span class=\"fas fa-money-check-alt  em080 infobox-bank_account pictofixedwidth\" style=\"\"><\/span> Pagos varios",
                "url": "https:\/\/alixar\/compta\/bank\/various_payment\/list.php?leftmenu=tax_various"
            },
            "searchintouser": {
                "position": 200,
                "shortcut": "U",
                "img": "object_user",
                "label": "Usuarios",
                "text": "<span class=\"fas fa-user infobox-adherent pictofixedwidth\" style=\"\"><\/span> Usuarios",
                "url": "https:\/\/alixar\/user\/list.php"
            },
            "searchintoexpensereport": {
                "position": 210,
                "img": "object_trip",
                "label": "Informes de gastos",
                "text": "<span class=\"fas fa-wallet infobox-expensereport pictofixedwidth\" style=\"\"><\/span> Informes de gastos",
                "url": "https:\/\/alixar\/expensereport\/list.php?mainmenu=hrm"
            },
            "searchintoleaves": {
                "position": 220,
                "img": "object_holiday",
                "label": "D&iacute;a libre",
                "text": "<span class=\"fas fa-umbrella-beach  em088 infobox-holiday pictofixedwidth\" style=\"\"><\/span> D&iacute;a libre",
                "url": "https:\/\/alixar\/holiday\/list.php?mainmenu=hrm"
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
<script src="https://alixar/core/js/lib_foot.js.php?lang=es_ES&layout=classic&version=20.0.0-alpha"></script>

<!-- A div to allow dialog popup by jQuery('#dialogforpopup').dialog() -->
<div id="dialogforpopup" style="display: none;"></div>

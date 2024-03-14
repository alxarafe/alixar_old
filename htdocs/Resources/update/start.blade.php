@extends('master/install_layout')

@section('body')
    <tbody>
    <tr>
        <div>
            <table class="nobordernopadding @if ($force_install_noedit) hidewhennoedit @endif">
                <tr>
                    <td colspan="3" class="label">
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="Resources/img/octicons/build/svg/globe.svg" width="20" alt="webserver">{!! $lang->trans("WebServer") !!}
                        </h3>
                    </td>
                </tr>
                <tr>
                    <td class="label">
                        <label for="main_dir"><b>{!! $webPagesDirectory !!}</b></label></td>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_dir"
                               name="main_dir"
                               value="{!! $main_dir !!}"
                               @if (!empty($force_install_noedit)) disabled @endif
                        >
                    </td>
                    <td class="comment">
                        <span class="opacitymedium">{!! $lang->trans("WithNoSlashAtTheEnd") !!}</span><br>
                        {!! $lang->trans("Examples") !!}:<br>
                        <ul>
                            <li>/var/www/dolibarr/htdocs</li>
                            <li>C:/wwwroot/dolibarr/htdocs</li>
                        </ul>
                    </td>
                </tr>

                <!-- Documents URL $dolibarr_main_data_root -->
                <tr>
                    <td class="label">
                        <label for="main_data_dir"><b>{!! $lang->trans("DocumentsDirectory") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_data_dir"
                               name="main_data_dir"
                               value="{!! $main_data_dir !!}"
                               @if (!empty($force_install_noedit)) disabled @endif
                        >
                    </td>
                    <td class="comment">
                        '<span class="opacitymedium">{!! $lang->trans("WithNoSlashAtTheEnd") !!}</span><br>
                        {!! $lang->trans("DirectoryRecommendation") !!}<br>
                        {!! $lang->trans("Examples") !!}:<br>
                        <ul>
                            <li>/var/lib/dolibarr/documents</li>
                            <li>C:/My Documents/dolibarr/documents</li>
                        </ul>
                    </td>
                </tr>

                <!-- Root URL $dolibarr_main_url_root -->
                <tr>
                    <td class="label"><label for="main_url"><b>{!! $lang->trans("URLRoot") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_url"
                               name="main_url"
                               value="{!!  $main_url!!} "
                               @if (!empty($force_install_noedit)) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("Examples") !!}:<br>
                        <ul>
                            <li>http://localhost/</li>
                            <li>http://www.myserver.com:8180/dolibarr</li>
                            <li>https://www.myvirtualfordolibarr.com/</li>
                        </ul>
                    </td>
                </tr>

                @if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on')
                    <!--  // Enabled if the installation process is "https://" -->
                    <tr>
                        <td class="label">
                            <label for="main_force_https">{!! $lang->trans("ForceHttps") !!}</label></td>
                        <td class="label">
                            <input type="checkbox"
                                   id="main_force_https"
                                   name="main_force_https"
                                   @if (!empty($force_install_mainforcehttps)) checked @endif
                                   @if($force_install_noedit == 2 && $force_install_mainforcehttps !== null) disabled @endif
                            >
                        </td>
                        <td class="comment">{!! $lang->trans("CheckToForceHttps") !!}
                        </td>
                    </tr>
                @endif

                <!-- Dolibarr database -->
                <tr>
                    <td colspan="3" class="label"><br>
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="Resources/img/octicons/build/svg/database.svg" width="20" alt="webserver"> {!! $lang->trans("DolibarrDatabase") !!}
                        </h3>
                    </td>
                </tr>

                <tr>
                    <td class="label">
                        <label for="db_name"><b>{!! $lang->trans("DatabaseName") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_name"
                               name="db_name"
                               value="{!! $db_name ?? 'alixar' !!}"
                               @if ($force_install_noedit == 2 && $force_install_database !== null)  disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("DatabaseName") !!}</td>
                </tr>

                <tr>
                    <!-- Driver type -->
                    <td class="label">
                        <label for="db_type"><b>{!! $lang->trans("DriverType") !!}</b></label>
                    </td>
                    <td class="label">
                        <select id="db_type"
                                name="db_type"
                                @if ($force_install_noedit == 2 && $force_install_type !== null) disabled @endif
                        >
                            @foreach($db_types as $key => $db_type_element)
                                <option value="{!! $db_type_element['name'] !!}"
                                        @if($key === $db_type) selected @endif
                                        @if(!empty($db_type_element['comment'])) disabled @endif
                                >{!! $db_type_element['shortname'] . ' ' . $db_type_element['classname'] . ' ' . $db_type_element['min_version'] . ' ' . $db_type_element['comment'] !!}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="comment">{!! $lang->trans("DatabaseType") !!}</td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_host"><b>{!! $lang->trans("DatabaseServer") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_host"
                               name="db_host"
                               value="{!! $db_host ?? 'localhost' !!}"
                               @if ($force_install_noedit == 2 && $force_install_dbserver !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("ServerAddressDescription") !!}
                    </td>

                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_port">{!! $lang->trans("Port") !!}</label></td>
                    <td class="label">
                        <input type="text"
                               name="db_port"
                               id="db_port"
                               value="{!! $db_port !!}"
                               @if ($force_install_noedit == 2 && $force_install_port !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("ServerPortDescription") !!}
                    </td>

                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_prefix">{!! $lang->trans("DatabasePrefix") !!}</label></td>
                    <td class="label">
                        <input type="text"
                               id="db_prefix"
                               name="db_prefix"
                               value="{!! $db_prefix !!}"
                               @if ($force_install_noedit == 2 && $force_install_prefix !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("DatabasePrefixDescription") !!}</td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label">
                        <label for="db_create_database">{!! $lang->trans("CreateDatabase") !!}</label></td>
                    <td class="label">
                        <input type="checkbox"
                               id="db_create_database"
                               name="db_create_database"
                               value="on"
                               @if ($db_create_database) checked @endif
                               @if ($install_noedit) disabled @endif
                        >
                    </td>
                    <td class="comment">
                        {!! $lang->trans("CheckToCreateDatabase") !!}
                    </td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_user"><b>{!! $lang->trans("Login") !!}</b></label></td>
                    <td class="label">
                        <input type="text"
                               id="db_user"
                               name="db_user"
                               value="{!! $db_user !!}"
                               @if($force_install_noedit == 2 && $force_install_databaselogin !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("AdminLogin") !!}</td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_pass"><b>{!! $lang->trans("Password") !!}</b></label></td>
                    <td class="label">
                        <input type="password" class="text-security"
                               id="db_pass" autocomplete="off"
                               name="db_pass"
                               value="{!! $db_pass !!}"
                               @if($force_install_noedit == 2 && $force_install_databasepass !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("AdminPassword") !!}</td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_create_user">{!! $lang->trans("CreateUser") !!}</label>
                    </td>
                    <td class="label">
                        <input type="checkbox"
                               id="db_create_user"
                               name="db_create_user"
                               value="on"
                               @if ($db_create_user) checked @endif
                               @if($force_install_noedit == 2 && $force_install_createuser !== null) disabled @endif
                        >
                    </td>
                    <td class="comment">
                        {!! $lang->trans("CheckToCreateUser") !!}
                    </td>
                </tr>

                <!-- Super access -->
                <tr class="hidesqlite hideroot">
                    <td colspan="3" class="label"><br>
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="Resources/img/octicons/build/svg/shield.svg" width="20" alt="webserver"> {!! $lang->trans("DatabaseSuperUserAccess") !!}
                        </h3>
                    </td>
                </tr>

                <tr class="hidesqlite hideroot">
                    <td class="label"><label for="db_user_root"><b>{!! $lang->trans("Login") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_user_root"
                               name="db_user_root"
                               class="needroot"
                               value="{!! $db_user_root !!}"
                               @if ($force_install_noedit > 0 && !empty($force_install_databaserootlogin)) disabled @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("DatabaseRootLoginDescription") !!}
                        <!--
                        {!! '<br>' . $lang->trans("Examples") . ':<br>' !!}
                        <ul>
                            <li>root (Mysql)</li>
                            <li>postgres (PostgreSql)</li>
                        </ul>
                        -->
                    </td>
                </tr>
                <tr class="hidesqlite hideroot">
                    <td class="label"><label for="db_pass_root"><b>{!! $lang->trans("Password") !!}</b></label>
                    </td>
                    <td class="label">
                        <input type="password"
                               autocomplete="off"
                               id="db_pass_root"
                               name="db_pass_root"
                               class="needroot text-security"
                               value="{!! $db_pass_root !!}"
                               @if ($force_install_noedit > 0 && !empty($force_install_databaserootpass)) disabled /*
                        May be removed by javascript*/ @endif
                        >
                    </td>
                    <td class="comment">{!! $lang->trans("KeepEmptyIfNoPassword") !!}
                    </td>
                </tr>
            </table>
        </div>

        <script type="text/javascript">
            function init_needroot() {
                console.log("init_needroot force_install_noedit={!! $force_install_noedit !!}");
                console.log(jQuery("#db_create_database").is(":checked"));
                console.log(jQuery("#db_create_user").is(":checked"));

                if (jQuery("#db_create_database").is(":checked") || jQuery("#db_create_user").is(":checked")) {
                    console.log("init_needroot show root section");
                    jQuery(".hideroot").show();
                    <?php
                    if (empty($force_install_noedit)) { ?>
                    jQuery(".needroot").removeAttr('disabled');
                    <?php } ?>
                } else {
                    console.log("init_needroot hide root section");
                    jQuery(".hideroot").hide();
                    jQuery(".needroot").prop('disabled', true);
                }
            }

            function checkDatabaseName(databasename) {
                if (databasename.match(/[;\.]/)) {
                    return false;
                }
                return true;
            }

            function jscheckparam() {
                console.log("Click on jscheckparam");

                var ok = true;

                if (document.forminstall.main_dir.value == '') {
                    ok = false;
                    alert('{!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldRequired", $lang->transnoentitiesnoconv("WebPagesDirectory"))) !!}');
                } else if (document.forminstall.main_data_dir.value == '') {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldRequired", $lang->transnoentitiesnoconv("DocumentsDirectory"))) !!}');
                } else if (document.forminstall.main_url.value == '') {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldRequired", $lang->transnoentitiesnoconv("URLRoot"))) !!}');
                } else if (document.forminstall.db_host.value == '') {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldRequired", $lang->transnoentitiesnoconv("Server"))) !!}');
                } else if (document.forminstall.db_name.value == '') {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldRequired", $lang->transnoentitiesnoconv("DatabaseName"))) !!}');
                } else if (!checkDatabaseName(document.forminstall.db_name.value)) {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $lang->transnoentitiesnoconv("DatabaseName"))) !!}');
                }
                // If create database asked
                else if (document.forminstall.db_create_database.checked == true && (document.forminstall.db_user_root.value == '')) {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("YouAskToCreateDatabaseSoRootRequired")) !!}');
                    init_needroot();
                }
                // If create user asked
                else if (document.forminstall.db_create_user.checked == true && (document.forminstall.db_user_root.value == '')) {
                    ok = false;
                    alert(' {!! \Alxarafe\Lib\Functions::dol_escape_js($lang->transnoentities("YouAskToCreateDatabaseUserSoRootRequired")) !!}');
                    init_needroot();
                }

                return ok;
            }


            jQuery(document).ready(function () { // TODO Test $( window ).load(function() to see if the init_needroot work better after a back

                var dbtype = jQuery("#db_type");

                dbtype.change(function () {
                    if (dbtype.val() == 'sqlite' || dbtype.val() == 'sqlite3') {
                        jQuery(".hidesqlite").hide();
                    } else {
                        jQuery(".hidesqlite").show();
                    }

                    // Automatically set default database ports and admin user
                    if (dbtype.val() == 'mysql' || dbtype.val() == 'mysqli') {
                        jQuery("#db_port").val(3306);
                        jQuery("#db_user_root").val('root');
                    } else if (dbtype.val() == 'pgsql') {
                        jQuery("#db_port").val(5432);
                        jQuery("#db_user_root").val('postgres');
                    } else if (dbtype.val() == 'mssql') {
                        jQuery("#db_port").val(1433);
                        jQuery("#db_user_root").val('sa');
                    }

                });

                jQuery("#db_create_database").click(function () {
                    console.log("click on db_create_database");
                    init_needroot();
                });
                jQuery("#db_create_user").click(function () {
                    console.log("click on db_create_user");
                    init_needroot();
                });
                <?php if ($force_install_noedit == 2 && empty($force_install_databasepass)) { ?>
                jQuery("#db_pass").focus();
                <?php } ?>

                init_needroot();
            });
        </script>
    </tr>
    </tbody>
@endsection
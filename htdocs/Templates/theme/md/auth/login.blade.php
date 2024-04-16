<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,follow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Dolibarr Development Team">
    <meta name="anti-csrf-newtoken" content="ae9fe71b773b59934fcfc74d3caa2256">
    <meta name="anti-csrf-currenttoken" content="ae9fe71b773b59934fcfc74d3caa2256">
    <link rel="shortcut icon" type="image/x-icon" href="/theme/dolibarr_256x256_color.png"/>
    <link rel="manifest" href="/theme/md/manifest.json.php"/>
    <title>Dolibarr - Login @ 20.0.0-alpha</title>
    <!-- Includes CSS for JQuery (Ajax library) -->
    <link rel="stylesheet" type="text/css" href="/includes/jquery/css/base/jquery-ui.css?layout=classic&amp;version=20.0.0-alpha">
    <link rel="stylesheet" type="text/css" href="/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css?layout=classic&amp;version=20.0.0-alpha">
    <link rel="stylesheet" type="text/css" href="/includes/jquery/plugins/select2/dist/css/select2.css?layout=classic&amp;version=20.0.0-alpha">
    <!-- Includes CSS for font awesome -->
    <link rel="stylesheet" type="text/css" href="/theme/common/fontawesome-5/css/all.min.css?layout=classic&amp;version=20.0.0-alpha">
    <!-- Includes CSS for Dolibarr theme -->
    <link rel="stylesheet" type="text/css" href="/theme/md/style.css.php?lang=es_ES&amp;theme=md&amp;entity=1&amp;layout=classic&amp;version=20.0.0-alpha&amp;revision=76">
    <!-- Includes JS for JQuery -->
    <script nonce="f540c1f4" src="/includes/jquery/js/jquery.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="f540c1f4" src="/includes/jquery/js/jquery-ui.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="f540c1f4" src="/includes/jquery/plugins/jnotify/jquery.jnotify.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="f540c1f4" src="/includes/jquery/plugins/select2/dist/js/select2.full.min.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <script nonce="f540c1f4" src="/includes/jquery/plugins/multiselect/jquery.multi-select.js?layout=classic&amp;version=20.0.0-alpha"></script>
    <!-- Includes JS of Dolibarr -->
    <script nonce="f540c1f4" src="/core/js/lib_head.js.php?lang=es_ES&amp;layout=classic&amp;version=20.0.0-alpha"></script>
    <!-- Includes JS added by page -->
    <script nonce="f540c1f4" src="/core/js/dst.js?lang=es_ES"></script>
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

<!-- BEGIN PHP TEMPLATE LOGIN.TPL.PHP -->
<body class="body bodylogin">

<script>
    $(document).ready(function () {
        /* Set focus on correct field */
        $('#username').focus(); 		// Warning to use this only on visible element
    });
</script>

<div class="login_center center" style="background-size: cover; background-position: center center; background-attachment: fixed; background-repeat: no-repeat; background: linear-gradient(4deg, rgb(240,240,240) 52%, rgb(60,70,100) 52.1%);">
    <div class="login_vertical_align">


        <form id="login" name="login" method="post" action="/index.php?mainmenu=home">

            <input type="hidden" name="token" value="ae9fe71b773b59934fcfc74d3caa2256"/>
            <input type="hidden" name="actionlogin" value="login">
            <input type="hidden" name="loginfunction" value="loginfunction"/>
            <input type="hidden" name="backtopage" value=""/>
            <!-- Add fields to store and send local user information. This fields are filled by the core/js/dst.js -->
            <input type="hidden" name="tz" id="tz" value=""/>
            <input type="hidden" name="tz_string" id="tz_string" value=""/>
            <input type="hidden" name="dst_observed" id="dst_observed" value=""/>
            <input type="hidden" name="dst_first" id="dst_first" value=""/>
            <input type="hidden" name="dst_second" id="dst_second" value=""/>
            <input type="hidden" name="screenwidth" id="screenwidth" value=""/>
            <input type="hidden" name="screenheight" id="screenheight" value=""/>
            <input type="hidden" name="dol_hide_topmenu" id="dol_hide_topmenu" value="0"/>
            <input type="hidden" name="dol_hide_leftmenu" id="dol_hide_leftmenu" value="0"/>
            <input type="hidden" name="dol_optimize_smallscreen" id="dol_optimize_smallscreen" value="0"/>
            <input type="hidden" name="dol_no_mouse_hover" id="dol_no_mouse_hover" value="0"/>
            <input type="hidden" name="dol_use_jmobile" id="dol_use_jmobile" value="0"/>


            <!-- Title with version -->
            <div class="login_table_title center" title="Dolibarr 20.0.0-alpha">
                <a class="login_table_title" href="https://www.dolibarr.org" target="_blank" rel="noopener noreferrer external">Dolibarr
                    20.0.0-alpha</a></div>


            <div class="login_table">

                <div id="login_line1">

                    <div id="login_left">
                        <img alt="" src="/theme/dolibarr_logo.svg" id="img_logo"/>
                    </div>

                    <br>

                    <div id="login_right">

                        <div class="tagtable left centpercent" title="Introduzca los datos de inicio de sesi&oacute;n">

                            <!-- Login -->
                            <div class="trinputlogin">
                                <div class="tagtd nowraponall center valignmiddle tdinputlogin">
                                    <!-- <span class="span-icon-user">-->
                                    <span class="fa fa-user"></span>
                                    <input type="text" id="username" maxlength="255" placeholder="Login" name="username" class="flat input-icon-user minwidth150" value="" tabindex="1" autofocus="autofocus" autocapitalize="off" autocomplete="on" spellcheck="false" autocorrect="off"/>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="trinputlogin">
                                <div class="tagtd nowraponall center valignmiddle tdinputlogin">
                                    <!--<span class="span-icon-password">-->
                                    <span class="fa fa-key"></span>
                                    <input type="password" id="password" maxlength="128" placeholder="Contrase&ntilde;a" name="password" class="flat input-icon-password minwidth150" value="" tabindex="2" autocomplete="off"/>
                                </div>
                            </div>


                        </div>

                    </div> <!-- end div login_right -->

                </div> <!-- end div login_line1 -->


                <div id="login_line2" style="clear: both">


                    <!-- Button Connection -->
                    <br>
                    <div id="login-submit-wrapper">
                        <input type="submit" class="button" value="&nbsp; Conexi&oacute;n &nbsp;" tabindex="5"/>
                    </div>


                    <br>
                    <div class="center" style="margin-top: 5px;"><a class="alogin" href="/user/passwordforgotten.php">&iquest;Olvid&oacute;
                            su
                            contrase&ntilde;a?</a>&nbsp;-&nbsp;<a class="alogin" href="/support/index.php" target="_blank" rel="noopener noreferrer">&iquest;Necesita
                            ayuda?</a></div>
                </div> <!-- end login line 2 -->

            </div> <!-- end login table -->


        </form>


        <!-- authentication mode = dolibarr -->
        <!-- cookie name used for this session = DOLSESSID_85f3df31515619e11ebbf28fced3174cb49cd80c -->
        <!-- urlfrom in this session =  -->

        <!-- Common footer is not used for login page, this is same than footer but inside login tpl -->


    </div>
</div><!-- end of center -->


</body>
</html>
<!-- END PHP TEMPLATE -->

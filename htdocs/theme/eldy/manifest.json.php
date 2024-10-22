<?php

/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/theme/eldy/manifest.json.php
 *      \brief      File for The Web App (PWA)
 */

defineIfNotDefined('NOREQUIREUSER', '1');
defineIfNotDefined('NOREQUIRESOC', '1');
defineIfNotDefined('NOREQUIRETRAN', '1');
defineIfNotDefined('NOTOKENRENEWAL', '1');
defineIfNotDefined('NOLOGIN', '1');
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');
defineIfNotDefined('NOSESSION', '1');

require_once realpath(BASE_PATH.'/../Dolibarr/Lib/MainFunctions.php');

top_httphead('text/json');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=10800, public, must-revalidate');
    // For a text/json, we must set an Expires to avoid to have it forced to an expired value by the web server
    header('Expires: ' . gmdate('D, d M Y H:i:s', dol_now('gmt') + 10800) . ' GMT');
} else {
    header('Cache-Control: no-cache');
}


$manifest = new stdClass();

$manifest->manifest_version = 3;

$manifest->name = constant('DOL_APPLICATION_TITLE');
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
    $manifest->name = getDolGlobalString('MAIN_APPLICATION_TITLE');
}
$manifest->short_name = $manifest->name;


$manifest->theme_color = getDolGlobalString('MAIN_MANIFEST_APPLI_THEME_COLOR', getDolGlobalString('THEME_ELDY_TOPMENU_BACK1', '#F05F40'));
if (!preg_match('/#[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9]$/', $manifest->theme_color)) {
    include_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';
    $manifest->theme_color = '#' . colorArrayToHex(colorStringToArray($manifest->theme_color));
}
$manifest->background_color = getDolGlobalString('MAIN_MANIFEST_APPLI_BG_COLOR', "#ffffff");
if (!preg_match('/#[a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9][a-f0-9]$/', $manifest->background_color)) {
    include_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';
    $manifest->background_color = '#' . colorArrayToHex(colorStringToArray($manifest->background_color));
}
$manifest->display = getDolGlobalString('MAIN_MANIFEST_DISPLAY', "minimal-ui");
$manifest->splash_pages = null;
$manifest->icons = array();
$manifest->start_url = constant('BASE_URL');
$manifest->id = constant('BASE_URL');

if (getDolGlobalString('MAIN_MANIFEST_APPLI_LOGO_URL')) {
    $icon = new stdClass();
    $icon->src = getDolGlobalString('MAIN_MANIFEST_APPLI_LOGO_URL');
    if ($conf->global->MAIN_MANIFEST_APPLI_LOGO_URL_SIZE) {
        $icon->sizes = getDolGlobalString('MAIN_MANIFEST_APPLI_LOGO_URL_SIZE') . "x" . getDolGlobalString('MAIN_MANIFEST_APPLI_LOGO_URL_SIZE');
    } else {
        $icon->sizes = "512x512";
    }
    $icon->type = "image/png";
    $manifest->icons[] = $icon;
} elseif (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED')) {
    if (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI')) {
        $iconRelativePath = 'logos/thumbs/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI');
        $iconPath = $conf->mycompany->dir_output . '/' . $iconRelativePath;
        if (is_readable($iconPath)) {
            $imgSize = getimagesize($iconPath);
            if (!empty($imgSize)) {
                $icon = new stdClass();
                $icon->src = BASE_URL . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($iconRelativePath);
                $icon->sizes = $imgSize[0] . "x" . $imgSize[1];
                $icon->type = "image/png";
                $manifest->icons[] = $icon;
            }
        }
    }

    if (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL')) {
        $iconRelativePath = 'logos/thumbs/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL');
        $iconPath = $conf->mycompany->dir_output . '/' . $iconRelativePath;
        if (is_readable($iconPath)) {
            $imgSize = getimagesize($iconPath);
            if ($imgSize) {
                $icon = new stdClass();
                $icon->src = BASE_URL . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($iconRelativePath);
                $icon->sizes = $imgSize[0] . "x" . $imgSize[1];
                $icon->type = "image/png";
                $manifest->icons[] = $icon;
            }
        }
    }

    if (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED')) {
        $iconRelativePath = 'logos/' . getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED');
        $iconPath = $conf->mycompany->dir_output . '/' . $iconRelativePath;
        if (is_readable($iconPath)) {
            $imgSize = getimagesize($iconPath);
            if ($imgSize) {
                $icon = new stdClass();
                $icon->src = BASE_URL . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($iconRelativePath);
                $icon->sizes = $imgSize[0] . "x" . $imgSize[1];
                $icon->type = "image/png";
                $manifest->icons[] = $icon;
            }
        }
    }
}

// Add Dolibarr std icon
if (empty($manifest->icons)) {
    $icon = new stdClass();
    $icon->src = BASE_URL . '/theme/alixar_square_logo_256x256_color.png';
    $icon->sizes = "256x256";
    $icon->type = "image/png";
    $manifest->icons[] = $icon;
}


print json_encode($manifest);

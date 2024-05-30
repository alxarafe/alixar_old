<?php

/* Copyright (C) 2004-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Cedric GROSS            <c.gross@kreiz-it.fr>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024		MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliModules\Install\Lib;

use DoliCore\Base\Config;

abstract class Check
{
    public static function all(): array
    {
        $result = [];

        /**
         * If the browser is modern, nothing is notified
         */
        $data = static::browser();
        if ($data['status'] !== Status::OK) {
            $result[] = $data;
        }

        $result[] = static::minPhp();

        /**
         * Show the warning only if the PHP version is too modern.
         */
        $data = static::maxPhp();
        if ($data['status'] !== Status::OK) {
            $result[] = $data;
        }

        $result[] = static::getPostSupport();
        $result[] = static::sessionId();
        $result[] = static::mbStringExtension();
        $result[] = static::jsonExtension();
        $result[] = static::gdExtension();
        $result[] = static::curlExtension();
        $result[] = static::calendarExtension();
        $result[] = static::xmlExtension();
        $result[] = static::utfExtension();
        $result[] = static::intlExtension();
        $result[] = static::imapExtension();
        $result[] = static::zipExtension();
        $result[] = static::memory();

        return $result;

        /*

        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->langs->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }

        $value = $this->checkConfFile();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        $conffile = Globals::getConfFilename();
        if (!file_exists($conffile)) {
            $text = $this->langs->trans('YouMustCreateWithPermission', $conffile);
            $text .= '<br><br>';
            $text .= '<span class="opacitymedium">' . $this->langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</span>';

            $this->checks[] = [
                'icon' => 'error',
                'text' => $text,
            ];

            return false;
        }

        $value = $this->checkIfWritable();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->langs->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }
        */
    }

    public static function browser(): array
    {
        $langs = Config::getLangs();

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (empty($useragent)) {
            return [
                'status' => Status::FAIL,
                'text' => $langs->trans('WarningBrowserTooOld'),
            ];
        }

        $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
        $browsername = $tmp['browsername'];
        $browserversion = $tmp['browserversion'];
        if ($browsername == 'ie' && $browserversion < 7) {
            return [
                'status' => Status::WARNING,
                'text' => $langs->trans('WarningBrowserTooOld'),
            ];
        }

        return ['status' => Status::OK];
    }

    public static function minPhp(): array
    {
        $langs = Config::getLangs();

        $arrayphpminversionerror = [7, 0, 0];
        $arrayphpminversionwarning = [7, 1, 0];

        if (versioncompare(versionphparray(), $arrayphpminversionerror) < 0) {        // Minimum to use (error if lower)
            return [
                'status' => Status::FAIL,
                'text' => $langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionerror))
            ];
        }

        if (versioncompare(versionphparray(), $arrayphpminversionwarning) < 0) {    // Minimum supported (warning if lower)
            return [
                'status' => Status::WARNING,
                'text' => $langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionwarning)),
            ];
        }

        return [
            'status' => Status::OK,
            'text' => $langs->trans("PHPVersion") . " " . versiontostring(versionphparray()),
        ];
    }

    public static function maxPhp(): array
    {
        $langs = Config::getLangs();

        $arrayphpmaxversionwarning = [8, 2, 0];

        if (versioncompare(versionphparray(), $arrayphpmaxversionwarning) > 0 && versioncompare(versionphparray(), $arrayphpmaxversionwarning) < 3) {        // Maximum to use (warning if higher)
            return [
                'status' => Status::WARNING,
                'text' => $langs->trans("ErrorPHPVersionTooHigh", versiontostring($arrayphpmaxversionwarning)),
            ];
        }

        return ['status' => Status::OK];
    }

    public static function getPostSupport(): array
    {
        $langs = Config::getLangs();

        if (empty($_GET) || empty($_POST)) {   // We must keep $_GET and $_POST here
            return [
                'status' => Status::WARNING,
                'text' => $langs->trans("PHPSupportPOSTGETKo") . ' (<a href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?testget=ok">' . $langs->trans("Recheck") . '</a>)',
            ];
        }

        return [
            'status' => Status::OK,
            'text' => $langs->trans("PHPSupportPOSTGETOk"),
        ];
    }

    public static function sessionId(): array
    {
        return static::checkExtension('function_exists', 'session_id', 'PHPSupportSessions', 'ErrorPHPDoesNotSupportSessions');
    }

    private static function checkExtension(string $checkFunction, string $name, string $textIfOk, string $textIfError, $param = ''): array
    {
        $langs = Config::getLangs();

        if ($checkFunction($name)) {
            return [
                'status' => Status::OK,
                'text' => $langs->trans($textIfOk, $param),
            ];
        }

        return [
            'status' => Status::FAIL,
            'text' => $langs->trans($textIfError, $param),
        ];
    }

    public static function mbStringExtension(): array
    {
        return static::checkExtension('extension_loaded', 'mbstring', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'MBString');
    }

    public static function jsonExtension(): array
    {
        return static::checkExtension('extension_loaded', 'json', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'JSON', true);
    }

    public static function gdExtension(): array
    {
        return static::checkExtension('function_exists', 'imagecreate', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'GD');
    }

    public static function curlExtension(): array
    {
        return static::checkExtension('function_exists', 'curl_init', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'Curl');
    }

    public static function calendarExtension(): array
    {
        return static::checkExtension('function_exists', 'easter_date', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'Calendar');
    }

    public static function xmlExtension(): array
    {
        return static::checkExtension('function_exists', 'simplexml_load_string', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'XML');
    }

    public static function utfExtension(): array
    {
        return static::checkExtension('function_exists', 'utf8_encode', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'UTF8');
    }

    public static function intlExtension(): array
    {
        // TODO: We need this?
        if ($_SERVER['SERVER_ADMIN'] ?? '' === 'doliwamp@localhost') {
            return ['status' => Status::OK];
        }
        return static::checkExtension('function_exists', 'locale_get_primary_language', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'Intl');
    }

    public static function imapExtension(): array
    {
        return static::checkExtension('function_exists', 'imap_open', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'IMAP');
    }

    public static function zipExtension(): array
    {
        return static::checkExtension('class_exists', 'ZipArchive', 'PHPSupport', 'ErrorPHPDoesNotSupport', 'ZIP');
    }

    public static function memory(): array
    {
        $memmaxorig = @ini_get("memory_limit");
        if (empty($memmaxorig)) {
            return ['status' => Status::OK];
        }

        $memmax = $memmaxorig;
        $memrequiredorig = '64M';
        $memrequired = 64 * 1024 * 1024;
        preg_match('/([0-9]+)([a-zA-Z]*)/i', $memmax, $reg);
        if ($reg[2]) {
            if (strtoupper($reg[2]) == 'G') {
                $memmax = $reg[1] * 1024 * 1024 * 1024;
            }
            if (strtoupper($reg[2]) == 'M') {
                $memmax = $reg[1] * 1024 * 1024;
            }
            if (strtoupper($reg[2]) == 'K') {
                $memmax = $reg[1] * 1024;
            }
        }

        $langs = Config::getLangs();

        $result = [];
        $result['ok'] = $memmax >= $memrequired || $memmax == -1;
        if ($result['ok']) {
            return [
                'status' => Status::OK,
                'text' => $langs->trans("PHPMemoryOK", $memmaxorig, $memrequiredorig),
            ];
        }

        return [
            'status' => Status::WARNING,
            'text' => $langs->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig),
        ];
    }
}

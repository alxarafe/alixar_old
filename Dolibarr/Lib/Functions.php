<?php

/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Disable unused stream wrappers
 *
 * @param array|null $streamsToDisable
 *
 * @return bool
 */
function unregisterStreamWrappers(array $streamsToDisable = null)
{
    if (!isset($streamsToDisable)) {
        $streamsToDisable = ['compress.zlib', 'compress.bzip2', 'ftp', 'ftps', 'glob', 'data', 'expect', 'ogg', 'rar', 'zip', 'zlib'];
    }

    $ok = true;
    $wrappers = stream_get_wrappers();
    foreach ($streamsToDisable as $streamToDisable) {
        if (!in_array($streamToDisable, $wrappers)) {
            continue;
        }

        $ok = $ok && stream_wrapper_unregister($streamToDisable);
    }

    return $ok;
}

/**
 * Defines the constant $name, if it is not already defined.
 *
 * @param string $name
 * @param        $value
 */
function defineIfNotDefined(string $name, $value)
{
    if (!defined($name)) {
        define($name, $value);
    }
}

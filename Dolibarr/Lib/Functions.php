<?php

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

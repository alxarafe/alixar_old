<?php

/* Copyright (C) 2018   Andreu Bisquerra    <jove@bisquerra.com>
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

// This page return an image of public photos of a category or product.
// Test to check image can be publicly viewed is done inside the viewimage.php wrapper.

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');    // Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))     define('NOREQUIREDB','1');      // Not disabled cause need to load personalized language
defineIfNotDefined('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))       define('NOREQUIRETRAN','1');
defineIfNotDefined('NOTOKENRENEWAL', '1');
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');

if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
    require BASE_PATH . '/main.inc.php'; // Load $user and permissions
}

$id = GETPOSTINT('id');
$w = GETPOSTINT('w');
$h = GETPOSTINT('h');
$query = GETPOST('query', 'alpha');

if (!isModEnabled('takepos')) {
    accessforbidden('Module not enabled');
}


/*
 * View
 */

if ($query == "cat") {
    require_once BASE_PATH . '/../Dolibarr/Lib/Categories.php';

    $object = new Categorie($db);
    $result = $object->fetch($id);

    $upload_dir = $conf->categorie->multidir_output[$object->entity];
    $pdir = get_exdir($object->id, 2, 0, 0, $object, 'category') . $object->id . "/photos/";
    $dir = $upload_dir . '/' . $pdir;

    foreach ($object->liste_photos($dir) as $key => $obj) {
        if ($obj['photo_vignette']) {
            $filename = $obj['photo_vignette'];
        } else {
            $filename = $obj['photo'];
        }
        $file = DOL_URL_ROOT . '/viewimage.php?cache=1&publictakepos=1&modulepart=category&entity=' . $object->entity . '&file=' . urlencode($pdir . $filename);
        header('Location: ' . $file);
        exit;
    }
    header('Location: ../../public/theme/common/nophoto.png');
    exit;
} elseif ($query == "pro") {
    $objProd = new Product($db);
    $objProd->fetch($id);
    $image = $objProd->show_photos('product', $conf->product->multidir_output[$objProd->entity], 'small', 1);

    $match = array();
    preg_match('@src="([^"]+)"@', $image, $match);
    $file = array_pop($match);
    if ($file == "") {
        header('Location: ../../public/theme/common/nophoto.png');
        exit;
    } else {
        if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
            header('Location: ' . $file . '&cache=1');
            exit;
        } else {
            header('Location: ' . $file . '&cache=1&publictakepos=1&modulepart=product');
            exit;
        }
    }
} else {
    // TODO We don't need this. Size of image must be defined on HTML page, image must NOT be resized when downloaded.

    // The file
    $filename = $query . ".jpg";

    // Dimensions
    [$width, $height] = getimagesize($filename);
    $new_width = $w;
    $new_height = $h;

    // Resample
    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefromjpeg($filename);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Output
    imagejpeg($image_p, null, 100);
}

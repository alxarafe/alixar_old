<?php

/* Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliModules\Billing\Model;

/**
 *       \file       htdocs/core/class/commonorder.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of orders classes (customer and supplier)
 */

use DoliCore\Base\GenericDocument;
use DoliModules\Billing\Trait\CommonIncoterm;

/**
 *      Superclass for orders classes
 */
abstract class CommonOrder extends GenericDocument
{
    use CommonIncoterm;


    /**
     * @var string code
     */
    public $code = "";

    /**
     *  Return clicable link of object (with eventually picto)
     *
     * @param string $option    Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
     * @param array  $arraydata Array of data
     *
     * @return     string                              HTML Code for Kanban thumb.
     */
    public function getKanbanView($option = '', $arraydata = null)
    {
        global $langs, $conf;

        $selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

        $return = '<div class="box-flex-item box-flex-grow-zero">';
        $return .= '<div class="info-box info-box-sm">';
        $return .= '<div class="info-box-icon bg-infobox-action">';
        $return .= img_picto('', 'order');
        $return .= '</div>';
        $return .= '<div class="info-box-content">';
        $return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . (method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref) . '</span>';
        if ($selected >= 0) {
            $return .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
        }
        if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
            $return .= '<br><div class="info-box-ref tdoverflowmax150">' . $this->thirdparty->getNomUrl(1) . '</div>';
        }
        if (property_exists($this, 'total_ht')) {
            $return .= '<div class="info-box-ref amount">' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . ' ' . $langs->trans('HT') . '</div>';
        }
        if (method_exists($this, 'getLibStatut')) {
            $return .= '<div class="info-box-status">' . $this->getLibStatut(3) . '</div>';
        }
        $return .= '</div>';
        $return .= '</div>';
        $return .= '</div>';
        return $return;
    }
}

<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliModules\Variants\Model;

use DoliCore\Base\Model;

class ProductCombination extends Model
{
    public $table = 'product_attribute_combination';

    public function fetch($rowid)
    {
        return DB::firstWhere('rowid', $rowid);
    }

    public function fetchCombinationPriceLevels($fk_price_level = 0, $useCache = true)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function saveCombinationPriceLevels($clean = 1)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function fetchByFkProductChild($productid, $donotloadpricelevel = 0)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function fetchAllByFkProductParent($fk_product_parent, $sort_by_ref = false)
    {
        $result = ProductCombination::where('fk_product_parent', $fk_product_parent);
        dump($result);
        return $result;
    }

    public function countNbOfCombinationForFkProductParent($fk_product_parent)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function create($user)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function update2(User $user)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function delete2(User $user)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function deleteByFkProductParent($user, $fk_product_parent)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function updateProperties(Product $parent, User $user)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function fetchByProductCombination2ValuePairs($prodid, array $features)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function getUniqueAttributesAndValuesByFkProductParent($productid)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function createProductCombination(User $user, Product $product, array $combinations, array $variations, $price_var_percent = false, $forced_pricevar = false, $forced_weightvar = false, $forced_refvar = false, $ref_ext = '')
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function copyAll(User $user, $origProductId, Product $destProduct)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }

    public function getCombinationLabel($prod_child)
    {
        die(__METHOD__ . ' of ' . __CLASS__);
    }
}

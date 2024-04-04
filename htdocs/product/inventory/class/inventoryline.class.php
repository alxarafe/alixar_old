<?php

/* Copyright (C) 2007-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

use DoliCore\Base\GenericDocumentLine;

/**
 * Class InventoryLine
 */
class InventoryLine extends GenericDocumentLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'inventoryline';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'inventorydet';

    /**
     * @var int  Does inventory support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test
     *      with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string String with name of icon for inventory
     */
    public $picto = 'stock';

    /**
     *  'type' if the field format.
     *  'label' the translation key.
     *  'enabled' is a condition when the field must be managed.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view
     *  forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be
     *  selected for viewing)
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
     *  'position' is the sort order of field.
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable
     *  like integer or double(24,8).
     *  'help' is a string visible as a tooltip on field
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     *  'default' is a default value for creation (can still be replaced by the global setup of default values)
     *  'showoncombobox' if field must be shown into the label of combobox
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>
     *       Array with all fields and their property. Do not use it as a static var. It may be modified by
     *       constructor.
     */
    public $fields = [
        'rowid' => ['type' => 'integer', 'label' => 'TechnicalID', 'visible' => -1, 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => 'Id',],
        'fk_inventory' => ['type' => 'integer:Inventory:product/inventory/class/inventory.class.php', 'label' => 'Inventory', 'visible' => 1, 'enabled' => 1, 'position' => 30, 'index' => 1, 'help' => 'LinkToInventory'],
        'fk_warehouse' => ['type' => 'integer:Entrepot:product/stock/class/entrepot.class.php', 'label' => 'Warehouse', 'visible' => 1, 'enabled' => 1, 'position' => 30, 'index' => 1, 'help' => 'LinkToThirdparty'],
        'fk_product' => ['type' => 'integer:Product:product/class/product.class.php', 'label' => 'Product', 'visible' => 1, 'enabled' => 1, 'position' => 32, 'index' => 1, 'help' => 'LinkToProduct'],
        'batch' => ['type' => 'string', 'label' => 'Batch', 'visible' => 1, 'enabled' => 1, 'position' => 32, 'index' => 1, 'help' => 'LinkToProduct'],
        'datec' => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 500],
        'tms' => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501],
        'qty_stock' => ['type' => 'double', 'label' => 'QtyFound', 'visible' => 1, 'enabled' => 1, 'position' => 32, 'index' => 1, 'help' => 'Qty we found/want (to define during draft edition)'],
        'qty_view' => ['type' => 'double', 'label' => 'QtyBefore', 'visible' => 1, 'enabled' => 1, 'position' => 33, 'index' => 1, 'help' => 'Qty before (filled once movements are validated)'],
        'qty_regulated' => ['type' => 'double', 'label' => 'QtyDelta', 'visible' => 1, 'enabled' => 1, 'position' => 34, 'index' => 1, 'help' => 'Qty added or removed (filled once movements are validated)'],
        'pmp_real' => ['type' => 'double', 'label' => 'PMPReal', 'visible' => 1, 'enabled' => 1, 'position' => 35],
        'pmp_expected' => ['type' => 'double', 'label' => 'PMPExpected', 'visible' => 1, 'enabled' => 1, 'position' => 36],
    ];

    /**
     * @var int ID
     */
    public $rowid;

    public $fk_inventory;
    public $fk_warehouse;
    public $fk_product;
    public $batch;
    public $datec;
    public $qty_stock;
    public $qty_view;
    public $qty_regulated;
    public $pmp_real;
    public $pmp_expected;


    /**
     * Create object in database
     *
     * @param User $user      User that creates
     * @param int  $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int              Return integer <0 if KO, >0 if OK
     */
    public function create(User $user, $notrigger = 0)
    {
        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id  Id object
     * @param string $ref Ref
     *
     * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null)
    {
        $result = $this->fetchCommon($id, $ref);
        //if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
        return $result;
    }

    /**
     * Update object into database
     *
     * @param User $user      User that modifies
     * @param int  $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int             Return integer <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User $user      User that deletes
     * @param int  $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int              Return integer <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = 0)
    {
        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }
}

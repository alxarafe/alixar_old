<?php

/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020       Lenin Rivas		        <lenin@leninrivas.com>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
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

/**
 * \file        mrp/class/mo.class.php
 * \ingroup     mrp
 * \brief       This file is a CRUD class file for Mo (Create/Read/Update/Delete)
 */

use DoliCore\Base\GenericDocumentLine;

/**
 * Class MoLine. You can also remove this and generate a CRUD class for lines objects.
 */
class MoLine extends GenericDocumentLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'mrp_production';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'mrp_production';

    /**
     * @var int  Does myobject support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does moline support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
        'fk_mo' => array('type' => 'integer', 'label' => 'Mo', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 15),
        'origin_id' => array('type' => 'integer', 'label' => 'Origin', 'enabled' => 1, 'visible' => -1, 'notnull' => 0, 'position' => 17),
        'origin_type' => array('type' => 'varchar(10)', 'label' => 'Origin type', 'enabled' => 1, 'visible' => -1, 'notnull' => 0, 'position' => 18),
        'position' => array('type' => 'integer', 'label' => 'Position', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 20),
        'fk_product' => array('type' => 'integer', 'label' => 'Product', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 25),
        'fk_warehouse' => array('type' => 'integer', 'label' => 'Warehouse', 'enabled' => 1, 'visible' => -1, 'position' => 30),
        'qty' => array('type' => 'real', 'label' => 'Qty', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
        'qty_frozen' => array('type' => 'smallint', 'label' => 'QuantityFrozen', 'enabled' => 1, 'visible' => 1, 'default' => '0', 'position' => 105, 'css' => 'maxwidth50imp', 'help' => 'QuantityConsumedInvariable'),
        'disable_stock_change' => array('type' => 'smallint', 'label' => 'DisableStockChange', 'enabled' => 1, 'visible' => 1, 'default' => '0', 'position' => 108, 'css' => 'maxwidth50imp', 'help' => 'DisableStockChangeHelp'),
        'batch' => array('type' => 'varchar(30)', 'label' => 'Batch', 'enabled' => 1, 'visible' => -1, 'position' => 140),
        'role' => array('type' => 'varchar(10)', 'label' => 'Role', 'enabled' => 1, 'visible' => -1, 'position' => 145),
        'fk_mrp_production' => array('type' => 'integer', 'label' => 'Fk mrp production', 'enabled' => 1, 'visible' => -1, 'position' => 150),
        'fk_stock_movement' => array('type' => 'integer', 'label' => 'StockMovement', 'enabled' => 1, 'visible' => -1, 'position' => 155),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 160),
        'tms' => array('type' => 'timestamp', 'label' => 'Tms', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 165),
        'fk_user_creat' => array('type' => 'integer', 'label' => 'UserCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 170),
        'fk_user_modif' => array('type' => 'integer', 'label' => 'UserModification', 'enabled' => 1, 'visible' => -1, 'position' => 175),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -1, 'position' => 180),
        'fk_default_workstation' => array('type' => 'integer', 'label' => 'DefaultWorkstation', 'enabled' => 1, 'visible' => 1, 'notnull' => 0, 'position' => 185),
        'fk_unit' => array('type' => 'int', 'label' => 'Unit', 'enabled' => 1, 'visible' => 1, 'notnull' => 0, 'position' => 186)
    );

    public $rowid;
    public $fk_mo;
    public $origin_id;
    public $origin_type;
    public $position;
    public $fk_product;
    public $fk_warehouse;
    public $qty;
    public $qty_frozen;
    public $disable_stock_change;
    public $efficiency;
    public $batch;
    public $role;
    public $fk_mrp_production;
    public $fk_stock_movement;
    public $date_creation;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;
    public $fk_parent_line;
    public $fk_unit;

    /**
     * @var int Service Workstation
     */
    public $fk_default_workstation;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $langs;

        $this->db = $db;

        if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     * @return int             Return integer <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = 0)
    {
        if (empty($this->qty)) {
            $this->error = 'BadValueForQty';
            return -1;
        }

        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null)
    {
        $result = $this->fetchCommon($id, $ref);
        return $result;
    }

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string       $sortorder      Sort Order
     * @param  string       $sortfield      Sort field
     * @param  int          $limit          limit
     * @param  int          $offset         Offset
     * @param  string|array $filter         Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string       $filtermode     Filter mode (AND or OR)
     * @return array|int                    int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = 'SELECT ';
        $sql .= $this->getFieldList();
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
            $sql .= ' WHERE t.entity IN (' . getEntity($this->element) . ')';
        } else {
            $sql .= ' WHERE 1 = 1';
        }

        // Deprecated.
        if (is_array($filter)) {
            $sqlwhere = array();
            if (count($filter) > 0) {
                foreach ($filter as $key => $value) {
                    if ($key == 't.rowid') {
                        $sqlwhere[] = $key . " = " . ((int) $value);
                    } elseif (strpos($key, 'date') !== false) {
                        $sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
                    } else {
                        $sqlwhere[] = $key . " LIKE '%" . $this->db->escape($this->db->escapeforlike($value)) . "%'";
                    }
                }
            }
            if (count($sqlwhere) > 0) {
                $sql .= ' AND (' . implode(' ' . $this->db->escape($filtermode) . ' ', $sqlwhere) . ')';
            }

            $filter = '';
        }

        // Manage filter
        $errormessage = '';
        $sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
        if ($errormessage) {
            $this->errors[] = $errormessage;
            dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
            return -1;
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= $this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     * @return int             Return integer <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User  $user       User that deletes
     * @param int   $notrigger  0=launch triggers after, 1=disable triggers
     * @return int              Return integer <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = 0)
    {
        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }
}

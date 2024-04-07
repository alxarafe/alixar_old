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

namespace DoliModules\Contact\Trait;

trait Mailing
{
    /**
     *  Set "blacklist" mailing status
     *
     * @param int $no_email 1=Do not send mailing, 0=Ok to receive mailing
     *
     * @return int                 Return integer <0 if KO, >0 if OK
     */
    public function setNoEmail($no_email)
    {
        $error = 0;

        // Update mass emailing flag into table mailing_unsubscribe
        if ($this->email) {
            $this->db->begin();

            if ($no_email) {
                $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "mailing_unsubscribe WHERE entity IN (" . getEntity('mailing', 0) . ") AND email = '" . $this->db->escape($this->email) . "'";
                $resql = $this->db->query($sql);
                if ($resql) {
                    $obj = $this->db->fetch_object($resql);
                    $noemail = $obj->nb;
                    if (empty($noemail)) {
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "mailing_unsubscribe(email, entity, date_creat) VALUES ('" . $this->db->escape($this->email) . "', " . getEntity('mailing', 0) . ", '" . $this->db->idate(dol_now()) . "')";
                        $resql = $this->db->query($sql);
                        if (!$resql) {
                            $error++;
                            $this->error = $this->db->lasterror();
                            $this->errors[] = $this->error;
                        }
                    }
                } else {
                    $error++;
                    $this->error = $this->db->lasterror();
                    $this->errors[] = $this->error;
                }
            } else {
                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "mailing_unsubscribe WHERE email = '" . $this->db->escape($this->email) . "' AND entity IN (" . getEntity('mailing', 0) . ")";
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                    $this->errors[] = $this->error;
                }
            }

            if (empty($error)) {
                $this->no_email = $no_email;
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                return $error * -1;
            }
        }

        return 0;
    }

    /**
     *  get "blacklist" mailing status
     *  set no_email attribute to 1 or 0
     *
     * @return int                 Return integer <0 if KO, >0 if OK
     */
    public function getNoEmail()
    {
        if ($this->email) {
            $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "mailing_unsubscribe WHERE entity IN (" . getEntity('mailing') . ") AND email = '" . $this->db->escape($this->email) . "'";
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                $this->no_email = $obj->nb;
                return 1;
            } else {
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->error;
                return -1;
            }
        }
        return 0;
    }
}
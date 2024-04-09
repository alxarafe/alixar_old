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

use Alxarafe\Tools\Debug;
use DoliModules\Contact\Model\MailingUnsubscribe;

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
        if (!$this->email) {
            Debug::message('Called to Mailing::setNoEmail with no email');
            return 0;
        }

        $entities = [getEntity('mailing', 0)];
        $this->no_email = $no_email;

        if ($no_email) {
            Debug::message('setNoEmail: unsubscribeEmails(' . $this->email . ')');
            return MailingUnsubscribe::unsubscribeEmail($this->email, $entities);
        }

        Debug::message('setNoEmail: subscribeEmail(' . $this->email . ')');
        MailingUnsubscribe::removeUnsubscriptionEmail($this->email, $entities);
        return 1;
    }

    /**
     *  get "blacklist" mailing status
     *  set no_email attribute to 1 or 0
     *
     * @return int                 Return integer <0 if KO, >0 if OK
     */
    public function getNoEmail()
    {
        if (!$this->email) {
            Debug::message('Called to Mailing::getNoEmail with no email');
            return 0;
        }
        $found = MailingUnsubscribe::unsubscribedEmail($this->email, [getEntity('mailing', 0)]);
        Debug::message('getNoEmail ' . $this->email . ': ' . ($found ? '' : 'NOT ') . 'subscribed!');
        if ($found) {
            $this->no_email = 1;
            return 1;
        }

        $this->no_email = 0;
        return 0;
    }
}

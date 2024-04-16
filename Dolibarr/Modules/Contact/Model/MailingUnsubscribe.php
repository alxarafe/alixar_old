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

namespace DoliModules\Contact\Model;

use DoliCore\Base\Model;

class MailingUnsubscribe extends Model
{
    /**
     * Indicates whether the automatic record creation and update fields are to be used.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Name of the table associated with the model. By default, it is the plural model name in
     * snakecase format: 'mailing_unsubscribes'.
     *
     * @var string
     */
    protected $table = 'mailing_unsubscribe';

    /**
     * List of fields that will be autocompleted with 'null' during the registration of a new record.
     *
     * @var string[]
     */
    protected $fillable = ['unsubscribegroup', 'ip', 'entity', 'email'];


    /**
     * Register an email to unsubscribe from lists.
     * TODO: It would be necessary to see if one or more entities really have to be passed.
     *
     * @param $email
     * @param $entities
     *
     * @return bool
     */
    public static function unsubscribeEmail($email, $entities): bool
    {
        foreach ($entities as $entity) {
            static::firstOrCreate([
                'email' => $email,
                'entity' => $entity,
            ]);
        }
        return static::unsubscribedEmail($email, $entities);
    }

    /**
     * Remove the email from the unsubscribed list.
     * TODO: It would be necessary to see if one or more entities really have to be passed.
     *
     * @param $email
     * @param $entities
     *
     * @return bool
     */
    public static function removeUnsubscriptionEmail($email, $entities): bool
    {
        return static::where('email', $email)->whereIn('entity', $entities)->delete();
    }

    /**
     * Check if the email is subscribed in some of the indicated entities.
     * TODO: It would be necessary to see if one or more entities really have to be passed.
     *
     * @param $email
     * @param $entities
     *
     * @return bool
     */
    public static function unsubscribedEmail($email, $entities): bool
    {
        $data = static::where('email', $email)->whereIn('entity', $entities)->get();
        return count($data) > 0;
    }
}

<?php
/***************************************************************************
 *  Copyright (C) 2003-2011 Polytechnique.org                              *
 *  http://opensource.polytechnique.org/                                   *
 *                                                                         *
 *  This program is free software; you can redistribute it and/or modify   *
 *  it under the terms of the GNU General Public License as published by   *
 *  the Free Software Foundation; either version 2 of the License, or      *
 *  (at your option) any later version.                                    *
 *                                                                         *
 *  This program is distributed in the hope that it will be useful,        *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 *  GNU General Public License for more details.                           *
 *                                                                         *
 *  You should have received a copy of the GNU General Public License      *
 *  along with this program; if not, write to the Free Software            *
 *  Foundation, Inc.,                                                      *
 *  59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                *
 ***************************************************************************/

// {{{ class MailingList

class MailingList
{
    public $address;        // Fully qualified address of the list
    public $mbox;           // mailbox for the list
    public $domain;         // domain for the list
    protected $mmclient;    // The XML-RPC client for Mailman requests

    public function __construct($mbox, $domain, $user, $pass='')
    {
        $this->mbox = $mbox;
        $this->domain = $domain;
        $this->address = "$mbox@$domain";

        if ($user instanceof PlUser) {
            $this->mmclient = new MMList($user, $this->domain);
        } else {
            $this->mmclient = new MMList($user, $pass, $this->domain);
        }
    }

    /** Instantiate a MailingList from its address.
     *
     * $user and $pass are connection parameters for MailMan.
     */
    public static function fromAddress($address, $user, $pass='')
    {
        if (strstr($address, '@') !== false) {
            list($mbox, $domain) = explode('@', $address);
        } else {
            global $globals;
            $mbox = $address;
            $domain = $globals->mail->domain;
        }
        return new MailingList($mbox, $domain, $user, $pass);
    }

    /** Retrieve the MailingList associated with a given promo.
     *
     * $user and $pass are connection parameters for MailMan.
     */
    public static function promo($promo, $user, $pass='')
    {
        global $globals;
        $mail_domain = $globals->mail->domain;
        return new MailingList('promo', "$promo.$mail_domain", $user, $pass);
    }

    /** Subscribe the current user to the list
     */
    public function subscribe()
    {
        return $this->mmclient->subscribe($this->mbox);
    }

    /** Unsubscribe the current user from the list
     */
    public function unsubscribe()
    {
        return $this->mmclient->unsubscribe($this->mbox);
    }

    /** Retrieve owners for the list.
     *
     * TODO: document the return type
     */
    public function getOwners()
    {
        return $this->mmclient->get_owners($this->mbox);
    }

    /** Retrieve members of the list.
     *
     * TODO: document the return type
     */
    public function getMembers()
    {
        return $this->mmclient->get_members($this->mbox);
    }

    /** Retrieve a subset of list members.
     *
     * TODO: document the return type
     */
    public function getMembersLimit($page, $number_per_page)
    {
        return $this->mmclient->get_members_limit($this->mbox, $page, $number_per_page);
    }

    /** Create a list
     */
    public function create($description, $advertise,
        $moderation_level, $subscription_level, $owners, $members)
    {
        return $this->mmclient->create_list($this->mbox, utf8_decode($description),
            $advertise, $moderation_level, $subscription_level,
            $owners, $members);
    }
}

// }}}

// vim:set et sw=4 sts=4 sws=4 enc=utf-8:
?>
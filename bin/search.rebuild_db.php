#!/usr/bin/php5 -q
<?php
/***************************************************************************
 *  Copyright (C) 2003-2008 Polytechnique.org                              *
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

require('./connect.db.inc.php');
require('user.func.inc.php');

ini_set('memory_limit', "16M");
$globals->debug = 0; // Do not store backtraces

XDB::execute('DELETE FROM search_name');
$res = XDB::iterRow('SELECT  auth_user_md5.user_id, nom, prenom, nom_usage, profile_nick
                       FROM  auth_user_md5
                  LEFT JOIN auth_user_quick USING(user_id)');
$i = 0;
$muls = array(1, 1, 1, 0.2);
$pub  = array(true, true, true, false);
while ($tmp = $res->next()) {
    $uid = array_shift($tmp);
    _user_reindex($uid, $tmp, $muls, $pub);
    printf("\r%u / %u",  ++$i, $res->total());
}

print "done\n";

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker enc=utf-8:
?>
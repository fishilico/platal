<?php
/***************************************************************************
 *  Copyright (C) 2003-2004 Polytechnique.org                              *
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

require 'xnet.inc.php';

require_once('xnet/evenements.php');

$evt = get_event_detail(Env::get('eid'), Env::get('item_id'));

header("Content-type: text/x-csv");
header("Pragma: ");
header("Cache-Control: ");
new_nonhtml_page('xnet/groupe/evt-csv.tpl');

if ($evt) {

    $admin = may_update();

    $tri = (Env::get('order') == 'alpha' ? 'promo, nom, prenom' : 'nom, prenom, promo');

    $ini = Env::has('initiale') ? 'AND IF(u.nom IS NULL,m.nom,IF(u.nom_usage<>"", u.nom_usage, u.nom)) LIKE "'.addslashes(Env::get('initiale')).'%"' : '';

    $participants = get_event_participants(Env::get('eid'), Env::get('item_id'), $ini, $tri, "", $evt['money'] && $admin, $evt['paiement_id']);

    $page->assign('participants', $participants);
    $page->assign('admin', $admin);
    $page->assign('moments', $evt['moments']);
    $page->assign('money', $evt['money']);
    $page->assign('tout', !Env::get('item_id', false));
}

$page->run();

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker:
?>
<?php
/***************************************************************************
 *  Copyright (C) 2003-2009 Polytechnique.org                              *
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

function smarty_function_valid_date($params, &$smarty)
{
    extract($params);

    if (!isset($name)) {
        $name = 'valid_date';
    }
    $text = "<select name=\"$name\">";
    if (!isset($from)) {
        $from = 1;
    }
    if (!isset($to)) {
        $to = 30;
    }
    $value = strtr($value, array('-' => ''));
    $time = time() + 3600 * 24 * $from;
    $mth  = '';
    for ($i = $from ; $i <= $to ; $i++) {
        $p_stamp = date('Ymd', $time);
        $date    = date('d / m / Y', $time);
        $select  = ($p_stamp == $value) ? 'selected="selected"' : '';
        $month   = pl_entities(strftime('%B', $time), ENT_QUOTES);
        if ($mth != $month) {
            if ($i != $from) {
                $text .= '</optgroup>';
            }
            $text .= "<optgroup label=\"$month\">";
            $mth = $month;
        }
        $time += 3600 * 24;
        $text .= "<option value=\"$p_stamp\" $select>$date</option>";
    }
    return $text . "</optgroup></select>";
}

/* vim: set expandtab enc=utf-8: */
?>
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

function smarty_compiler_javascript($tag_attrs, &$compiler)
{
    extract($compiler->_parse_attrs($tag_attrs));

    if (!isset($name)) {
        return null;
    }
    $name = pl_entities(trim($name, '\'"'), ENT_QUOTES);
    $name = "javascript/$name.js";
    if (isset($full) && $full) {
        global $globals;
        $name = $globals->baseurl . '/' . $name;
    }

    return "?><script type='text/javascript' src='$name'></script><?php";
}

/* vim: set expandtab enc=utf-8: */

?>
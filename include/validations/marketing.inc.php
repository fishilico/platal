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


class MarkReq extends Validate
{
    // {{{ properties

    public $perso;

    public $m_id;
    public $m_email;
    public $m_nom;
    public $m_prenom;
    public $m_promo;
    public $m_relance;
    public $m_type;
    public $m_data;

    public $rules = "Accepter si l'adresse email parait correcte, et pas absurde (ou si le marketeur est de confiance). Si le
    demandeur marque sa propre adresse email, refuser dans tous les cas.
    Ne pas marqueter au nom de Polytechnique.org plus d'une fois par an.
    Sauf abus flagrant, il n'y a pas de raison de refuser des marketing perso répétés.";
    // }}}
    // {{{ constructor

    public function __construct($sender, $mark_id, $email, $perso, $type, $data)
    {
        parent::__construct($sender, false, 'marketing');
        $this->m_id    = $mark_id;
        $this->m_email = $email;
        $this->perso   = $perso;
        $this->m_type  = $type;
        $this->m_data  = $data;

        $res = XDB::query('SELECT  u.nom, u.prenom, u.promo
                             FROM  auth_user_md5      AS u
                            WHERE  user_id = {?}
                         GROUP BY  u.user_id', $mark_id);
        list ($this->m_nom, $this->m_prenom, $this->m_promo) = $res->fetchOneRow();
    }

    // }}}
    // {{{ function formu()

    public function formu()
    {
        $res = XDB::query('SELECT  IF(MAX(m.last)>p.relance, MAX(m.last), p.relance)
                             FROM  auth_user_md5      AS u
                        LEFT JOIN  register_pending   AS p ON p.uid = u.user_id
                        LEFT JOIN  register_marketing AS m ON m.uid = u.user_id
                            WHERE  user_id = {?}',
                            $this->m_id);
        $this->m_relance = $res->fetchOneCell();
        return 'include/form.valid.mark.tpl';
    }

    // }}}
    // {{{ function _mail_subj

    protected function _mail_subj()
    {
        return "[Polytechnique.org] Marketing de {$this->m_prenom} {$this->m_nom} ({$this->m_promo})";
    }

    // }}}
    // {{{ function _mail_body

    protected function _mail_body($isok)
    {
        if ($isok) {
            return "  Un email de marketing vient d'être envoyé "
                .($this->perso ? 'en ton nom' : 'en notre nom')
                ." à {$this->m_prenom} {$this->m_nom} ({$this->m_promo}) pour l'encourager à s'inscrire !\n\n"
                ."Merci de ta participation !\n";
        } else {
            return "  Nous n'avons pas jugé bon d'envoyer d'email de marketing à {$this->m_prenom} {$this->m_nom} ({$this->m_promo}).";
        }
    }

    // }}}
    // {{{ function commit()

    public function commit()
    {
        $market = Marketing::get($this->m_id, $this->m_email);
        if ($market == null) {
            return false;
        }
        $market->send();
        return true;
    }

    // }}}
}

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker enc=utf-8:
?>
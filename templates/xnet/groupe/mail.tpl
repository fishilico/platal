{**************************************************************************}
{*                                                                        *}
{*  Copyright (C) 2003-2004 Polytechnique.org                             *}
{*  http://opensource.polytechnique.org/                                  *}
{*                                                                        *}
{*  This program is free software; you can redistribute it and/or modify  *}
{*  it under the terms of the GNU General Public License as published by  *}
{*  the Free Software Foundation; either version 2 of the License, or     *}
{*  (at your option) any later version.                                   *}
{*                                                                        *}
{*  This program is distributed in the hope that it will be useful,       *}
{*  but WITHOUT ANY WARRANTY; without even the implied warranty of        *}
{*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *}
{*  GNU General Public License for more details.                          *}
{*                                                                        *}
{*  You should have received a copy of the GNU General Public License     *}
{*  along with this program; if not, write to the Free Software           *}
{*  Foundation, Inc.,                                                     *}
{*  59 Temple Place, Suite 330, Boston, MA  02111-1307  USA               *}
{*                                                                        *}
{**************************************************************************}


<h1>{$asso.nom} : Envoyer un mail</h1>

<p class="descr">
Ton message peut �tre personnalis� : si tu rentres les mots &lt;cher&gt;, &lt;prenom&gt;,
&lt;nom&gt;, ces mots seront remplac�s, pour chacun des destinataires, par "cher" accord� au
masculin ou f�minin, par son pr�nom, ou son nom.
</p>
 
<form action="{$smarty.server.PHP_SELF}" method="post">
  <table class='large'>
    <tr>
      <th colspan="2">Ecrire un mail :</th>
    </tr>
    <tr>
      <td class="titre">Exp�diteur :</td>
      <td>
        <input type="text" name="from" size="55" maxlength="70"
          value="{if $smarty.request.from}{$smarty.request.from}{else}&quot;{$smarty.session.prenom} {$smarty.session.nom}&quot; <{$smarty.session.bestalias}@polytechnique.org>{/if}" />
      </td>
    </tr>

    <tr>
      <th colspan="2">Destinataires</th>
    </tr>
    <tr valign="top">
      <td style='padding-left: 1em' class='titre'>
        *&nbsp;<em>membres</em>
      </td>
      <td>
        <input type="checkbox" name="membres" value="1" {if $smarty.request.membres}checked="checked"{/if} />
        <em>�crit � tous les membres du groupe</em> <a href="annuaire.php" class='popup'>(voir membres)</a>
      </td>
    </tr>

    {foreach from=$listes item=l}
    <tr>
      <td style='padding-left: 1em' class='titre'>
        *&nbsp;{$l.list}
      </td>
      <td>
        <input type="checkbox" name="ml[{$l.list}]" value="1" {if $smarty.request.ml[$l.list]}checked="checked"{/if} />
        {$l.addr}
        <a href="listes-admin.php?liste={$l.list}" class="popup">(voir composition)</a>
      </td>
    </tr>
    {/foreach}

    {* NOT SUPPORTED ATM
    {foreach from=$alias item=a}
    {assign var=b value=$a|regex_replace:"!@.*!":""}
    <tr>
      <td style='padding-left: 1em' class='titre'>
        *&nbsp;{$b}
      </td>
      <td>
        <input type="checkbox" name="al[{$b}]" value="1" {if $smarty.request.al[$b]}checked="checked"{/if} />
        {$a} <a href="alias-admin.php?liste={$b}" class='popup'>(voir composition)</a>
      </td>
    </tr>
    {/foreach}
    *}

    <tr>
      <th colspan="2">Contenu du mail</th>
    </tr>
    <tr>
      <td class="titre">
        Sujet :
      </td>
      <td><input type="text" name="sujet" value="{$smarty.request.sujet|default:"remplir le sujet ..."}" size=55 maxlength=70></td>
    </tr>
    <tr>
      <td colspan="2">
        <textarea name="body" cols="72" rows="25">{if $smarty.request.body}{$smarty.request.body}{else}  &lt;cher&gt; &lt;prenom&gt;,
          
  Nous avons le plaisir de t'adresser la lettre mensuelle du groupe {$asso.nom}.

  (ins�rer le texte...)

  Le bureau du groupe {$asso.nom}.{/if}
</textarea>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="send" value="Envoyer le message"></td>
    </tr>
  </table>
</form>

{* vim:set et sw=2 sts=2 sws=2: *}
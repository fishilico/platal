{**************************************************************************}
{*                                                                        *}
{*  Copyright (C) 2003-2015 Polytechnique.org                             *}
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

{if $users|@count}
<form action="{$platal->ns}directory/unact" method="post">
  {xsrf_token_field}
  <table cellspacing="2" cellpadding="0" class="tiny">
    <tr>
      <th>Nom</th>
      <th>Email</th>
      <th>
        <a href="javascript:toggleAll()">{icon name="arrow_refresh" title="Tout (dé)cocher"}</a>
      </th>
    </tr>
    {foreach from=$users item=user}
    <tr>
      <td class="checkboxToggle">{profile user=$user promo=true}</td>
      <td class="checkboxToggle">{$user->email}</td>
      <td class="checkboxToggle"><input type="checkbox" class="moderate_email" name="enable_accounts[{$user->id()}]" /></td>
    </tr>
    {/foreach}
    <tr>
      <td colspan="3" class="center">
        <input type="submit" value="Activer les comptes" />
      </td>
    </tr>
  </table>

  <script type="text/javascript">//<![CDATA[
  {literal}
  var toggleState = false;
  function toggleAll() {
    toggleState = !toggleState;
    var boxes = $(':checkbox.moderate_email');
    if (toggleState) {
      boxes.attr('checked', 'checked');
    } else {
      boxes.removeAttr('checked');
    }
  }

  $('.checkboxToggle').click(function (event) {
    // Don't uncheck the checkbox when clicking it
    if (event.target.tagName === 'INPUT') {
      return;
    }

    var checkbox = $(this).parent().find(':checkbox');
    checkbox = checkbox.attr('checked', !checkbox.attr('checked'));
    event.stopPropagation();
  });
  {/literal}
  //]]></script>

</form>
{else}
<p>Tous les inscrits au groupe ont un compte leur permettant d'accéder aux pages du groupe.</p>
{/if}

{* vim:set et sw=2 sts=2 sws=2 fenc=utf-8: *}

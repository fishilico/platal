{**************************************************************************}
{*                                                                        *}
{*  Copyright (C) 2003-2009 Polytechnique.org                             *}
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


<h1>
  Changer de mot de passe
</h1>

<p>
  Ton mot de passe doit faire au moins <strong>6 caractères</strong> et comporter deux types de
  caractères parmi les suivants&nbsp;: lettres minuscules, lettres majuscules, chiffres, caractères spéciaux.
  Attention au type de clavier que tu utilises (qwerty&nbsp;?) et aux majuscules/minuscules.
</p>
<p>
  Pour une sécurité optimale, ton mot de passe circule de manière chiffrée (https) et est
  stocké chiffré irréversiblement sur nos serveurs.
</p>
<br />
<form action="{$smarty.server.REQUEST_URI}" method="post" id="changepass">
  <table class="tinybicol" cellpadding="3" cellspacing="0"
    summary="Formulaire de mot de passe">
    <tr>
      <th colspan="2">
        Saisie du nouveau mot de passe
      </th>
    </tr>
    <tr>
      <td class="titre">
        Mot de passe&nbsp;:
      </td>
      <td>
        <input type="password" size="10" maxlength="256" name="nouveau" />
      </td>
    </tr>
    <tr>
      <td class="titre">
        Retape-le une fois&nbsp;:
      </td>
      <td>
        <input type="password" size="10" maxlength="256" name="nouveau2" />
      </td>
    </tr>
    <tr>
      <td class="titre">
        Sécurité
      </td>
      <td>
        {checkpasswd prompt="nouveau" submit="submitn"}
      </td>
    </tr>
    <tr>
      <td colspan="2" class="center">
        <input type="submit" value="Changer" name="submitn" onclick="EnCryptedResponse(); return false;" />
      </td>
    </tr>
  </table>
</form>
<form action="{$smarty.server.REQUEST_URI}" method="post" id="changepass2">
<p>
{xsrf_token_field}
<input type="hidden" name="response2"  value="" />
</p>
</form>

<p>
  Note bien qu'il s'agit là du mot de passe te permettant de t'authentifier sur le site {#globals.core.sitename#}&nbsp;;
  le mot de passe te permettant d'utiliser le serveur <a href="./Xorg/SMTPSécurisé">SMTP</a> et <a href="Xorg/NNTPSécurisé">NNTP</a>
  de {#globals.core.sitename#} (si tu as <a href="./password/smtp">activé l'accès SMTP et NNTP</a>)
  est indépendant de celui-ci et tu peux le modifier <a href="./password/smtp">ici</a>.
</p>

{* vim:set et sw=2 sts=2 sws=2 enc=utf-8: *}
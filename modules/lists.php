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

class ListsModule extends PLModule
{
    protected $client;

    function handlers()
    {
        return array(
            'lists'           => $this->make_hook('lists',     AUTH_MDP),
            'lists/ajax'      => $this->make_hook('ajax',      AUTH_MDP, 'user', NO_AUTH),
            'lists/create'    => $this->make_hook('create',    AUTH_MDP),

            'lists/members'   => $this->make_hook('members',   AUTH_COOKIE),
            'lists/annu'      => $this->make_hook('annu',      AUTH_COOKIE),
            'lists/archives'  => $this->make_hook('archives',  AUTH_COOKIE),
            'lists/archives/rss' => $this->make_hook('rss',    AUTH_PUBLIC, 'user', NO_HTTPS),

            'lists/moderate'  => $this->make_hook('moderate',  AUTH_MDP),
            'lists/admin'     => $this->make_hook('admin',     AUTH_MDP),
            'lists/options'   => $this->make_hook('options',   AUTH_MDP),
            'lists/delete'    => $this->make_hook('delete',    AUTH_MDP),

            'lists/soptions'  => $this->make_hook('soptions',  AUTH_MDP),
            'lists/check'     => $this->make_hook('check',     AUTH_MDP),
            'admin/lists'     => $this->make_hook('admin_all', AUTH_MDP, 'admin'),
        );
    }

    function on_subscribe($forlife, $uid, $promo, $password)
    {
        $this->prepare_client(null);
        $this->client->subscribe("promo$promo");
    }

    function prepare_client(&$page)
    {
        global $globals;

        require_once dirname(__FILE__).'/lists/lists.inc.php';

        $this->client = new MMList(S::v('uid'), S::v('password'));
        return $globals->mail->domain;
    }

    function get_pending_ops($domain, $list)
    {
        list($subs,$mails) = $this->client->get_pending_ops($list);
        $res = XDB::query("SELECT  mid
                             FROM  ml_moderate
                            WHERE  ml = {?} AND domain = {?}",
                          $list, $domain);
        $mids = $res->fetchColumn();
        foreach ($mails as $key=>$mail) {
            if (in_array($mail['id'], $mids)) {
                unset($mails[$key]);
            }
        }
        return array($subs, $mails);
    }

    function handler_lists(&$page)
    {
        function filter_owner($list)
        {
            return $list['own'];
        }

        function filter_member($list)
        {
            return $list['sub'];
        }

        $domain = $this->prepare_client($page);

        $page->changeTpl('lists/index.tpl');
        $page->addJsLink('ajax.js');
        $page->assign('xorg_title','Polytechnique.org - Listes de diffusion');


        if (Get::has('del')) {
            S::assert_xsrf_token();
            $this->client->unsubscribe(Get::v('del'));
            pl_redirect('lists');
        }
        if (Get::has('add')) {
            S::assert_xsrf_token();
            $this->client->subscribe(Get::v('add'));
            pl_redirect('lists');
        }
        if (Post::has('promo_add')) {
            S::assert_xsrf_token();

            $promo = Post::i('promo_add');
            if ($promo >= 1900 and $promo < 2100) {
                $this->client->subscribe("promo$promo");
            } else {
                $page->trigSuccess("promo incorrecte, il faut une promo sur 4 chiffres.");
            }
        }

        $listes = $this->client->get_lists();
        $owner  = array_filter($listes, 'filter_owner');
        $listes = array_diff_key($listes, $owner);
        $member = array_filter($listes, 'filter_member');
        $listes = array_diff_key($listes, $member);
        foreach ($owner as $key=>$liste) {
            list($subs,$mails) = $this->get_pending_ops($domain, $liste['list']);
            $owner[$key]['subscriptions'] = $subs;
            $owner[$key]['mails'] = $mails;
        }
        $page->register_modifier('hdc', 'list_header_decode');
        $page->assign_by_ref('owner',  $owner);
        $page->assign_by_ref('member', $member);
        $page->assign_by_ref('public', $listes);
    }

    function handler_ajax(&$page, $list = null)
    {
        header('Content-Type: text/html; charset="UTF-8"');
        $domain = $this->prepare_client($page);
        $page->changeTpl('lists/liste.inc.tpl', NO_SKIN);
        S::assert_xsrf_token();

        if (Get::has('unsubscribe')) {
            $this->client->unsubscribe($list);
        }
        if (Get::has('subscribe')) {
            $this->client->subscribe($list);
        }
        if (Get::has('sadd')) { /* 4 = SUBSCRIBE */
            $this->client->handle_request($list, Get::v('sadd'), 4, '');
        }
        if (Get::has('mid')) {
            $this->moderate_mail($domain, $list, Get::i('mid'));
        }

        list($liste, $members, $owners) = $this->client->get_members($list);
        if ($liste['own']) {
            list($subs,$mails) = $this->get_pending_ops($domain, $list);
            $liste['subscriptions'] = $subs;
            $liste['mails'] = $mails;
        }
        $page->register_modifier('hdc', 'list_header_decode');
        $page->assign_by_ref('liste', $liste);
    }

    function handler_create(&$page)
    {
        global $globals;

        $page->changeTpl('lists/create.tpl');

        $user_promo  = S::i('promo');
        $year        = date('Y');
        $month       = date('m');
        $young_promo = $very_young_promo = 0;
        if ((($year > $user_promo) && ($month > 3)) && ($year < $user_promo + 5)) {
            $young_promo = 1;
        }
        if ((($year > $user_promo) && ($month > 7)) && (($year < $user_promo + 1) && ($month < 8))) {
            $very_young_promo = 1;
        }
        $page->assign('young_promo', $young_promo);
        $page->assign('very_young_promo', $very_young_promo);

        $owners  = preg_split("/[\s]+/", Post::v('owners'), -1, PREG_SPLIT_NO_EMPTY);
        $members = preg_split("/[\s]+/", Post::v('members'), -1, PREG_SPLIT_NO_EMPTY);

        // click on validate button 'add_owner_sub' or type <enter>
        if (Post::has('add_owner_sub') && Post::has('add_owner')) {
            require_once('user.func.inc.php');
            // if we want to add an owner and then type <enter>, then both
            // add_owner_sub and add_owner are filled.
            $oforlifes = get_users_forlife_list(Post::v('add_owner'), true);
            $mforlifes = get_users_forlife_list(Post::v('add_member'), true);
            if (!is_null($oforlifes)) {
                $owners = array_merge($owners, $oforlifes);
            }
            // if we want to add a member and then type <enter>, then
            // add_owner_sub is filled, whereas add_owner is empty.
            if (!is_null($mforlifes)) {
                $members = array_merge($members, $mforlifes);
            }
        }

        // click on validate button 'add_member_sub'
        require_once('user.func.inc.php');
        if (Post::has('add_member_sub') && Post::has('add_member')) {
            $forlifes = get_users_forlife_list(Post::v('add_member'), true);
            if (!is_null($forlifes)) {
                $members = array_merge($members, $forlifes);
            }
        }
        if (Post::has('add_member_sub') && isset($_FILES['add_member_file']) && $_FILES['add_member_file']['tmp_name']) {
            $upload =& PlUpload::get($_FILES['add_member_file'], S::v('forlife'), 'list.addmember', true);
            if (!$upload) {
                $page->trigError('Une erreur s\'est produite lors du téléchargement du fichier');
            } else {
                $forlifes = get_users_forlife_list($upload->getContents(), true);
                if (!is_null($forlifes)) {
                    $members = array_merge($members, $forlifes);
                }
            }
        }

        ksort($owners);	
        $owners = array_unique($owners);
        ksort($members);
        $members = array_unique($members);

        $page->assign('owners', join("\n", $owners));
        $page->assign('members', join("\n", $members));

        if (!Post::has('submit')) {
            return;
        } else {
            S::assert_xsrf_token();
        }

        $asso = Post::v('asso');
        $liste = Post::v('liste');

        if (empty($liste)) {
            $page->trigError('Le champ «adresse souhaitée» est vide.');
        }
        if (!preg_match("/^[a-zA-Z0-9\-]*$/", $liste)) {
            $page->trigError('Le nom de la liste ne doit contenir que des lettres non accentuées, chiffres et tirets.');
        }

        if (($asso == "binet") || ($asso == "alias")) {
            $promo = Post::i('promo');
            $domain = $promo . '.' . $globals->mail->domain;

            if (($promo < 1921) || ($promo > date('Y'))) {
                $page->trigError('La promotion est mal renseignée, elle doit être du type : 2004.');
            }

            $new = $liste . '@' . $domain;
            $res = XDB::query('SELECT COUNT(*) FROM x4dat.virtual WHERE alias={?}', $new);

        } else {
            if ($asso == "groupex") {
                $groupex_name = Post::v('groupex_name');

                $res_groupe = XDB::query('SELECT mail_domain FROM groupex.asso WHERE nom={?}', $groupex_name);
                $domain = $res_groupe->fetchOneCell();

                if (!$domain) {
                    $page->trigError('Il n\'y a aucun groupe de ce nom sur Polytechnique.net.');
                }

                $new = $liste . '@' . $domain;
                $res = XDB::query('SELECT COUNT(*) FROM x4dat.virtual WHERE alias={?}', $new);
            } else {
                $res = XDB::query("SELECT COUNT(*) FROM aliases WHERE alias={?}", $liste);
                $domain = $globals->mail->domain;
            }
        }

        $n = $res->fetchOneCell();

        if ($n) {
            $page->trigError('L\'«adresse souhaitée» est déjà prise.');
        }

        if (!Post::v('desc')) {
            $page->trigError('Le sujet est vide.');
        }

        if (!count($owners)) {
            $page->trigError('Il n\'y a pas de gestionnaire.');
        }

        if (count($members)<4) {
            $page->trigError('Il n\'y a pas assez de membres.');
        }

        if (!$page->nb_errs()) {
            $page->assign('created', true);
            require_once 'validations.inc.php';
            $req = new ListeReq(S::v('uid'), $asso, $liste, $domain,
                                Post::v('desc'), Post::i('advertise'),
                                Post::i('modlevel'), Post::i('inslevel'),
                                $owners, $members);
            $req->submit();
        }
    }

    function handler_members(&$page, $liste = null)
    {
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $this->prepare_client($page);

        $page->changeTpl('lists/members.tpl');

        if (Get::has('del')) {
            S::assert_xsrf_token();
            $this->client->unsubscribe($liste);
            pl_redirect('lists/members/'.$liste);
        }

        if (Get::has('add')) {
            S::assert_xsrf_token();
            $this->client->subscribe($liste);
            pl_redirect('lists/members/'.$liste);
        }

        $members = $this->client->get_members($liste);

        $tri_promo = !Env::b('alpha');

        if (list($det,$mem,$own) = $members) {
            $membres = list_sort_members($mem, $tri_promo);
            $moderos = list_sort_owners($own, $tri_promo);

            $page->assign_by_ref('details', $det);
            $page->assign_by_ref('members', $membres);
            $page->assign_by_ref('owners',  $moderos);
            $page->assign('nb_m',  count($mem));
        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit d'en voir les détails");
        }
    }

    function handler_annu(&$page, $liste = null, $action = null, $subaction = null)
    {
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $this->prepare_client($page);

        if (Get::has('del')) {
            S::assert_xsrf_token();
            $this->client->unsubscribe($liste);
            pl_redirect('lists/annu/'.$liste);
        }
        if (Get::has('add')) {
            S::assert_xsrf_token();
            $this->client->subscribe($liste);
            pl_redirect('lists/annu/'.$liste);
        }

        $owners = $this->client->get_owners($liste);
        if (!is_array($owners)) {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit d'en voir les détails");
        }

        global $platal;
        list(,$members) = $this->client->get_members($liste);
        $users = array();
        foreach ($members as $m) {
            $users[] = $m[1];
        }
        require_once 'userset.inc.php';
        $view = new ArraySet($users);
        $view->addMod('trombi', 'Trombinoscope', true, array('with_promo' => true));
        if (empty($GLOBALS['IS_XNET_SITE'])) {
            $view->addMod('minifiche', 'Mini-fiches', false);
        }
        $view->addMod('geoloc', 'Planisphère');
        $view->apply("lists/annu/$liste", $page, $action, $subaction);
        if ($action == 'geoloc' && $subaction) {
            return;
        }

        $page->changeTpl('lists/annu.tpl');
        $moderos = list_sort_owners($owners[1]);
        $page->assign_by_ref('details', $owners[0]);
        $page->assign_by_ref('owners',  $moderos);
    }

    function handler_archives(&$page, $liste = null, $action = null, $artid = null)
    {
        global $globals;

        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $domain = $this->prepare_client($page);

        $page->changeTpl('lists/archives.tpl');

        if (list($det) = $this->client->get_members($liste)) {
            if (substr($liste,0,5) != 'promo' && ($det['ins'] || $det['priv'])
                    && !$det['own'] && ($det['sub'] < 2)) {
                $page->kill("La liste n'existe pas ou tu n'as pas le droit de la consulter.");
            }
            $get = Array('listname' => $liste, 'domain' => $domain);
            if (Post::has('updateall')) {
                $get['updateall'] = Post::v('updateall');
            }
            require_once 'banana/ml.inc.php';
            get_banana_params($get, null, $action, $artid);
            run_banana($page, 'MLBanana', $get);
        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit de la consulter.");
        }
    }

    function handler_rss(&$page, $liste = null, $alias = null, $hash = null)
    {
        require_once('rss.inc.php');
        $uid = init_rss(null, $alias, $hash);
        if (!$uid || !$liste) {
            exit;
        }

        $res = XDB::query("SELECT user_id AS uid, password, alias AS forlife
                             FROM auth_user_md5 AS u
                       INNER JOIN aliases       AS a ON (a.id = u.user_id AND a.type = 'a_vie')
                            WHERE u.user_id = {?}", $uid);
        $row = $res->fetchOneAssoc();
        $_SESSION = array_merge($row, $_SESSION);

        $domain = $this->prepare_client($page);
        if (list($det) = $this->client->get_members($liste)) {
            if (substr($liste,0,5) != 'promo' && ($det['ins'] || $det['priv'])
                    && !$det['own'] && ($det['sub'] < 2)) {
                exit;
            }
            require_once('banana/ml.inc.php');
            $banana = new MLBanana(S::v('forlife'), Array('listname' => $liste, 'domain' => $domain, 'action' => 'rss2'));
            $banana->run();
        }
        exit;
    }

    function moderate_mail($domain, $liste, $mid)
    {
        if (Env::has('mok')) {
            $action = 'accept';
        } elseif (Env::has('mno')) {
            $action = 'refuse';
        } elseif (Env::has('mdel')) {
            $action = 'delete';
        } else {
            return false;
        }
        Get::kill('mid');
        return XDB::execute("INSERT IGNORE INTO  ml_moderate
                                         VALUES  ({?}, {?}, {?}, {?}, {?}, NOW(), {?}, NULL)",
                            $liste, $domain, $mid, S::i('uid'), $action, Post::v('reason'));
    }

    function handler_moderate(&$page, $liste = null)
    {
        if (is_null($liste)) {
             return PL_NOT_FOUND;
        }

        $domain = $this->prepare_client($page);

        $page->changeTpl('lists/moderate.tpl');

        $page->register_modifier('hdc', 'list_header_decode');

        if (Env::has('sadd') || Env::has('sdel')) {
            S::assert_xsrf_token();

            if (Env::has('sadd')) { /* 4 = SUBSCRIBE */
                $sub = $this->client->get_pending_sub($liste, Env::v('sadd'));
                $this->client->handle_request($liste,Env::v('sadd'),4,'');
                $info = "validée";
            }
            if (Post::has('sdel')) { /* 2 = REJECT */
                $sub = $this->client->get_pending_sub($liste, Env::v('sdel'));
                $this->client->handle_request($liste, Post::v('sdel'), 2, utf8_decode(Post::v('reason')));
                $info = "refusée";
            }
            if ($sub) {
                $mailer = new PlMailer();
                $mailer->setFrom("$liste-bounces@{$domain}");
                $mailer->addTo("$liste-owner@{$domain}");
                $mailer->addHeader('Reply-To', "$liste-owner@{$domain}");
                $mailer->setSubject("L'inscription de {$sub['name']} a été $info");
                $text = "L'inscription de {$sub['name']} à la liste $liste@{$domain} a été $info par " . S::v('prenom')  . ' '
                      . S::v('nom') . '(' . S::v('promo') . ")\n";
                if (trim(Post::v('reason'))) {
                    $text .= "\nLa raison invoquée est :\n" . Post::v('reason');
                }
                $mailer->setTxtBody(wordwrap($text, 72));
                $mailer->send();
            }
            if (Env::has('sadd')) {
                pl_redirect('lists/moderate/'.$liste);
            }
        }

        if (Post::has('moderate_mails') && Post::has('select_mails')) {
            S::assert_xsrf_token();

            $mails = array_keys(Post::v('select_mails'));
            foreach($mails as $mail) {
                $this->moderate_mail($domain, $liste, $mail);
            }
        } elseif (Env::has('mid')) {
            if (Get::has('mid') && !Env::has('mok') && !Env::has('mdel')) {
                $page->changeTpl('lists/moderate_mail.tpl');
                require_once('banana/moderate.inc.php');
                $params = array('listname' => $liste, 'domain' => $domain,
                                'artid' => Get::i('mid'), 'part' => Get::v('part'), 'action' => Get::v('action'));
                $params['client'] = $this->client;
                run_banana($page, 'ModerationBanana', $params);

                $msg = file_get_contents('/etc/mailman/fr/refuse.txt');
                $msg = str_replace("%(adminaddr)s", "$liste-owner@{$domain}", $msg);
                $msg = str_replace("%(request)s",   "<< SUJET DU MAIL >>",    $msg);
                $msg = str_replace("%(reason)s",    "<< TON EXPLICATION >>",  $msg);
                $msg = str_replace("%(listname)s",  $liste, $msg);
                $page->assign('msg', $msg);
                return;
            }

            $mail = $this->moderate_mail($domain, $liste, Env::i('mid'));
        } elseif (Env::has('sid')) {
            if (list($subs,$mails) = $this->get_pending_ops($domain, $liste)) {
                foreach($subs as $user) {
                    if ($user['id'] == Env::v('sid')) {
                        $page->changeTpl('lists/moderate_sub.tpl');
                        $page->assign('del_user', $user);
                        return;
                    }
                }
            }

        }

        if (list($subs,$mails) = $this->get_pending_ops($domain, $liste)) {
            foreach ($mails as $key=>$mail) {
                $mails[$key]['stamp'] = strftime("%Y%m%d%H%M%S", $mail['stamp']);
                if ($mail['fromx']) {
                    $page->assign('with_fromx', true);
                } else {
                    $page->assign('with_nonfromx', true);
                }
            }
            $page->assign_by_ref('subs', $subs);
            $page->assign_by_ref('mails', $mails);
        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit de la modérer");
        }
    }

    static public function no_login_callback($login)
    {
        require_once 'user.func.inc.php';
        global $list_unregistered, $globals;

        $users = get_not_registered_user($login, true);
        if ($users && $users->total()) {
            if (!isset($list_unregistered)) {
                $list_unregistered = array();
            }
            $list_unregistered[$login] = $users;
        } else {
            list($name, $dom) = @explode('@', $login);
            if ($dom == $globals->mail->domain || $dom == $globals->mail->domain2) {
                _default_user_callback($login);
            }
        }
    }

    function handler_admin(&$page, $liste = null)
    {
        global $globals;

        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $domain = $this->prepare_client($page);

        $page->changeTpl('lists/admin.tpl');

        if (Env::has('send_mark')) {
            S::assert_xsrf_token();

            $actions = Env::v('mk_action');
            $uids    = Env::v('mk_uid');
            $mails   = Env::v('mk_email');
            foreach ($actions as $key=>$action) {
                switch ($action) {
                  case 'none':
                    break;

                  case 'marketu': case 'markets':
                    require_once 'emails.inc.php';
                    $mail = valide_email($mails[$key]);
                    if (isvalid_email_redirection($mail)) {
                        $from = ($action == 'marketu') ? 'user' : 'staff';
                        $market = Marketing::get($uids[$key], $mail);
                        if (!$market) {
                            $market = new Marketing($uids[$key], $mail, 'list', "$liste@$domain", $from, S::v('uid'));
                            $market->add();
                            break;
                        }
                    }

                  default:
                    XDB::execute('INSERT IGNORE INTO  register_subs (uid, type, sub, domain)
                                              VALUES  ({?}, \'list\', {?}, {?})',
                                  $uids[$key], $liste, $domain);
                }
            }
        }

        if (Env::has('add_member')) {
            S::assert_xsrf_token();

            require_once('user.func.inc.php');
            $members = get_users_forlife_list(Env::v('add_member'),
                                              false,
                                              array('ListsModule', 'no_login_callback'));
            $arr = $this->client->mass_subscribe($liste, $members);
            if (is_array($arr)) {
                foreach($arr as $addr) {
                    $page->trigSuccess("{$addr[0]} inscrit.");
                }
            }
        }

        if (isset($_FILES['add_member_file']) && $_FILES['add_member_file']['tmp_name']) {
            S::assert_xsrf_token();

            $upload =& PlUpload::get($_FILES['add_member_file'], S::v('forlife'), 'list.addmember', true);
            if (!$upload) {
                $page->trigError('Une erreur s\'est produite lors du téléchargement du fichier');
            } else {
                $members = get_users_forlife_list($upload->getContents(),
                                                  false,
                                                  array('ListsModule', 'no_login_callback'));
                $arr = $this->client->mass_subscribe($liste, $members);
                if (is_array($arr)) {
                    foreach($arr as $addr) {
                        $page->trigSuccess("{$addr[0]} inscrit.");
                    }
                }
            }
        }

        if (Env::has('del_member')) {
            S::assert_xsrf_token();

            if (strpos(Env::v('del_member'), '@') === false) {
                $this->client->mass_unsubscribe(
                    $liste, array(Env::v('del_member').'@'.$globals->mail->domain));
            } else {
                $this->client->mass_unsubscribe($liste, array(Env::v('del_member')));
            }
            pl_redirect('lists/admin/'.$liste);
        }

        if (Env::has('add_owner')) {
            S::assert_xsrf_token();

            require_once('user.func.inc.php');
            $owners = get_users_forlife_list(Env::v('add_owner'), false, array('ListsModule', 'no_login_callback'));
            if ($owners) {
                foreach ($owners as $login) {
                    if ($this->client->add_owner($liste, $login)) {
                        $page->trigSuccess($alias." ajouté aux modérateurs.");
                    }
                }
            }
        }

        if (Env::has('del_owner')) {
            S::assert_xsrf_token();

            if (strpos(Env::v('del_owner'), '@') === false) {
                $this->client->del_owner($liste, Env::v('del_owner').'@'.$globals->mail->domain);
            } else {
                $this->client->del_owner($liste, Env::v('del_owner'));
            }
            pl_redirect('lists/admin/'.$liste);
        }

        if (list($det,$mem,$own) = $this->client->get_members($liste)) {
            global $list_unregistered;
            if ($list_unregistered) {
                $page->assign_by_ref('unregistered', $list_unregistered);
            }
            $membres = list_sort_members($mem, @$tri_promo);
            $moderos = list_sort_owners($own, @$tri_promo);

            $page->assign_by_ref('details', $det);
            $page->assign_by_ref('members', $membres);
            $page->assign_by_ref('owners',  $moderos);
            $page->assign('np_m', count($mem));

        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit de l'administrer.<br />"
                       ." Si tu penses qu'il s'agit d'une erreur, "
                       ."<a href='mailto:support@polytechnique.org'>contact le support</a>");
        }
    }

    function handler_options(&$page, $liste = null)
    {
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $this->prepare_client($page);

        $page->changeTpl('lists/options.tpl');

        if (Post::has('submit')) {
            S::assert_xsrf_token();

            $values = $_POST;
            $values = array_map('utf8_decode', $values);
            $spamlevel = intval($values['bogo_level']);
            $unsurelevel = intval($values['unsure_level']);
            if ($spamlevel == 0) {
                $unsurelevel = 0;
            }
            if ($spamlevel > 3 || $spamlevel < 0 || $unsurelevel < 0 || $unsurelevel > 1) {
                $page->trigError("Réglage de l'antispam non valide");
            } else {
                $this->client->set_bogo_level($liste, ($spamlevel << 1) + $unsurelevel);
            }
            switch($values['moderate']) {
                case '0':
                    $values['generic_nonmember_action']  = 0;
                    $values['default_member_moderation'] = 0;
                    break;
                case '1':
                    $values['generic_nonmember_action']  = 1;
                    $values['default_member_moderation'] = 0;
                    break;
                case '2':
                    $values['generic_nonmember_action']  = 1;
                    $values['default_member_moderation'] = 1;
                    break;
            }
            unset($values['submit'], $values['bogo_level'], $values['moderate']);
            $values['send_goodbye_msg']      = !empty($values['send_goodbye_msg']);
            $values['admin_notify_mchanges'] = !empty($values['admin_notify_mchanges']);
            $values['subscribe_policy']      = empty($values['subscribe_policy']) ? 0 : 2;
            if (isset($values['subject_prefix'])) {
                $values['subject_prefix'] = trim($values['subject_prefix']).' ';
            }
            $this->client->set_owner_options($liste, $values);
        } elseif (isvalid_email(Post::v('atn_add'))) {
            S::assert_xsrf_token();
            $this->client->add_to_wl($liste, Post::v('atn_add'));
        } elseif (Get::has('atn_del')) {
            S::assert_xsrf_token();
            $this->client->del_from_wl($liste, Get::v('atn_del'));
            pl_redirect('lists/options/'.$liste);
        }

        if (list($details,$options) = $this->client->get_owner_options($liste)) {
            $page->assign_by_ref('details', $details);
            $page->assign_by_ref('options', $options);
            $bogo_level = intval($this->client->get_bogo_level($liste));
            $page->assign('unsure_level', $bogo_level & 1);
            $page->assign('bogo_level', $bogo_level >> 1);
        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit de l'administrer");
        }
    }

    function handler_delete(&$page, $liste = null)
    {
        global $globals;
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $domain = $this->prepare_client($page);
        if ($domain == $globals->mail->domain || $domain == $globals->mail->domain2) {
            $domain = '';
            $table  = 'aliases';
            $type   = 'liste';
        } else {
            $domain = '@' . $domain;
            $table  = 'virtual';
            $type   = 'list';
        }

        $page->changeTpl('lists/delete.tpl');
        if (Post::v('valid') == 'OUI') {
            S::assert_xsrf_token();

            if ($this->client->delete_list($liste, Post::b('del_archive'))) {
                foreach (array('', '-owner', '-admin', '-bounces', '-unsubscribe') as $app) {
                    XDB::execute("DELETE FROM  $table
                                        WHERE  type={?} AND alias={?}",
                                 $type, $liste.$app.$domain);
                }
                $page->assign('deleted', true);
            } else {
                $page->kill('Une erreur est survenue lors de la suppression de la liste.<br />'
                         . 'Contact les administrateurs du site pour régler le problème : '
                         . '<a href="mailto:support@polytechnique.org">support@polytechnique.org</a>');
            }
        } elseif (list($details,$options) = $this->client->get_owner_options($liste)) {
            $page->assign_by_ref('details', $details);
            $page->assign_by_ref('options', $options);
            $page->assign('bogo_level', $this->client->get_bogo_level($liste));
        } else {
            $page->kill("La liste n'existe pas ou tu n'as pas le droit de l'administrer");
        }
    }

    function handler_soptions(&$page, $liste = null)
    {
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $this->prepare_client($page);

        $page->changeTpl('lists/soptions.tpl');

        if (Post::has('submit')) {
            S::assert_xsrf_token();

            $values = $_POST;
            $values = array_map('utf8_decode', $values);
            unset($values['submit']);
            $values['advertised'] = empty($values['advertised']) ? false : true;
            $values['archive'] = empty($values['archive']) ? false : true;
            $this->client->set_admin_options($liste, $values);
        }

        if (list($details,$options) = $this->client->get_admin_options($liste)) {
            $page->assign_by_ref('details', $details);
            $page->assign_by_ref('options', $options);
        } else {
            $page->kill("La liste n'existe pas");
        }
    }

    function handler_check(&$page, $liste = null)
    {
        if (is_null($liste)) {
            return PL_NOT_FOUND;
        }

        $this->prepare_client($page);

        $page->changeTpl('lists/check.tpl');

        if (Post::has('correct')) {
            S::assert_xsrf_token();
            $this->client->check_options($liste, true);
        }

        if (list($details,$options) = $this->client->check_options($liste)) {
            $page->assign_by_ref('details', $details);
            $page->assign_by_ref('options', $options);
        } else {
            $page->kill("La liste n'existe pas");
        }
    }

    function handler_admin_all(&$page) {
        $page->changeTpl('lists/admin_all.tpl');
        $page->assign('xorg_title','Polytechnique.org - Administration - Mailing lists');

        $client = new MMList(S::v('uid'), S::v('password'));
        $listes = $client->get_all_lists();
        $page->assign_by_ref('listes', $listes);
    }
}

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker enc=utf-8:
?>
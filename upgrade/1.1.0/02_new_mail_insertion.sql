INSERT INTO  email_source_account (uid, email, type, flags, expire)
     SELECT  uid, CONCAT(alias, '@polytechnique.org'), IF(type = 'a_vie', 'forlife', 'alias'), REPLACE(flags, 'epouse', 'marital'), expire
       FROM  aliases
      WHERE  type = 'a_vie' OR type = 'alias';
INSERT INTO  email_source_account (uid, email, type, flags, expire)
     SELECT  uid, CONCAT(alias, '@m4x.org'), IF(type = 'a_vie', 'forlife', 'alias'), REPLACE(flags, 'epouse', 'marital'), expire
       FROM  aliases
      WHERE  type = 'a_vie' OR type = 'alias';
INSERT INTO  email_source_account (uid, email, type)
     SELECT  a.uid, v.alias, 'alias'
       FROM  virtual          AS v
  LEFT JOIN  virtual_redirect AS vr ON (v.vid = vr.vid)
  LEFT JOIN  accounts         AS a  ON (a.hruid = LEFT(vr.redirect, LOCATE('@', vr.redirect)-1))
      WHERE  v.type = 'user' AND v.alias LIKE '%@melix.net' AND a.uid IS NOT NULL;
INSERT INTO  email_source_account (uid, email, type)
     SELECT  a.uid, REPLACE(v.alias, '@melix.net', '@melix.org'), 'alias'
       FROM  virtual          AS v
  LEFT JOIN  virtual_redirect AS vr ON (v.vid = vr.vid)
  LEFT JOIN  accounts         AS a  ON (a.hruid = LEFT(vr.redirect, LOCATE('@', vr.redirect)-1))
      WHERE  v.type = 'user' AND v.alias LIKE '%@melix.net' AND a.uid IS NOT NULL;

INSERT INTO  email_source_other (hrmid, email, type)
     SELECT  CONCAT(CONCAT('h.', alias), '.polytechnique.org'), CONCAT(alias, '@polytechnique.org'), 'homonym'
       FROM  aliases
      WHERE  type = 'homonyme'
   GROUP BY  (alias);
INSERT INTO  email_source_other (hrmid, email, type)
     SELECT  CONCAT(CONCAT('h.', alias), '.polytechnique.org'), CONCAT(alias, '@m4x.org'), 'homonym'
       FROM  aliases
      WHERE  type = 'homonyme'
   GROUP BY  (alias);

INSERT INTO  homonyms_list (hrmid, uid)
     SELECT  CONCAT(CONCAT('h.', a.alias), '.polytechnique.org'), h.uid
       FROM  homonyms AS h
 INNER JOIN  aliases  AS a ON (a.uid = h.homonyme_id)
      WHERE  a.type = 'homonyme';

INSERT INTO  email_redirect_account (uid, redirect, rewrite, type, action, broken_date, broken_level, last, flags, hash, allow_rewrite)
     SELECT  a.uid, e.email, e.rewrite, 'smtp', ef.email, e.panne, e.panne_level, e.last,
             IF(e.flags = '', 'inactive', IF(e.flags = 'disable', 'disabled', IF(e.flags = 'panne', 'broken', e.flags))), e.hash, e.allow_rewrite
       FROM  emails   AS e
  LEFT JOIN  emails   AS ef ON (e.uid = ef.uid)
  LEFT JOIN  accounts AS a  ON (e.uid = a.uid)
      WHERE  e.flags != 'filter' AND ef.flags = 'filter';
INSERT INTO  email_redirect_account (uid, redirect, type, action, flags)
     SELECT  a.uid, CONCAT(a.hruid, '@g.polytechnique.org'), 'googleapps', ef.email, 'active'
       FROM  email_options AS eo
  LEFT JOIN  accounts      AS a  ON (a.uid = eo.uid)
  LEFT JOIN  emails        AS ef ON (eo.uid = ef.uid)
      WHERE  FIND_IN_SET('googleapps', eo.storage) AND ef.flags = 'filter';
INSERT INTO  email_redirect_account (uid, redirect, type, action, flags)
     SELECT  a.uid, CONCAT(a.hruid, '@imap.polytechnique.org'), 'imap', 'let_spams', 'active'
       FROM  email_options AS eo
  LEFT JOIN  accounts      AS a ON (a.uid = eo.uid)
      WHERE  FIND_IN_SET('imap', eo.storage);

INSERT INTO  email_redirect_other (hrmid, type, action)
     SELECT  hrmid, 'homonym', 'homonym'
       FROM  email_source_other
      WHERE  type = 'homonym'
   GROUP BY  (hrmid);

INSERT INTO  email_virtual (email, redirect, type)
     SELECT  v.alias, vr.redirect, IF(v.type = 'dom', 'domain', IF(v.type = 'evt', 'event', v.type))
       FROM  virtual          AS v
  LEFT JOIN  virtual_redirect AS vr ON (vr.vid = v.vid)
      WHERE  v.alias NOT LIKE '%@melix.net' AND vr.vid IS NOT NULL AND v.alias != '@melix.org';
INSERT INTO  email_virtual (email, type, redirect)
     SELECT  CONCAT(alias, '@polytechnique.org'), 'list',
             CONCAT('polytechnique.org_', REPLACE(REPLACE(REPLACE(CONCAT(alias, '+post@listes.polytechnique.org'),
                                                                  '-admin+post', '+admin'),
                                                          '-owner+post', '+owner'),
                                                  '-bounces+post', '+bounces'))
       FROM  aliases
      WHERE  type = 'liste';
INSERT INTO  email_virtual (email, type, redirect)
     SELECT  CONCAT(alias, '@m4x.org'), 'list',
             CONCAT('polytechnique.org_', REPLACE(REPLACE(REPLACE(CONCAT(alias, '+post@listes.polytechnique.org'),
                                                                  '-admin+post', '+admin'),
                                                          '-owner+post', '+owner'),
                                                  '-bounces+post', '+bounces'))
       FROM  aliases
      WHERE  type = 'liste';
INSERT INTO  email_virtual (email, redirect, type)
     SELECT  v.alias, vr.redirect, 'user'
       FROM  virtual          AS v
  LEFT JOIN  virtual_redirect AS vr ON (v.vid = vr.vid)
  LEFT JOIN  accounts         AS a  ON (a.hruid = LEFT(vr.redirect, LOCATE('@', vr.redirect)-1))
      WHERE  v.type = 'user' AND v.alias LIKE '%@melix.net' AND vr.vid IS NOT NULL AND a.uid IS NULL;
INSERT INTO  email_virtual (email, redirect, type)
     SELECT  REPLACE(v.alias, '@melix.net', '@melix.org'), vr.redirect, 'user'
       FROM  virtual          AS v
  LEFT JOIN  virtual_redirect AS vr ON (v.vid = vr.vid)
  LEFT JOIN  accounts         AS a ON  (a.hruid = LEFT(vr.redirect, LOCATE('@', vr.redirect)-1))
      WHERE  v.type = 'user' AND v.alias LIKE '%@melix.net' AND vr.vid IS NOT NULL AND a.uid IS NULL;

-- Note: There are some adresses on virtual that have no match on the virtual_redirect.
--       The adresses in this situation are dropped.

INSERT INTO  email_virtual_domains (domain)
     VALUES  ('polytechnique.org'), ('m4x.org');
INSERT INTO  email_virtual_domains (domain)
     SELECT  domain
       FROM  virtual_domains;

-- From aliases file
INSERT INTO  email_virtual (email, redirect, type)
     VALUES  ('otrs.platal@polytechnique.org', 'otrs@svoboda.polytechnique.org', 'admin'),
             ('otrs.platal@m4x.org', 'otrs.platal@polytechnique.org', 'admin'),
             ('validation@polytechnique.org', 'hotliners@staff.polytechnique.org', 'admin'),
             ('validation@m4x.org', 'validation@polytechnique.org', 'admin'),
             ('listes+admin@polytechnique.org', 'br@staff.polytechnique.org', 'admin'),
             ('listes+admin@m4x.org', 'listes+admin@polytechnique.org', 'admin'),
             ('listes@polytechnique.org', 'otrs.platal+listes@polytechnique.org', 'admin'),
             ('listes@m4x.org', 'listes@polytechnique.org', 'admin'),
             ('gld@polytechnique.org', 'listes@polytechnique.org', 'admin'),
             ('gld@m4x.org', 'gld@polytechnique.org', 'admin'),
             ('support@polytechnique.org', 'otrs.platal+support@polytechnique.org', 'admin'),
             ('support@m4x.org', 'support@polytechnique.org', 'admin'),
             ('contact@polytechnique.org', 'otrs.platal+contact@polytechnique.org', 'admin'),
             ('contact@m4x.org', 'contact@polytechnique.org', 'admin'),
             ('register@polytechnique.org', 'otrs.platal+register@polytechnique.org', 'admin'),
             ('register@m4x.org', 'register@polytechnique.org', 'admin'),
             ('info@polytechnique.org', 'otrs.platal+info@polytechnique.org', 'admin'),
             ('info@m4x.org', 'info@polytechnique.org', 'admin'),
             ('bug@polytechnique.org', 'otrs.platal+bug@polytechnique.org', 'admin'),
             ('bug@m4x.org', 'bug@polytechnique.org', 'admin'),
             ('resetpass@polytechnique.org', 'otrs.platal+resetpass@polytechnique.org', 'admin'),
             ('resetpass@m4x.org', 'resetpass@polytechnique.org', 'admin'),
             ('association@polytechnique.org', 'otrs.platal+association@polytechnique.org', 'admin'),
             ('association@m4x.org', 'association@polytechnique.org', 'admin'),
             ('x-org@polytechnique.org', 'association@polytechnique.org', 'admin'),
             ('x-org@m4x.org', 'x-org@polytechnique.org', 'admin'),
             ('manageurs@polytechnique.org', 'otrs@support.manageurs.com', 'partner'),
             ('manageurs@m4x.org', 'manageurs@polytechnique.org', 'partner'),
             ('fondation@polytechnique.org', 'fondation@fondationx.org', 'partner'),
             ('fondation@m4x.org', 'fondation@polytechnique.org', 'partner'),
             ('ax@polytechnique.org', 'ax@wanadoo.fr', 'partner'),
             ('ax@m4x.org', 'ax@polytechnique.org', 'partner'),
             ('annuaire-ax@polytechnique.org', 'annuaire-ax@wanadoo.fr', 'partner'),
             ('annuaire-ax@m4x.org', 'annuaire-ax@polytechnique.org', 'partner'),
             ('ax-bdc@polytechnique.org', 'ax-bdc@wanadoo.fr', 'partner'),
             ('ax-bdc@m4x.org', 'ax-bdc@polytechnique.org', 'partner'),
             ('jaune@polytechnique.org', 'null@hruid.polytechnique.org', 'partner'),
             ('jaune@m4x.org', 'jaune@polytechnique.org', 'partner'),
             ('jaune+rouge@polytechnique.org', 'jaune_rouge@wanadoo.fr', 'partner'),
             ('jaune+rouge@m4x.org', 'jaune+rouge@polytechnique.org', 'partner'),
             ('xcourseaularge@polytechnique.org', 'info@xcourseaularge.polytechnique.org', 'partner'),
             ('xcourseaularge@m4x.org', 'xcourseaularge@polytechnique.org', 'partner'),
             ('xim@polytechnique.org', 'membres@x-internet.polytechnique.org', 'partner'),
             ('xim@m4x.org', 'xim@polytechnique.org', 'partner'),
             ('x-consult@polytechnique.org', 'info@x-consult.polytechnique.org', 'partner'),
             ('x-consult@m4x.org', 'x-consult@polytechnique.org', 'partner'),
             ('xmcb@polytechnique.org', 'xmcb@x-consult.polytechnique.org', 'partner'),
             ('xmcb@m4x.org', 'xmcb@polytechnique.org', 'partner'),
             ('x-maroc@polytechnique.org', 'allam@mtpnet.gov.ma', 'partner'),
             ('x-maroc@m4x.org', 'x-maroc@polytechnique.org', 'partner'),
             ('x-musique@polytechnique.org', 'xmusique@free.fr', 'partner'),
             ('x-musique@m4x.org', 'x-musique@polytechnique.org', 'partner'),
             ('x-resistance@polytechnique.org', 'info@xresistance.org', 'partner'),
             ('x-resistance@m4x.org', 'x-resistance@polytechnique.org', 'partner'),
             ('x-israel@polytechnique.org', 'info@x-israel.polytechnique.org', 'partner'),
             ('x-israel@m4x.org', 'x-israel@polytechnique.org', 'partner'),
             ('gpx@polytechnique.org', 'g.p.x@infonie.fr', 'partner'),
             ('gpx@m4x.org', 'gpx@polytechnique.org', 'partner'),
             ('g.p.x@polytechnique.org', 'gpx@polytechnique.org', 'partner'),
             ('g.p.x@m4x.org', 'g.p.x@polytechnique.org', 'partner'),
             ('pointgamma@polytechnique.org', 'gamma@frankiz.polytechnique.fr', 'partner'),
             ('pointgamma@m4x.org', 'pointgamma@polytechnique.org', 'partner'),
             ('xmpentrepreneur@polytechnique.org', 'xmp.entrepreneur@gmail.com', 'partner'),
             ('xmpentrepreneur@m4x.org', 'xmpentrepreneur@polytechnique.org', 'partner'),
             ('xmp-entrepreneur@polytechnique.org', 'xmp.entrepreneur@gmail.com', 'partner'),
             ('xmp-entrepreneur@m4x.org', 'xmp-entrepreneur@polytechnique.org', 'partner'),
             ('xmpangels@polytechnique.org', 'xmpangels@xmp-ba.m4x.org', 'partner'),
             ('xmpangels@m4x.org', 'xmpangels@polytechnique.org', 'partner'),
             ('xmp-angels@polytechnique.org', 'xmpangels@xmp-ba.m4x.org', 'partner'),
             ('xmp-angels@m4x.org', 'xmp-angels@polytechnique.org', 'partner'),
             ('relex@polytechnique.org', 'relex@staff.polytechnique.org', 'admin'),
             ('relex@m4x.org', 'relex@polytechnique.org', 'admin'),
             ('tresorier@polytechnique.org', 'tresorier@staff.polytechnique.org', 'admin'),
             ('tresorier@m4x.org', 'tresorier@polytechnique.org', 'admin'),
             ('aaege-sso@polytechnique.org', 'aaege-sso@staff.polytechnique.org', 'admin'),
             ('aaege-sso@m4x.org', 'aaege-sso@polytechnique.org', 'admin'),
             ('innovation@polytechnique.org', 'innovation@staff.polytechnique.org', 'admin'),
             ('innovation@m4x.org', 'innovation@polytechnique.org', 'admin'),
             ('groupes@polytechnique.org', 'groupes@staff.polytechnique.org', 'admin'),
             ('groupes@m4x.org', 'groupes@polytechnique.org', 'admin'),
             ('br@polytechnique.org', 'br@staff.polytechnique.org', 'admin'),
             ('br@m4x.org', 'br@polytechnique.org', 'admin'),
             ('ca@polytechnique.org', 'ca@staff.polytechnique.org', 'admin'),
             ('ca@m4x.org', 'ca@polytechnique.org', 'admin'),
             ('personnel@polytechnique.org', 'br@staff.polytechnique.org', 'admin'),
             ('personnel@m4x.org', 'personnel@polytechnique.org', 'admin'),
             ('cil@polytechnique.org', 'cil@staff.polytechnique.org', 'admin'),
             ('cil@m4x.org', 'cil@polytechnique.org', 'admin'),
             ('opensource@polytechnique.org', 'contact@polytechnique.org', 'admin'),
             ('opensource@m4x.org', 'opensource@polytechnique.org', 'admin'),
             ('forums@polytechnique.org', 'forums@staff.m4x.org', 'admin'),
             ('forums@m4x.org', 'forums@polytechnique.org', 'admin'),
             ('telepaiement@polytechnique.org', 'telepaiement@staff.m4x.org', 'admin'),
             ('telepaiement@m4x.org', 'telepaiement@polytechnique.org', 'admin'),
             ('hotliners@polytechnique.org', 'hotliners@staff.m4x.org', 'admin'),
             ('hotliners@m4x.org', 'hotliners@polytechnique.org', 'admin'),
             ('kes@polytechnique.org', 'kes@frankiz.polytechnique.fr', 'partner'),
             ('kes@m4x.org', 'kes@polytechnique.org', 'partner'),
             ('kes1999@polytechnique.org', 'cariokes@polytechnique.org', 'partner'),
             ('kes1999@m4x.org', 'kes1999@polytechnique.org', 'partner'),
             ('kes2000@polytechnique.org', 'kestinpowers@polytechnique.org', 'partner'),
             ('kes2000@m4x.org', 'kes2000@polytechnique.org', 'partner');

INSERT INTO  email_source_other (hrmid, email, type)
     VALUES  ('ax.test.polytechnique.org', 'AX-test@polytechnique.org', 'ax'),
             ('ax.test.polytechnique.org', 'AX-test@m4x.org', 'ax'),
             ('ax.nicolas.zarpas.polytechnique.org', 'AX-nicolas.zarpas@polytechnique.org', 'ax'),
             ('ax.nicolas.zarpas.polytechnique.org', 'AX-nicolas.zarpas@m4x.org', 'ax'),
             ('ax.carrieres.polytechnique.org', 'AX-carrieres@polytechnique.org', 'ax'),
             ('ax.carrieres.polytechnique.org', 'AX-carrieres@m4x.org', 'ax'),
             ('ax.info1.polytechnique.org', 'AX-info1@polytechnique.org', 'ax'),
             ('ax.info1.polytechnique.org', 'AX-info1@m4x.org', 'ax'),
             ('ax.info2.polytechnique.org', 'AX-info2@polytechnique.org', 'ax'),
             ('ax.info2.polytechnique.org', 'AX-info2@m4x.org', 'ax'),
             ('ax.bal.polytechnique.org', 'AX-bal@polytechnique.org', 'ax'),
             ('ax.bal.polytechnique.org', 'AX-bal@m4x.org', 'ax'),
             ('ax.annuaire.polytechnique.org', 'AX-annuaire@polytechnique.org', 'ax'),
             ('ax.annuaire.polytechnique.org', 'AX-annuaire@m4x.org', 'ax'),
             ('ax.jaune-rouge.polytechnique.org', 'AX-jaune-rouge@polytechnique.org', 'ax'),
             ('ax.jaune-rouge.polytechnique.org', 'AX-jaune-rouge@m4x.org', 'ax'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'jean-pierre.bilah.1980@polytechnique.org', 'honeypot'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'jean-pierre.bilah.1980@m4x.org', 'honeypot'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'jean-pierre.blah.1980@polytechnique.org', 'honeypot'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'jean-pierre.blah.1980@m4x.org', 'honeypot');

INSERT INTO  email_redirect_other (hrmid, redirect, type, action)
     VALUES  ('ax.nicolas.zarpas.polytechnique.org', 'nicolas.zarpas-ax@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.carrieres.polytechnique.org', 'manuela.brasseur-bdc@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.info1.polytechnique.org', 'sylvie.clairefond-ax@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.info2.polytechnique.org', 'catherine.perot-ax@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.bal.polytechnique.org', 'baldelx-ax@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.annuaire.polytechnique.org', 'annuaire-ax@wanadoo.fr', 'smtp', 'tag_spams'),
             ('ax.jaune-rouge.polytechnique.org', 'jaune_rouge@wanadoo.fr', 'smtp', 'tag_spams'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'jean-pierre.bilah.1980.mbox@murphy.m4x.org', 'smtp', 'let_spams'),
             ('honey.jean-pierre.bilah.1980.polytechnique.org', 'raphael.barrois.2006@polytechnique.org', 'smtp', 'let_spams');

-- Drop renamed list
DELETE FROM email_virtual WHERE email LIKE 'tech-email%@polytechnique.org';
DELETE FROM email_virtual WHERE email LIKE 'tech-email%@m4x.org';

-- Imap and bounce
UPDATE  email_redirect_account AS e,
        (SELECT  IF(SUM(IF(type != 'imap', 1, 0)) = 0, 'imap_only', 'normal') AS status, uid
           FROM  email_redirect_account
          WHERE  flags = 'active'
       GROUP BY  uid) AS sub
   SET  e.action = 'imap_and_bounce'
 WHERE  sub.status = 'imap_only' AND sub.uid = e.uid AND type = 'imap';

-- vim:set syntax=mysql:
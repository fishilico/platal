ALTER TABLE auth_user_md5 ADD COLUMN mail_storage SET('imap', 'googleapps') DEFAULT '' NOT NULL AFTER smtppass;
UPDATE auth_user_md5 SET mail_storage = 'imap';

# vim:set syntax=mysql:

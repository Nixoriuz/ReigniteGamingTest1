<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/


/* Downloads upgrade */

$SQL[] = "UPDATE core_sys_conf_settings SET conf_value='disk' WHERE conf_key='idm_filestorage' AND conf_value IN ('web','nonweb');";
$SQL[] = "UPDATE downloads_files_records SET record_storagetype='disk' WHERE record_storagetype IN ('web','nonweb');";
$SQL[] = "ALTER TABLE downloads_files_records CHANGE record_storagetype record_storagetype varchar(24) NOT NULL default 'disk';";

$SQL[] = "ALTER TABLE core_groups ADD idm_throttling INT NOT NULL DEFAULT '0',
	ADD idm_wait_period INT NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE downloads_categories ADD ctags_disabled TINYINT NOT NULL DEFAULT '0',
	ADD ctags_noprefixes TINYINT NOT NULL DEFAULT '0',
	ADD ctags_predefined TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE downloads_comments DROP INDEX comment_fid,
	ADD INDEX comment_fid ( comment_fid , comment_date );";
	
$SQL[] = "ALTER TABLE downloads_files DROP INDEX file_cat,
	ADD INDEX file_cat ( file_cat , file_updated );";

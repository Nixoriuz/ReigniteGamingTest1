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

$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN( 'idm_enablerandom' );";

$SQL[] = "ALTER TABLE downloads_comments CHANGE ip_address ip_address VARCHAR( 46 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_downloads CHANGE dip dip VARCHAR( 46 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files CHANGE file_ipaddress file_ipaddress VARCHAR( 46 ) NOT NULL DEFAULT '0',
	DROP INDEX file_submitter,
	ADD INDEX file_submitter ( file_submitter, file_open, file_updated );";
$SQL[] = "ALTER TABLE downloads_sessions CHANGE dsess_ip dsess_ip VARCHAR( 46 ) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_urls CHANGE url_ip url_ip VARCHAR( 46 ) NULL DEFAULT NULL;";

$SQL[] = "UPDATE core_applications SET app_title='Downloads' WHERE app_directory='downloads';";

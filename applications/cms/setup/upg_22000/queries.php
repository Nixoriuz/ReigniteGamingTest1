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

/* IP.Content upgrade */

$SQL[] = "ALTER TABLE ccs_database_comments CHANGE comment_ip_address comment_ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0',
	CHANGE comment_date comment_date INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_database_ratings CHANGE rating_ip_address rating_ip_address VARCHAR( 46 ) NOT NULL DEFAULT '0',
	CHANGE rating_added rating_added INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_block_wizard CHANGE wizard_started wizard_started INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "UPDATE ccs_database_categories SET category_last_record_date=0 WHERE category_last_record_date='';";
$SQL[] = "ALTER TABLE ccs_database_categories CHANGE category_last_record_date category_last_record_date INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_database_revisions CHANGE revision_date revision_date INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_folders CHANGE last_modified last_modified INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_pages CHANGE page_last_edited page_last_edited INT( 10 ) NOT NULL DEFAULT '0',
	CHANGE page_cache_last page_cache_last INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_page_templates CHANGE template_updated template_updated INT( 10 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE ccs_page_wizard CHANGE wizard_started wizard_started INT( 10 ) NOT NULL DEFAULT '0';";


$SQL[]	= "UPDATE core_permission_index SET perm_type='categories' WHERE app='ccs' AND perm_type='cat';";
$SQL[]	= "UPDATE core_permission_index SET perm_type='databases' WHERE app='ccs' AND perm_type='database';";
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

$SQL[] = "ALTER TABLE ccs_pages DROP page_description,
	ADD page_title VARCHAR( 255 ) NULL DEFAULT NULL,
	DROP INDEX page_seo_name,
	ADD INDEX page_seo_name ( page_seo_name (100), page_folder (100) ),
	ADD page_quicknav TINYINT NOT NULL DEFAULT '1';";
	
$SQL[] = "ALTER TABLE ccs_page_wizard DROP wizard_description, 
	ADD INDEX ( wizard_started ),
	ADD wizard_page_title VARCHAR( 255 ) NULL DEFAULT NULL,
	ADD wizard_page_quicknav TINYINT NOT NULL DEFAULT '1';";
	
$SQL[] = "ALTER TABLE ccs_block_wizard ADD INDEX ( wizard_started );";

$SQL[] = "ALTER TABLE ccs_databases DROP database_meta_keywords, 
	DROP database_meta_description,
	ADD database_rss_cached INT NOT NULL DEFAULT '0' AFTER database_rss_cache,
	ADD database_tags_enabled TINYINT NOT NULL DEFAULT '0',
	ADD database_tags_noprefixes TINYINT NOT NULL DEFAULT '0',
	ADD database_tags_predefined TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE ccs_database_categories ADD category_rss_cached INT NOT NULL DEFAULT '0' AFTER category_rss_cache, 
	ADD category_rss_exclude TINYINT NOT NULL DEFAULT '0' AFTER category_rss_cached,
	ADD category_page_title VARCHAR( 255 ) NULL DEFAULT NULL,
	ADD category_record_comments INT NOT NULL DEFAULT '0' AFTER category_records_queued,
	ADD category_record_comments_queued INT NOT NULL DEFAULT '0' AFTER category_record_comments,
	ADD category_tags_override TINYINT NOT NULL DEFAULT '0',
	ADD category_tags_enabled TINYINT NOT NULL DEFAULT '0',
	ADD category_tags_noprefixes TINYINT NOT NULL DEFAULT '0',
	ADD category_tags_predefined TEXT NULL DEFAULT NULL;";

$SQL[] = "ALTER TABLE ccs_database_moderators ADD moderator_edit_comment TINYINT NOT NULL DEFAULT '0',
	ADD moderator_restore_revision TINYINT NOT NULL DEFAULT '0',
	ADD moderator_extras TINYINT NOT NULL DEFAULT '0';";

$SQL[] = "ALTER TABLE ccs_database_comments ADD comment_author VARCHAR( 255 ) NULL DEFAULT NULL,
	ADD comment_edit_date INT NOT NULL DEFAULT '0';";

$SQL[] = "UPDATE core_sys_conf_settings SET conf_value='' WHERE conf_key='ccs_template_type' AND conf_value='codepress';";

$SQL[] = "DELETE FROM ccs_template_blocks WHERE tpb_name='block__watched_content';";

$SQL[] = "ALTER TABLE ccs_template_blocks ADD tpb_human_name VARCHAR(255) NULL DEFAULT NULL,
	ADD tpb_app_type VARCHAR(30) NULL DEFAULT NULL,
	ADD tpb_content_type VARCHAR(30) NULL DEFAULT NULL,
	ADD tpb_image VARCHAR(255) NULL DEFAULT NULL,
	ADD tpb_position int NOT NULL DEFAULT 0,
	ADD tpb_protected TINYINT NOT NULL DEFAULT 0,
  ADD tpb_desc TEXT NULL DEFAULT NULL;";

$SQL[] = "CREATE TABLE ccs_slug_memory (
memory_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
memory_url VARCHAR( 255 ) NULL DEFAULT NULL ,
memory_type VARCHAR( 32 ) NULL DEFAULT NULL ,
memory_type_id INT NOT NULL DEFAULT '0',
memory_type_id_2 INT NOT NULL DEFAULT '0',
KEY ( memory_url )
);";

$SQL[] = "DROP TABLE ccs_database_notifications;";

$SQL[] = "CREATE TABLE ccs_menus (
  menu_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  menu_parent_id INT NOT NULL DEFAULT '0',
  menu_title VARCHAR( 255 ) NULL DEFAULT NULL ,
  menu_url TEXT NULL DEFAULT NULL ,
  menu_submenu INT NOT NULL DEFAULT '0',
  menu_position VARCHAR( 255 ) NOT NULL DEFAULT '0',
  menu_description TEXT NULL DEFAULT NULL ,
  menu_attributes TEXT NULL DEFAULT NULL ,
  menu_permissions TEXT NULL DEFAULT NULL ,
  INDEX ( menu_parent_id, menu_position(150) ),
  INDEX ( menu_position(150) )
);";

$SQL[] = "ALTER TABLE ccs_blocks ADD block_template int NOT NULL DEFAULT 0 AFTER block_key, ADD INDEX ( block_template );";

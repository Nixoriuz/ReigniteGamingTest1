<?php

$SQL[]	= "ALTER TABLE ccs_database_fields ADD field_validator TEXT NULL DEFAULT NULL, ADD field_topic_format TEXT NULL DEFAULT NULL;";

$SQL[]	= "ALTER TABLE ccs_databases ADD database_rss VARCHAR( 255 ) NOT NULL DEFAULT '0',
ADD database_rss_cache MEDIUMTEXT NULL DEFAULT NULL ,
ADD database_field_content VARCHAR( 255 ) NULL DEFAULT NULL,
ADD database_lang_sl VARCHAR( 255 ) NOT NULL DEFAULT '',
ADD database_lang_pl VARCHAR( 255 ) NOT NULL DEFAULT '',
ADD database_lang_su VARCHAR( 255 ) NOT NULL DEFAULT '',
ADD database_lang_pu VARCHAR( 255 ) NOT NULL DEFAULT '',
ADD database_comment_bump TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD database_featured_article INT NOT NULL DEFAULT '0',
ADD database_is_articles TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD database_meta_keywords TEXT NULL DEFAULT NULL,
ADD database_meta_description TEXT NULL DEFAULT NULL,
ADD database_forum_record TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD database_forum_comments TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD database_forum_delete TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD database_forum_forum MEDIUMINT NOT NULL DEFAULT '0',
ADD database_forum_prefix VARCHAR( 255 ) NULL DEFAULT NULL,
ADD database_forum_suffix VARCHAR( 255 ) NULL DEFAULT NULL,
ADD INDEX ( database_is_articles );";

$SQL[]	= "ALTER TABLE ccs_database_categories ADD category_rss VARCHAR( 255 ) NULL DEFAULT '0',
ADD category_rss_cache MEDIUMTEXT NULL DEFAULT NULL,
ADD category_furl_name VARCHAR( 255 ) NULL DEFAULT NULL,
ADD category_meta_keywords TEXT NULL DEFAULT NULL,
ADD category_meta_description TEXT NULL DEFAULT NULL,
ADD category_template INT NOT NULL DEFAULT '0',
ADD category_forum_override TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD category_forum_record TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD category_forum_comments TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD category_forum_delete TINYINT( 1 ) NOT NULL DEFAULT '0',
ADD category_forum_forum MEDIUMINT NOT NULL DEFAULT '0',
ADD category_forum_prefix VARCHAR( 255 ) NULL DEFAULT NULL,
ADD category_forum_suffix VARCHAR( 255 ) NULL DEFAULT NULL,
ADD INDEX ( category_template );";

$SQL[]	= "ALTER TABLE ccs_database_categories ADD category_records_queued INT NOT NULL DEFAULT '0' AFTER category_records;";

$SQL[]	= "CREATE TABLE ccs_database_notifications (
  notify_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  notify_member INT NOT NULL default '0',
  notify_database INT NOT NULL default '0',
  notify_record INT NOT NULL default '0',
  notify_category INT NOT NULL default '0',
  notify_start INT NOT NULL default '0',
  notify_last_sent INT NOT NULL default '0',
  KEY notify_member (notify_member),
  KEY notify_category (notify_category,notify_database),
  KEY notify_record (notify_record,notify_database)
);";

$SQL[]	= "CREATE TABLE ccs_database_modqueue (
  mod_id INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
  mod_database INT NOT NULL default '0',
  mod_record INT NOT NULL default '0',
  mod_comment INT NOT NULL default '0',
  mod_poster INT NOT NULL default '0',
  KEY mod_database (mod_database,mod_record,mod_comment)
);";

$SQL[]	= "ALTER TABLE ccs_database_moderators ADD moderator_add_record TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[]	= "DELETE FROM ccs_template_cache WHERE cache_type='block';";
$SQL[]	= "ALTER TABLE ccs_template_cache DROP index cache_type;";
$SQL[]	= "ALTER TABLE ccs_template_cache ADD UNIQUE INDEX cache_type (cache_type,cache_type_id);";


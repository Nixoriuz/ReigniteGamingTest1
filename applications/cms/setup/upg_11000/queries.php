<?php

$SQL[]	= "UPDATE core_applications SET app_title='IP.Content' WHERE app_directory='ccs';";

$SQL[]	= "ALTER TABLE ccs_pages ADD INDEX ( page_content_type );";

$SQL[]	= "CREATE TABLE ccs_database_fields (
  field_id int(11) NOT NULL auto_increment,
  field_database_id mediumint(9) NOT NULL,
  field_name varchar(255) NOT NULL,
  field_description text,
  field_key varchar(255) NOT NULL,
  field_type varchar(255) default NULL,
  field_required tinyint(1) NOT NULL default '0',
  field_user_editable tinyint(1) NOT NULL default '0',
  field_position int(11) NOT NULL default '0',
  field_max_length mediumint(9) NOT NULL default '0',
  field_extra text,
  PRIMARY KEY  (field_id),
  KEY field_database_id (field_database_id),
  KEY field_key (field_key)
);";

$SQL[]	= "CREATE TABLE ccs_databases (
  database_id mediumint(9) NOT NULL auto_increment,
  database_name varchar(255) NOT NULL,
  database_key varchar(255) NOT NULL,
  database_database varchar(255) NULL,
  database_description text,
  database_field_count mediumint(9) NOT NULL default '0',
  database_record_count mediumint(9) NOT NULL default '0',
  database_template_listing int(11) NOT NULL default '0',
  database_template_display mediumint(9) NOT NULL default '0',
  database_user_editable tinyint(1) NOT NULL default '0',
  database_all_editable tinyint(1) NOT NULL default '0',
  database_open tinyint(1) NOT NULL default '0',
  database_comments tinyint(1) NOT NULL default '0',
  database_rate tinyint(1) NOT NULL default '0',
  database_revisions tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (database_id),
  UNIQUE KEY database_key (database_key)
);";

$SQL[]	= "ALTER TABLE ccs_page_templates ADD template_database tinyint(1) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_page_templates ADD INDEX ( template_database );";

$SQL[]	= "CREATE TABLE ccs_database_revisions (
revision_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
revision_database_id mediumint(9) NOT NULL ,
revision_record_id int(11) NOT NULL ,
revision_data LONGTEXT NULL ,
revision_date varchar(13) NOT NULL DEFAULT '0',
revision_member_id int(11) NOT NULL DEFAULT '0',
INDEX ( revision_database_id ,  revision_record_id ),
INDEX ( revision_member_id )
);";

$SQL[]	= "CREATE TABLE ccs_attachments_map (
map_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
map_attach_id INT NOT NULL DEFAULT '0',
map_database_id MEDIUMINT NOT NULL DEFAULT  '0',
map_field_id INT NOT NULL DEFAULT '0',
map_record_id INT NOT NULL DEFAULT '0',
INDEX ( map_database_id ),
INDEX ( map_attach_id )
);";

$SQL[]	= "CREATE TABLE ccs_database_ratings (
rating_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
rating_user_id INT NOT NULL DEFAULT  '0',
rating_database_id MEDIUMINT NOT NULL DEFAULT  '0',
rating_record_id INT NOT NULL DEFAULT  '0',
rating_rating INT NOT NULL DEFAULT  '0',
rating_added VARCHAR( 13 ) NOT NULL DEFAULT  '0',
rating_ip_address VARCHAR( 16 ) NOT NULL DEFAULT  '0',
INDEX (  rating_user_id ,  rating_database_id ,  rating_record_id )
);";

$SQL[]	= "CREATE TABLE  ccs_database_comments (
comment_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
comment_user INT NOT NULL DEFAULT  '0',
comment_database_id MEDIUMINT NOT NULL DEFAULT  '0',
comment_record_id INT NOT NULL DEFAULT  '0',
comment_date VARCHAR( 13 ) NOT NULL DEFAULT  '0',
comment_ip_address VARCHAR( 16 ) NOT NULL DEFAULT  '0',
comment_post TEXT NULL DEFAULT NULL ,
INDEX ( comment_user )
);";



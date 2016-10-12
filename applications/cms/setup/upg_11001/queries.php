<?php

$SQL[]	= "CREATE TABLE ccs_database_categories (
category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
category_database_id MEDIUMINT NOT NULL DEFAULT '0',
category_name VARCHAR( 255 ) NULL DEFAULT NULL ,
category_parent_id INT NOT NULL DEFAULT '0',
category_last_record_id INT NOT NULL DEFAULT '0',
category_last_record_date VARCHAR( 13 ) NOT NULL DEFAULT '0',
category_last_record_member INT NOT NULL DEFAULT  '0',
category_last_record_name VARCHAR( 255 ) NULL DEFAULT NULL,
category_last_record_seo_name VARCHAR( 255 ) NULL DEFAULT NULL,
category_description TEXT NULL DEFAULT NULL,
category_position INT NOT NULL DEFAULT '0',
category_records INT NOT NULL DEFAULT '0',
INDEX ( category_database_id )
);";

$SQL[]	= "ALTER TABLE ccs_database_fields ADD field_html TINYINT NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_databases ADD database_template_categories MEDIUMINT NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_databases CHANGE database_template_listing database_template_listing MEDIUMINT NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_databases ADD  database_field_title VARCHAR( 255 ) NULL DEFAULT NULL,
ADD database_field_sort VARCHAR( 255 ) NULL DEFAULT NULL,
ADD database_field_direction VARCHAR( 4 ) NOT NULL DEFAULT 'desc',
ADD database_field_perpage SMALLINT NOT NULL DEFAULT '25';";



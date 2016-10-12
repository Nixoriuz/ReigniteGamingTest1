<?php

if( !\IPS\Db::i()->checkForColumn( 'ccs_database_fields', 'field_topic_format' ) )
{
	$SQL[]	= "ALTER TABLE ccs_database_fields ADD field_topic_format TEXT NULL DEFAULT NULL;";
}

$SQL[]	= "ALTER TABLE ccs_databases ADD database_search TINYINT( 1 ) NOT NULL DEFAULT '0';";

$SQL[]	= "UPDATE ccs_databases SET database_search=1;";

$SQL[]	= "ALTER TABLE ccs_template_blocks CHANGE tpb_content tpb_content MEDIUMTEXT NULL DEFAULT NULL;";

$SQL[]	= "CREATE TABLE ccs_revisions (
revision_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
revision_type VARCHAR( 32 ) NOT NULL ,
revision_type_id INT NOT NULL ,
revision_content MEDIUMTEXT NULL DEFAULT NULL ,
revision_other MEDIUMTEXT NULL ,
revision_date INT NOT NULL DEFAULT '0',
revision_member INT NOT NULL DEFAULT '0',
INDEX ( revision_type , revision_type_id, revision_date ),
INDEX ( revision_member )
);";

$SQL[]	= "ALTER TABLE ccs_page_wizard ADD wizard_omit_filename TINYINT( 1 ) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_pages ADD page_omit_filename TINYINT( 1 ) NOT NULL DEFAULT '0';";

if( !\IPS\Db::i()->checkForColumn( 'ccs_database_categories', 'category_records_queued' ) )
{
	$SQL[]	= "ALTER TABLE ccs_database_categories ADD category_records_queued INT NOT NULL DEFAULT '0' AFTER category_records;";
}


$SQL[]	= "ALTER TABLE ccs_template_blocks ADD INDEX tpb_name(tpb_name);";
$SQL[]	= "ALTER TABLE ccs_containers ADD INDEX container_type( container_type, container_order );";

<?php

$SQL[]	= "CREATE TABLE ccs_database_moderators (
moderator_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
moderator_database_id INT NOT NULL DEFAULT '0',
moderator_type VARCHAR( 16 ) NULL DEFAULT NULL ,
moderator_type_id INT NOT NULL DEFAULT '0',
moderator_delete_record TINYINT(1) NOT NULL DEFAULT '0',
moderator_edit_record TINYINT(1) NOT NULL DEFAULT '0',
moderator_lock_record TINYINT(1) NOT NULL DEFAULT '0',
moderator_unlock_record TINYINT(1) NOT NULL DEFAULT '0',
moderator_delete_comment TINYINT(1) NOT NULL DEFAULT '0',
INDEX ( moderator_database_id )
);";

$SQL[]	= "ALTER TABLE ccs_database_categories ADD category_show_records TINYINT(1) NOT NULL DEFAULT '1';";



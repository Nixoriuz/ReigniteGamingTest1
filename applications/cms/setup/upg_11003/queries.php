<?php

$SQL[]	= "ALTER TABLE ccs_databases ADD database_record_approve TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_database_categories ADD category_has_perms TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_database_comments ADD comment_approved TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_databases ADD database_comment_approve TINYINT(1) NOT NULL DEFAULT '0';";

$SQL[]	= "ALTER TABLE ccs_database_moderators ADD moderator_approve_record TINYINT(1) NOT NULL DEFAULT '0',
	ADD moderator_approve_comment TINYINT(1) NOT NULL DEFAULT '0',
	ADD moderator_pin_record TINYINT(1) NOT NULL DEFAULT '0';";



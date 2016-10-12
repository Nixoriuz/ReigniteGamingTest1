<?php

$SQL[]	= "ALTER TABLE ccs_database_fields ADD field_default_value TEXT NULL DEFAULT NULL,
ADD field_display_listing TINYINT( 1 ) NOT NULL DEFAULT '1',
ADD field_display_display TINYINT( 1 ) NOT NULL DEFAULT '1',
ADD field_format_opts TEXT NULL DEFAULT NULL;";



<?php

$SQL[] = "ALTER TABLE core_groups ADD idm_bypass_revision TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_temp_records ADD INDEX ( record_added );";
$SQL[] = "ALTER TABLE downloads_downloads ADD INDEX ( dtime );";
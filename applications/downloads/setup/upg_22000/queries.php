<?php

$SQL[] = "ALTER TABLE downloads_files ADD file_cost FLOAT NOT NULL DEFAULT '0.00';";
$SQL[] = "ALTER TABLE core_groups ADD idm_bypass_paid TINYINT( 1 ) NOT NULL DEFAULT 0, ADD idm_add_paid TINYINT( 1 ) NOT NULL DEFAULT 0;";
$SQL[] = "delete FROM core_sys_conf_settings WHERE conf_key='idm_guest_report';";

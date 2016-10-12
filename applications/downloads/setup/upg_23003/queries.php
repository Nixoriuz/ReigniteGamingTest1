<?php

$SQL[] = "ALTER TABLE downloads_files ADD COLUMN file_renewal_term INT(5) NOT NULL DEFAULT 0;";
$SQL[] = "ALTER TABLE downloads_files ADD COLUMN file_renewal_units CHAR(1) NULL DEFAULT NULL;";
$SQL[] = "ALTER TABLE downloads_files ADD COLUMN file_renewal_price FLOAT NOT NULL DEFAULT '0.00';";
$SQL[] = "ALTER TABLE downloads_mods ADD modusefeature TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files ADD file_featured TINYINT( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( file_featured );";
$SQL[] = "ALTER TABLE downloads_files ADD file_pinned TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_mods ADD modcanpin TINYINT( 1 ) NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files ADD file_comments INT NOT NULL DEFAULT '0';";

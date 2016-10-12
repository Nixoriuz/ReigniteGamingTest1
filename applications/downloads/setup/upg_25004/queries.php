<?php
/*
+--------------------------------------------------------------------------
|   IP.Board vVERSION_NUMBER
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2009 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

/* Downloads upgrade */

$SQL[] = "ALTER TABLE downloads_files CHANGE file_size file_size BIGINT NOT NULL DEFAULT '0';";
$SQL[] = "ALTER TABLE downloads_files_records CHANGE record_size record_size BIGINT NOT NULL DEFAULT '0';";

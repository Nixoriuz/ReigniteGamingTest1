<?php

$SQL[]	= "ALTER TABLE ccs_database_comments ADD INDEX ( comment_database_id , comment_record_id , comment_date );";
$SQL[]	= "ALTER TABLE ccs_attachments_map DROP INDEX map_database_id , ADD INDEX map_database_id ( map_database_id , map_record_id );";
$SQL[]	= "ALTER TABLE ccs_database_fields ADD field_truncate MEDIUMINT NOT NULL DEFAULT '100';";



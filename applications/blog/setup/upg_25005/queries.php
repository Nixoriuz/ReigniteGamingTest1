<?php

// Reset short entries (#35240)
$SQL[] = "UPDATE blog_entries SET entry_short='';";


# New indexes, to avoid filesorts & full table scans
$SQL[] = "ALTER TABLE blog_comments DROP INDEX comment_member_id, ADD INDEX comment_member_id ( member_id, comment_date );";
$SQL[] = "ALTER TABLE blog_category_mapping DROP INDEX map_entry_id, ADD INDEX map_blog_id ( map_blog_id, map_entry_id );";

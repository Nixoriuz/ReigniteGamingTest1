<?php

$SQL[]	= "update ccs_pages SET page_view_perms=CONCAT(',',page_view_perms,',') WHERE page_view_perms != '*';";

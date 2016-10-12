<?php

/* Downloads upgrade */

$SQL[] = "ALTER TABLE downloads_downloads CHANGE dsize dsize BIGINT NOT NULL DEFAULT '0';";

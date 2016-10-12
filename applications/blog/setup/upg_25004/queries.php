<?php

// Some installs may still have this old (hidden) settings group installed
$SQL[] = "DELETE FROM core_sys_settings_titles WHERE conf_title_keyword='blog_masks';";


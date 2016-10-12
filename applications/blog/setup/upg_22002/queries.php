<?php

$SQL[] = "ALTER TABLE blog_lastinfo CHANGE blog_tag_cloud blog_tag_cloud MEDIUMTEXT;";
$SQL[] = "UPDATE blog_lastinfo SET blog_tag_cloud='';";

if( ! \IPS\Db::i()->checkForColumn( 'blog_themes', 'theme_css_overwrite' ) )
{
	$SQL[] = "ALTER TABLE blog_themes ADD theme_css_overwrite TINYINT( 1 ) NOT NULL DEFAULT '0';";
}
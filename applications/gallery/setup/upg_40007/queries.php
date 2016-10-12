<?php

$SQL = array();

if ( !\IPS\Db::i()->checkForColumn( 'gallery_images', 'image_gps_latlon' ) )
{
	$SQL[] = "ALTER TABLE gallery_images ADD image_gps_latlon VARCHAR(255) DEFAULT '';";
}

if ( !\IPS\Db::i()->checkForTable( 'gallery_albums_temp' ) )
{
	$SQL[] = "CREATE TABLE gallery_albums_temp (
		album_id	INT(10),
		album_g_perms_view	TEXT
	);";
}

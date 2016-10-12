<?php
$SQL = array();

$SQL[] = "UPDATE gallery_albums_main SET album_position=10000000 WHERE album_is_global=0;";

if ( ! \IPS\Db::i()->checkForColumn( 'gallery_albums_main', 'album_g_latest_imgs' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_g_latest_imgs TEXT AFTER album_g_perms_moderate;";
}

if ( ! \IPS\Db::i()->checkForColumn( 'gallery_albums_main', 'album_child_tree' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_child_tree TEXT;";
}

if ( ! \IPS\Db::i()->checkForColumn( 'gallery_albums_main', 'album_parent_tree' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_parent_tree TEXT;";
}

if ( ! \IPS\Db::i()->checkForColumn( 'gallery_albums_main', 'album_can_tag' ) )
{
	$SQL[] = "ALTER TABLE gallery_albums_main ADD album_can_tag INT(1) NOT NULL DEFAULT 1, ADD album_preset_tags TEXT;";
}

<?php
$SQL = array();

/* Clean up from previous versions */
if ( \IPS\Db::i()->checkForTable( 'gallery_albums' ) AND \IPS\Db::i()->checkForTable( 'gallery_albums_main' ) )
{
	$SQL[] = "DROP TABLE gallery_albums;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_albums_temp' ) )
{
	$SQL[] = "DROP TABLE gallery_albums_temp;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_categories' ) )
{
	$SQL[] = "DROP TABLE gallery_categories;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_ecardlog' ) )
{
	$SQL[] = "DROP TABLE gallery_ecardlog;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_favorites' ) )
{
	$SQL[] = "DROP TABLE gallery_favorites;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_subscriptions' ) )
{
	$SQL[] = "DROP TABLE gallery_subscriptions;";
}

if ( \IPS\Db::i()->checkForTable( 'gallery_upgrade_history' ) )
{
	$SQL[] = "DROP TABLE gallery_upgrade_history;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_images', 'category_id' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP category_id;";
}

/* This old index laying around? */
if( \IPS\Db::i()->checkForIndex( 'gallery_bandwidth', 'date' ) )
{
	$SQL[] = "ALTER TABLE gallery_bandwidth DROP INDEX `date`;";
}

/* Old settings potentially still around */
$SQL[] = "DELETE FROM core_sys_conf_settings WHERE conf_key IN ( 'gallery_display_category', 'gallery_display_album', 'gallery_default_view', 'gallery_enable_both_views', 
	'gallery_full_image', 'gallery_display_block_row', 'gallery_web_accessible', 'gallery_display_photostrip', 'gallery_last_updated', 'gallery_show_lastpic', 
	'gallery_display_subcats', 'gallery_dir_images', 'gallery_cache_albums', 'gallery_stats_where', 'gallery_images_per_block', 'gallery_last5_images', 
	'gallery_random_images', 'gallery_stats', 'gallery_feature_image', 'gallery_thumbnail_link', 'gallery_thumb_width', 'gallery_thumb_height', 'gallery_allowed_domains',
	'gallery_antileech_image', 'gallery_use_rate', 'gallery_rate_display', 'gallery_bandwidth_thumbs', 'gallery_use_ecards', 'display_hotlinking', 'gallery_guests_ecards',
	'gallery_comment_order', 'gallery_allow_usercopyright', 'gallery_copyright_default', 'gallery_exif', 'gallery_iptc', 'gallery_exif_sections', 'gallery_notices_cat',
	'gallery_notices_album', 'gallery_notices_img', 'gallery_idx_num_col', 'gallery_idx_num_row', 'gallery_stats_col', 'gallery_stats_row', 'gallery_stats_cols', 'gallery_stats_rows' );";

/* Convert ratings table (easy) */
if ( \IPS\Db::i()->checkForColumn( 'gallery_ratings', 'id' ) )
{
	$SQL[] = "ALTER TABLE gallery_ratings CHANGE id rate_id BIGINT NOT NULL AUTO_INCREMENT,
				CHANGE member_id rate_member_id INT NOT NULL DEFAULT 0,
				CHANGE rating_where rate_type VARCHAR(32) NOT NULL DEFAULT 'image',
				CHANGE rating_foreign_id rate_type_id BIGINT NOT NULL DEFAULT 0,
				CHANGE rdate rate_date INT NOT NULL DEFAULT 0,
				CHANGE rate rate_rate INT NOT NULL DEFAULT 0,
				DROP INDEX rating_find_me,
				ADD INDEX rating_find_me ( rate_member_id, rate_type, rate_type_id );";
}

/* Convert comments table (easy) - we used to use ignore keyword to prevent warnings about truncating the edit time, but now we need to do this
	the long way since alter ignore table has been removed */
if ( \IPS\Db::i()->checkForColumn( 'gallery_comments', 'pid' ) )
{
	/* Note that we have to manually set the prefix for some of these because the auto-replacement regex doesn't look for them */
	$SQL[] = "CREATE TABLE gallery_comments_temp LIKE " . \IPS\Db::i()->prefix . "gallery_comments;";
	$SQL[] = "ALTER TABLE gallery_comments_temp CHANGE pid comment_id INT NOT NULL AUTO_INCREMENT,
				CHANGE edit_time comment_edit_time INT NOT NULL DEFAULT 0,
				CHANGE author_id comment_author_id INT NOT NULL DEFAULT 0,
				CHANGE author_name comment_author_name VARCHAR(255) NULL DEFAULT NULL,
				CHANGE ip_address comment_ip_address VARCHAR(46) NULL DEFAULT NULL,
				CHANGE post_date comment_post_date INT NOT NULL DEFAULT 0,
				CHANGE comment comment_text TEXT NULL DEFAULT NULL,
				CHANGE approved comment_approved TINYINT NOT NULL DEFAULT 0,
				CHANGE img_id comment_img_id BIGINT NOT NULL DEFAULT 0,
				ADD INDEX (comment_ip_address),
				DROP INDEX img_id,
				ADD INDEX img_id (comment_img_id,comment_post_date);";
	$SQL[] = "INSERT IGNORE INTO "   . \IPS\Db::i()->prefix . "gallery_comments_temp SELECT * FROM "   . \IPS\Db::i()->prefix . "gallery_comments;";
	$SQL[] = "DROP TABLE gallery_comments;";
	$SQL[] = "RENAME TABLE gallery_comments_temp TO gallery_comments;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_comments', 'use_sig' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP use_sig;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_comments', 'use_emo' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP use_emo;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_comments', 'edit_name' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP edit_name;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_comments', 'append_edit' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP append_edit;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_comments', 'img_id_2' ) )
{
	$SQL[] = "ALTER TABLE gallery_comments DROP INDEX img_id_2;";
}

/* Add new column to temp uploads table (easy) */
if ( !\IPS\Db::i()->checkForColumn( 'gallery_images_uploads', 'upload_category_id' ) )
{
	$SQL[] = "ALTER TABLE gallery_images_uploads ADD upload_category_id INT NOT NULL DEFAULT 0 AFTER upload_album_id;";
}

/* And then convert images - not too difficult */
if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'album_id' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX album_id;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'approved' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX approved;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'album_id_2' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX album_id_2;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'gb_select' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX gb_select;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'image_feature_flag' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX image_feature_flag;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'lastcomment' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX lastcomment;";
}

if ( \IPS\Db::i()->checkForIndex( 'gallery_images', 'rnd_lookup' ) )
{
	$SQL[] = "ALTER TABLE gallery_images DROP INDEX rnd_lookup;";
}

if ( \IPS\Db::i()->checkForColumn( 'gallery_images', 'id' ) )
{
	$SQL[] = "ALTER TABLE gallery_images CHANGE id image_id BIGINT NOT NULL AUTO_INCREMENT,
				CHANGE member_id image_member_id INT NOT NULL DEFAULT 0,
				ADD image_category_id INT NOT NULL DEFAULT 0 AFTER image_member_id,
				CHANGE img_album_id image_album_id BIGINT NOT NULL DEFAULT 0,
				CHANGE caption image_caption VARCHAR(255) NOT NULL,
				CHANGE description image_description TEXT NULL DEFAULT NULL,
				CHANGE directory image_directory VARCHAR(255) NULL DEFAULT NULL,
				CHANGE masked_file_name image_masked_file_name VARCHAR(255) NULL DEFAULT NULL,
				CHANGE file_name image_file_name VARCHAR(255) NULL DEFAULT NULL,
				CHANGE medium_file_name image_medium_file_name VARCHAR(255) NULL DEFAULT NULL,
				CHANGE original_file_name image_original_file_name VARCHAR(255) NULL DEFAULT NULL,
				CHANGE file_size image_file_size INT NOT NULL DEFAULT 0,
				CHANGE file_type image_file_type VARCHAR(50) NULL DEFAULT NULL,
				CHANGE approved image_approved TINYINT NOT NULL DEFAULT 0,
				CHANGE thumbnail image_thumbnail TINYINT NOT NULL DEFAULT 0,
				CHANGE views image_views INT NOT NULL DEFAULT 0,
				CHANGE comments image_comments INT NOT NULL DEFAULT 0,
				CHANGE comments_queued image_comments_queued INT NOT NULL DEFAULT 0,
				CHANGE idate image_date INT NOT NULL DEFAULT 0,
				CHANGE ratings_total image_ratings_total INT NOT NULL DEFAULT 0,
				CHANGE ratings_count image_ratings_count INT NOT NULL DEFAULT 0,
				CHANGE rating image_rating INT NOT NULL DEFAULT 0,
				CHANGE lastcomment image_last_comment INT NOT NULL DEFAULT 0,
				CHANGE pinned image_pinned TINYINT NOT NULL DEFAULT 0,
				CHANGE media image_media TINYINT NOT NULL DEFAULT 0,
				CHANGE credit_info image_credit_info TEXT NULL DEFAULT NULL,
				CHANGE copyright image_copyright VARCHAR(255) NULL DEFAULT NULL,
				CHANGE metadata image_metadata TEXT NULL DEFAULT NULL,
				CHANGE media_thumb image_media_thumb VARCHAR(255) NULL DEFAULT NULL,
				CHANGE caption_seo image_caption_seo VARCHAR(255) NOT NULL,
				CHANGE image_feature_flag image_feature_flag TINYINT NOT NULL DEFAULT 0,
				CHANGE image_gps_show image_gps_show TINYINT NOT NULL DEFAULT 0,
				ADD INDEX album_id (image_album_id, image_approved, image_date),
				ADD INDEX image_feature_flag (image_feature_flag, image_date),
				ADD INDEX gb_select (image_approved, image_parent_permission, image_date),
				ADD INDEX lastcomment (image_last_comment, image_date);";
}

/* Add our new tables */
$SQL[] = "CREATE TABLE gallery_categories (
  category_id int(11) NOT NULL AUTO_INCREMENT,
  category_parent_id int(11) NOT NULL DEFAULT '0',
  category_name varchar(255) DEFAULT NULL,
  category_name_seo varchar(255) DEFAULT NULL,
  category_description text,
  category_count_imgs int(11) NOT NULL DEFAULT '0',
  category_count_comments int(11) NOT NULL DEFAULT '0',
  category_count_imgs_hidden int(11) NOT NULL DEFAULT '0',
  category_count_comments_hidden int(11) NOT NULL DEFAULT '0',
  category_cover_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_id bigint(20) NOT NULL DEFAULT '0',
  category_last_img_date int(11) NOT NULL DEFAULT '0',
  category_type int(11) NOT NULL DEFAULT '0',
  category_sort_options text,
  category_allow_comments tinyint(4) NOT NULL DEFAULT '0',
  category_allow_rating tinyint(4) NOT NULL DEFAULT '0',
  category_approve_img tinyint(4) NOT NULL DEFAULT '0',
  category_approve_com tinyint(4) NOT NULL DEFAULT '0',
  category_rules mediumtext,
  category_rating_aggregate int(11) NOT NULL DEFAULT '0',
  category_rating_count int(11) NOT NULL DEFAULT '0',
  category_rating_total int(11) NOT NULL DEFAULT '0',
  category_after_forum_id int(11) NOT NULL DEFAULT '0',
  category_watermark tinyint(4) NOT NULL DEFAULT '0',
  category_position int(11) NOT NULL DEFAULT '0',
  category_can_tag tinyint(4) NOT NULL DEFAULT '0',
  category_preset_tags text,
  category_public_albums int(11) NOT NULL DEFAULT '0',
  category_nonpublic_albums int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (category_id),
  KEY category_last_img_id (category_last_img_id)
);";


if( !\IPS\Db::i()->checkForTable( 'gallery_moderators' ) )
{
	$SQL[] = "CREATE TABLE gallery_moderators (
	  mod_id int(11) NOT NULL AUTO_INCREMENT,
	  mod_type varchar(32) NOT NULL DEFAULT 'group',
	  mod_type_id int(11) NOT NULL DEFAULT '0',
	  mod_type_name varchar(255) DEFAULT NULL,
	  mod_categories text,
	  mod_can_approve tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_edit tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_hide tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_delete tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_approve_comments tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_edit_comments tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_delete_comments tinyint(4) NOT NULL DEFAULT '0',
	  mod_can_move tinyint(4) NOT NULL DEFAULT '0',
	  mod_set_cover_image tinyint(4) NOT NULL DEFAULT '0',
	  PRIMARY KEY (mod_id),
	  KEY mod_type (mod_type,mod_type_id)
	);";
}

/* Groups table changes? */
if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_ecard' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_ecard;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_rate' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_rate;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_slideshows' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_slideshows;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_favorites' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_favorites;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_comment' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_comment;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_move_own' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_move_own;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_mod_albums' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_mod_albums;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_album_private' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_album_private;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_gal_avatar' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_gal_avatar;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_max_notes' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_max_notes;";
}

if ( \IPS\Db::i()->checkForColumn( 'core_groups', 'g_gallery_cat_cover' ) )
{
	$SQL[] = "ALTER TABLE core_groups DROP g_gallery_cat_cover;";
}

if ( !\IPS\Db::i()->checkForColumn( 'core_groups', 'g_create_albums_private' ) )
{
	$SQL[] = "ALTER TABLE core_groups ADD g_create_albums_private TINYINT( 1 ) UNSIGNED default '0' NOT NULL,
		ADD g_create_albums_fo TINYINT( 1 ) UNSIGNED default '0' NOT NULL,
		ADD g_delete_own_albums TINYINT( 1 ) NOT NULL DEFAULT '0';";
}

/* Albums handled by the upgrader script */
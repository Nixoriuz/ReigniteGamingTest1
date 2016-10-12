<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		24 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\setup\upg_50000;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Conversion from 3.0
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\core\Setup\Upgrade::runLegacySql( 'gallery', 50000 );
		
		return TRUE;
	}

	/**
	 * Convert members album amd categories
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		if ( !\IPS\Db::i()->checkForColumn( 'gallery_albums_main', 'album_category_id' ) )
		{
			\IPS\Db::i()->addColumn( 'gallery_albums_main', array( "name" => "album_category_id", "type" => "int", "length" => 40, "allow_null" => false, "default" => '0', "comment" => "", "auto_increment" => false, "binary" => false ) );
		}
		
		try
		{
			$setting	= \IPS\Db::i()->select( '*', 'core_sys_conf_settings', array( 'conf_key=?', 'gallery_members_album' ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$setting	= array(
				'conf_key'			=> 'gallery_members_album',
				'conf_value'		=> 0,
				'conf_default'		=> 0
			);

			\IPS\Db::i()->insert( 'core_sys_conf_settings', $setting );
			\IPS\Settings::i()->gallery_members_album	= 0;
		}

		unset( \IPS\Data\Store::i()->settings );

		//-----------------------------------------
		// Get global albums and loop
		//-----------------------------------------

		$albumCatMap	= array();
		$imagesOnly		= array();
		$position		= 1;
		$options		= null;

		foreach( \IPS\Db::i()->select( '*', 'gallery_albums_main', "album_is_global=1", 'album_position ASC' ) as $album )
		{
			//-----------------------------------------
			// Fix older sort options
			//-----------------------------------------

			$options	= @unserialize( $album['album_sort_options'] );

			if( $options['key'] )
			{
				$options['key']	= ( $options['key'] == 'name' ) ? 'image_caption' : ( ( $options['key'] == 'idate' ) ? 'image_date' : ( ( $options['key'] == 'rating' ) ? 'image_rating' : ( ( $options['key'] == 'comments' ) ? 'image_comments' : ( ( $options['key'] == 'views' ) ? 'image_views' : $options['key'] ) ) ) );
				$album['album_sort_options']	= serialize($options);
			}

			$options	= null;

			//-----------------------------------------
			// Insert new category
			//-----------------------------------------

			$category	= array(
								'category_name'				=> $album['album_name'],
								'category_name_seo'			=> $album['album_name_seo'],
								'category_description'		=> $album['album_description'],
								'category_cover_img_id'		=> $album['album_cover_img_id'],
								'category_type'				=> ( $album['album_g_container_only'] == 1 ) ? 1 : 2,
								'category_sort_options'		=> $album['album_sort_options'],
								'category_allow_comments'	=> $album['album_allow_comments'],
								'category_allow_rating'		=> $album['album_allow_rating'],
								'category_approve_img'		=> $album['album_g_approve_img'],
								'category_approve_com'		=> $album['album_g_approve_com'],
								'category_rules'			=> $album['album_g_rules'],
								'category_after_forum_id'	=> $album['album_after_forum_id'],
								'category_watermark'		=> ( !$album['album_watermark'] ) ? 0 : ( ( $album['album_watermark'] == 2 ) ? 2 : 1 ),
								'category_can_tag'			=> $album['album_can_tag'],
								'category_preset_tags'		=> $album['album_preset_tags'],
								'category_position'			=> $position,
								);

			$category['category_id'] = \IPS\Db::i()->insert( 'gallery_categories', $category );

			if( $album['album_g_perms_thumbs'] == 'member' )
			{
				\IPS\Settings::i()->gallery_members_album	= $category['category_id'];
			}

			//-----------------------------------------
			// Insert permissions
			//-----------------------------------------

			$permissions	= array(
									'app'			=> 'gallery',
									'perm_type'		=> 'categories',
									'perm_type_id'	=> $category['category_id'],
									'perm_view'		=> $album['album_g_perms_view'] ?: '',
									'perm_2'		=> $album['album_g_perms_images'],
									'perm_3'		=> $album['album_g_perms_comments'],
									'perm_4'		=> $album['album_g_perms_comments'],
									'perm_5'		=> $album['album_g_perms_moderate'],
									);

			\IPS\Db::i()->insert( 'core_permission_index', $permissions );

			//-----------------------------------------
			// Update images in this category
			//-----------------------------------------

			\IPS\Db::i()->update( 'gallery_images', array( 'image_category_id' => $category['category_id'], 'image_album_id' => 0, 'image_parent_permission' => $album['album_g_perms_view'], 'image_privacy' => 0 ), 'image_album_id=' . $album['album_id'] );

			//-----------------------------------------
			// Store mapping
			//-----------------------------------------

			$position++;

			$albumCatMap[ $album['album_id'] ]	= array( 'album' => $album, 'category' => $category );

			if( $category['category_type'] == 2 )
			{
				$imagesOnly[]					= $category['category_id'];
			}
		}

		//-----------------------------------------
		// Fix album data
		//-----------------------------------------

		$foundMembersGallery	= 0;

		foreach( $albumCatMap as $albumId => $data )
		{
			//-----------------------------------------
			// Set subcategory parent association if necessary
			//-----------------------------------------

			if( $data['album']['album_parent_id'] )
			{
				\IPS\Db::i()->update( 'gallery_categories', array( 'category_parent_id' => $albumCatMap[ $data['album']['album_parent_id'] ]['category']['category_id'] ), 'category_id=' . $data['category']['category_id'] );
			}

			//-----------------------------------------
			// Move our child albums
			//-----------------------------------------

			\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_category_id' => $data['category']['category_id'], 'album_parent_id' => 0 ), 'album_parent_id=' . $albumId );

			//-----------------------------------------
			// Fix members album cat association
			//-----------------------------------------

			if( $albumId == \IPS\Settings::i()->gallery_members_album )
			{
				\IPS\Settings::i()->gallery_members_album	= $data['category']['category_id'];
				$foundMembersGallery	= $data['category']['category_id'];

				\IPS\Db::i()->update( 'gallery_categories', array( 'category_type' => 1 ), 'category_id=' . $data['category']['category_id'] );
			}
		}

		//-----------------------------------------
		// If we didn't find a members gallery, make one
		//-----------------------------------------

		if( !$foundMembersGallery )
		{
			$category	= array(
								'category_name'				=> 'Temp global album for root member albums',
								'category_name_seo'			=> 'temp-global-album-for-root-member-albums',
								'category_description'		=> "This is a temporary global album that holds the member albums that didn't have the proper parent album set. This album has NO permissions and is not visible from the public side, please move the albums in the proper location.",
								'category_cover_img_id'		=> 0,
								'category_type'				=> 1,
								'category_sort_options'		=> '',
								'category_allow_comments'	=> 1,
								'category_allow_rating'		=> 1,
								'category_approve_img'		=> 0,
								'category_approve_com'		=> 0,
								'category_rules'			=> '',
								'category_after_forum_id'	=> 0,
								'category_watermark'		=> 0,
								'category_can_tag'			=> 1,
								'category_preset_tags'		=> '',
								'category_position'			=> $position,
								);

			$category['category_id'] = \IPS\Db::i()->insert( 'gallery_categories', $category );

			$foundMembersGallery		= $category['category_id'];
			\IPS\Settings::i()->gallery_members_album	= $category['category_id'];
		}

		//-----------------------------------------
		// Move any albums in a category with type 2 to members album cat
		//-----------------------------------------

		if( count( $imagesOnly ) )
		{
			\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_category_id' => $foundMembersGallery ), 'album_category_id IN(' . implode( ',', $imagesOnly ) . ')' );
		}

		//-----------------------------------------
		// Delete global albums
		//-----------------------------------------

		\IPS\Db::i()->delete( 'gallery_albums_main', 'album_is_global=1' );

		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Settings::i()->gallery_members_album ), array( 'conf_key=?', 'gallery_members_album' ) );
		unset( \IPS\Data\Store::i()->settings );

		return TRUE;
	}

	/**
	 * Clean up missing and extraneous fields and indexes
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3()
	{
		//-----------------------------------------
		// No idea why, but sometimes this index disappears?
		// @link	http://community.invisionpower.com/resources/bugs.html/_/ip-gallery/missing-index-img-id-after-upgrade-r38838
		//-----------------------------------------

		if( !\IPS\Db::i()->checkForIndex( 'gallery_comments', 'img_id' ) )
		{
			if( \IPS\Db::i()->checkForIndex( 'gallery_comments', 'comment_img_id' ) )
			{
				\IPS\Db::i()->dropIndex( 'gallery_comments', 'comment_img_id' );
			}

			\IPS\Db::i()->addIndex( 'gallery_comments', array(
				'type'			=> 'index',
				'name'			=> 'img_id',
				'columns'		=> array( 'comment_img_id', 'comment_post_date' )
			) );
		}

		//-----------------------------------------
		// Add new columns
		//-----------------------------------------

		\IPS\Db::i()->addColumn( 'gallery_albums_main', array( "name" => "album_type", "type" => "INT", "length" => 10, "allow_null" => false, "default" => 0, "comment" => "", "auto_increment" => false, "binary" => false ) );
		\IPS\Db::i()->addColumn( 'gallery_albums_main', array( "name" => "album_last_x_images", "type" => "TEXT", "length" => null, "allow_null" => true, "default" => null, "comment" => "", "auto_increment" => false, "binary" => false ) );

		//-----------------------------------------
		// Change existing columns
		//-----------------------------------------

		\IPS\Db::i()->changeColumn( 'gallery_albums_main', "album_allow_comments", array( "name" => "album_allow_comments", "type" => "TINYINT", "length" => 1, "allow_null" => false, "default" => 0, "comment" => "", "auto_increment" => false, "binary" => false ) );
		\IPS\Db::i()->changeColumn( 'gallery_albums_main', "album_allow_rating", array( "name" => "album_allow_rating", "type" => "TINYINT", "length" => 1, "allow_null" => false, "default" => 0, "comment" => "", "auto_increment" => false, "binary" => false ) );
		\IPS\Db::i()->changeColumn( 'gallery_albums_main', "album_watermark", array( "name" => "album_watermark", "type" => "TINYINT", "length" => 1, "allow_null" => false, "default" => 0, "comment" => "", "auto_increment" => false, "binary" => false ) );

		//-----------------------------------------
		// Delete old columns
		//-----------------------------------------

		\IPS\Db::i()->dropColumn( 'gallery_albums_main', array(
			'album_is_global', 'album_is_profile', 'album_cache', 'album_node_level', 'album_node_left', 'album_preset_tags',
			'album_node_right', 'album_g_approve_img', 'album_g_approve_com', 'album_g_bitwise', 'album_g_rules',
			'album_g_container_only', 'album_g_perms_thumbs', 'album_g_perms_view', 'album_g_perms_images', 'album_g_perms_comments',
			'album_g_perms_moderate', 'album_g_latest_imgs', 'album_detail_default', 'album_child_tree', 'album_parent_tree', 'album_can_tag'
		) );
		
		return TRUE;
	}

	/**
	 * Convert albums step 1
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step4()
	{
		$perCycle	= 100;
		$did		= 0;
		$limit		= intval( \IPS\Request::i()->extra );
		
		//-----------------------------------------
		// Fetch albums that have a parent defined
		//-----------------------------------------

		
		foreach( \IPS\Db::i()->select( '*', 'gallery_albums_main', NULL, 'album_id ASC', array( $limit, $percycle ) ) as $row )
		{
			$did++;
			$update	= array();

			//-----------------------------------------
			// Reset watermark
			//-----------------------------------------

			if( $row['album_watermark'] )
			{
				$update['album_watermark']	= 1;
			}

			//-----------------------------------------
			// Reset public/private/friend-only
			//-----------------------------------------

			if( $row['album_is_public'] == 1 )
			{
				$update['album_type']	= 1;
			}
			else if( $row['album_is_public'] == 2 )
			{
				$update['album_type']	= 3;
			}
			else
			{
				$update['album_type']	= 2;
			}

			//-----------------------------------------
			// Get the parent (up to 4 levels deep..)
			//-----------------------------------------

			if( $row['album_parent_id'] )
			{
				try
				{
					$parent	= \IPS\Db::i()->select( 'album_id, album_parent_id, album_category_id', 'gallery_albums_main', 'album_id=' . intval($row['album_parent_id']) )->first();
				}
				catch( \UnderflowException $e )
				{
					$parent	= array( 'album_id' => 0 );	
				}

				if( $parent['album_id'] )
				{
					if( $parent['album_category_id'] )
					{
						$update['album_category_id']	= $parent['album_category_id'];
					}
					else if( $parent['album_parent_id'] )
					{
						try
						{
							$_parent	= \IPS\Db::i()->select( 'album_id, album_parent_id, album_category_id', 'gallery_albums_main', 'album_id=' . intval($parent['album_parent_id']) )->first();
						}
						catch( \UnderflowException $e )
						{
							$_parent	= array( 'album_id' => 0 );	
						}

						if( $_parent['album_id'] )
						{
							if( $_parent['album_category_id'] )
							{
								$update['album_category_id']	= $_parent['album_category_id'];
							}
							else if( $_parent['album_parent_id'] )
							{
								try
								{
									$__parent	= \IPS\Db::i()->select( 'album_id, album_parent_id, album_category_id', 'gallery_albums_main', 'album_id=' . intval($_parent['album_parent_id']) )->first();
								}
								catch( \UnderflowException $e )
								{
									$__parent	= array( 'album_id' => 0 );	
								}

								if( $__parent['album_id'] )
								{
									if( $__parent['album_category_id'] )
									{
										$update['album_category_id']	= $__parent['album_category_id'];
									}
									else if( $__parent['album_parent_id'] )
									{
										try
										{
											$___parent	= \IPS\Db::i()->select( 'album_id, album_parent_id, album_category_id', 'gallery_albums_main', 'album_id=' . intval($__parent['album_parent_id']) )->first();
										}
										catch( \UnderflowException $e )
										{
											$___parent	= array( 'album_id' => 0 );	
										}

										if( $___parent['album_category_id'] )
										{
											$update['album_category_id']	= $___parent['album_category_id'];
										}
									}
								}
							}
						}
					}
				}
			}

			//-----------------------------------------
			// If we didn't find cat, move to members albums cat
			//-----------------------------------------

			if( !$update['album_category_id'] )
			{
				$update['album_category_id']	= $row['album_category_id'] ? $row['album_category_id'] : (int) \IPS\Settings::i()->gallery_members_album;
			}

			//-----------------------------------------
			// Save updates
			//-----------------------------------------

			if( count($update) )
			{
				\IPS\Db::i()->update( 'gallery_albums_main', $update, 'album_id=' . $row['album_id'] );
			}
		}
		
		//-----------------------------------------
		// Got any more? .. redirect
		//-----------------------------------------

		if( $did )
		{
			return ( $limit + $did );
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Convert albums step 2
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step5()
	{
		//-----------------------------------------
		// Move any lingering albums to member album cat
		//-----------------------------------------

		\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_category_id' => (int) \IPS\Settings::i()->gallery_members_album ), 'album_category_id=0' );

		//-----------------------------------------
		// Delete old columns
		//-----------------------------------------

		\IPS\Db::i()->dropColumn( 'gallery_albums_main', 'album_parent_id' );
		\IPS\Db::i()->dropColumn( 'gallery_albums_main', 'album_is_public' );

		//-----------------------------------------
		// Update indexes
		//-----------------------------------------

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_nodes' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_nodes' );
		}

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_parent_id' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_parent_id' );
		}

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_owner_id' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_owner_id' );
		}

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_count_imgs' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_count_imgs' );
		}

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_has_a_perm' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_has_a_perm' );
		}

		if( \IPS\Db::i()->checkForIndex( 'gallery_albums_main', 'album_child_lup' ) )
		{
			\IPS\Db::i()->dropIndex( 'gallery_albums_main', 'album_child_lup' );
		}

		\IPS\Db::i()->addIndex( 'gallery_albums_main', array(
			'type'			=> 'index',
			'name'			=> 'album_owner_id',
			'columns'		=> array( 'album_owner_id', 'album_last_img_date' )
		) );

		\IPS\Db::i()->addIndex( 'gallery_albums_main', array(
			'type'			=> 'index',
			'name'			=> 'album_parent_id',
			'columns'		=> array( 'album_category_id', 'album_name_seo' )
		) );

		//-----------------------------------------
		// Rename the table
		//-----------------------------------------

		\IPS\Db::i()->renameTable( 'gallery_albums_main', 'gallery_albums' );
		
		return TRUE;
	}
}
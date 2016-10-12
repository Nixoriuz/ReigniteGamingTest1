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

namespace IPS\gallery\setup\upg_40000;

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
		\IPS\core\Setup\Upgrade::runLegacySql( 'gallery', 40000 );
		
		return TRUE;
	}

	/**
	 * Convert albums step 1
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		foreach( \IPS\Db::i()->select( '*', 'gallery_albums_main', "album_is_global=1 AND album_g_rules LIKE 'parent-cat-%'" ) as $album )
		{
			$oldParent = str_replace( 'parent-cat-', '', $album['album_g_rules'] );

			if ( intval( $oldParent ) and $oldParent > 0 )
			{
				try
				{
					$parent = \IPS\Db::i()->select( 'album_id', 'gallery_albums_main', "album_cache='catid-" . $oldParent . "'" )->first();
				}
				catch( \UnderflowException $e )
				{
					$parent = 0;
				}
															
				/* convert */
				\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_parent_id' => intval( $parent ) ), 'album_id=' . $album['album_id'] );
			}
		}
		
		return TRUE;
	}

	/**
	 * Convert albums step 2
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3()
	{
		foreach( \IPS\Db::i()->select( '*', 'gallery_albums_main', "album_cache LIKE 'catid-%'" ) as $album )
		{
			$oldParent = str_replace( 'catid-', '', $album['album_cache'] );

			\IPS\Db::i()->update( 'gallery_images', array( 'img_album_id' => intval( $album['album_id'] ) ), 'category_id=' . intval( $oldParent ) );
		}
		
		return TRUE;
	}

	/**
	 * Convert albums step 3
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step4()
	{
		foreach( \IPS\Db::i()->select( '*', 'gallery_albums_main', "album_cache LIKE 'cat-%'" ) as $album )
		{
			$oldParent = str_replace( 'cat-', '', $album['album_cache'] );

			if ( intval( $oldParent ) and $oldParent > 0 )
			{
				try
				{
					$parent = \IPS\Db::i()->select( 'album_id', 'gallery_albums_main', "album_cache='catid-" . $oldParent . "'" )->first();
				}
				catch( \UnderflowException $e )
				{
					$parent = 0;
				}
															
				/* convert */
				\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_parent_id' => intval( $parent ) ), 'album_id=' . $album['album_id'] );
				\IPS\Db::i()->update( 'gallery_images', array( 'img_album_id' => intval( $parent ) ), 'category_id=' . intval( $oldParent ) );
			}
		}

		\IPS\Db::i()->query( "UPDATE " . \IPS\Db::i()->prefix . "gallery_albums_main SET
				album_g_perms_thumbs   = TRIM( BOTH ',' FROM  album_g_perms_thumbs ),
				album_g_perms_view     = TRIM( BOTH ',' FROM  album_g_perms_view ),
				album_g_perms_images   = TRIM( BOTH ',' FROM  album_g_perms_images ),
				album_g_perms_comments = TRIM( BOTH ',' FROM  album_g_perms_comments ),
				album_g_perms_moderate = TRIM( BOTH ',' FROM  album_g_perms_moderate );" );
		
		return TRUE;
	}

	/**
	 * Convert albums step 4
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step5()
	{
		foreach( \IPS\Db::i()->select( '*', 'gallery_categories', "status=0" ) as $category )
		{
			\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_g_perms_images' => '' ), "album_cache='catid-{$category['id']}'" );
		}

		\IPS\Db::i()->update( 'gallery_albums_main', array( 'album_cache' => '', 'album_node_level' => 0, 'album_node_left' => 0, 'album_node_right' => 0 ) );
		
		return TRUE;
	}
}
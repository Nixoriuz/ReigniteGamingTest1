<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Pages
 * @since		08 Jan 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\setup\upg_23002;

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
	 * Upgrade
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\core\Setup\Upgrade::runLegacySql( 'cms', 23002 );
		
		return TRUE;
	}

	/**
	 * Upgrade
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		foreach( \IPS\Db::i()->select( '*', 'ccs_blocks', array( 'block_type=?', 'plugin' ) ) as $r )
		{
			$_config	= unserialize( $r['block_config'] );

			if( $_config['plugin'] == 'watched_content' )
			{
				\IPS\Db::i()->delete( 'ccs_blocks', "block_id=" . $r['block_id'] );
			}
		}
		
		return TRUE;
	}

	/**
	 * Upgrade
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3()
	{
		foreach( \IPS\Db::i()->select( '*', 'ccs_databases' ) as $database )
		{
			$_permissionIndex	= array();
			
			if( !$database['database_open'] )
			{
				$_permissionIndex['perm_view']	= '';
			}

			if( !$database['database_comments'] )
			{
				$_permissionIndex['perm_5']	= '';
			}

			if( !$database['database_rate'] )
			{
				$_permissionIndex['perm_6']	= '';
			}

			if( !$database['database_user_editable'] )
			{
				$_permissionIndex['perm_3']	= '';
				$_permissionIndex['perm_4']	= '';
			}

			if( count($_permissionIndex) )
			{
				\IPS\Db::i()->update( 'core_permission_index', $_permissionIndex, "app='ccs' AND perm_type='databases' AND perm_type_id={$database['database_id']}" );
			}

			if( $database['database_rss'] )
			{
				$_values	= explode( ';', $database['database_rss'] );
				\IPS\Db::i()->update( 'ccs_databases', array( 'database_rss' => $_values[0] ? intval($_values[2]) : 0 ), 'database_id=' . $database['database_id'] );
			}

			\IPS\Db::i()->addColumn( $database['database_database'], array(
				"name"		=> "record_comments_queued",
				"type"		=> "INT",
				"length"	=> 10,
				"null"		=> false,
				"default"	=> 0,
				"comment"	=> "",
				"unsigned"	=> false
			)	);
		}

		foreach( \IPS\Db::i()->select( '*', 'ccs_database_categories' ) as $cat )
		{
			if( $cat['category_rss'] )
			{
				$_values	= explode( ';', $cat['category_rss'] );
				\IPS\Db::i()->update( 'ccs_database_categories', array( 'category_rss' => $_values[0] ? intval($_values[2]) : 0, 'category_rss_exclude' => ( intval($_values[3]) > 0 ) ? 1 : 0 ), 'category_id=' . $cat['category_id'] );
			}
		}
		
		return TRUE;
	}
}
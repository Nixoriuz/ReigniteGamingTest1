<?php
/**
 * @brief		Upgrade steps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		3 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_33013;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrade steps
 */
class _Upgrade
{
	/**
	 * Step 1
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if ( \IPS\Db::i()->checkForIndex( 'rc_comments', 'report_comments' ) )
		{
			\IPS\Db::i()->dropIndex( 'rc_comments', 'report_comments' );
		}
		
		\IPS\Db::i()->addIndex( 'rc_comments', array(
			'type'			=> 'key',
			'name'			=> 'report_comments',
			'columns'		=> array( 'rid', 'approved', 'comment_date' )
		) );
		\IPS\Db::i()->update( 'rc_comments', array( 'approved' => 1 ), array( 'approved=?', 0 ) );

		\IPS\Db::i()->delete( 'core_sys_conf_settings', "conf_key IN( 'allow_dynamic_img', 'post_order_column' )" );
		\IPS\Db::i()->delete( 'core_share_links_caches', "cache_key='mosttypes'" );
		unset( \IPS\Data\Store::i()->settings );

		$toRun = \IPS\core\Setup\Upgrade::runManualQueries( array( array(
			'table'	=> 'members',
			'query'	=> "ALTER TABLE " . \IPS\Db::i()->prefix . "members " . \IPS\Db::i()->buildIndex( 'members', array(
				'type'		=> 'key',
				'name'		=> 'member_groups',
				'columns'	=> array( 'member_group_id', 'mgroup_others' ),
			) )
		) ) );
		
		if ( count( $toRun ) )
		{
			$mr = \IPS\core\Setup\Upgrade::adjustMultipleRedirect( array( 1 => 'core', 'extra' => array( '_upgradeStep' => 2 ) ) );

			/* Queries to run manually */
			return array( 'html' => \IPS\Theme::i()->getTemplate( 'forms' )->queries( $toRun, \IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => $mr ) ) ) );
		}

		/* Finish */
		return TRUE;
	}
}
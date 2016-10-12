<?php
/**
 * @brief		whosOnline Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		28 Jul 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * whosOnline Widget
 */
class _whosOnline extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'whosOnline';
	
	/**
	 * @brief	App
	 */
	public $app = 'core';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		/* Do we have permission? */
		if ( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'online' ) ) )
		{
			return "";
		}

		$where = array(
			array( 'core_sessions.running_time>' . \IPS\DateTime::create()->sub( new \DateInterval( 'PT30M' ) )->getTimeStamp() ),
			array( 'core_groups.g_hide_online_list=0' )
		);

		$members     = array();
		$memberCount = 0;
		$guests      = 0;
		$anonymous   = 0;
		
		foreach( \IPS\Db::i()->select( 'core_sessions.member_id,core_sessions.member_name,core_sessions.seo_name,core_sessions.member_group,core_sessions.login_type', 'core_sessions', $where, 'core_sessions.running_time DESC', $this->orientation === 'horizontal' ? NULL : 60 )->join( 'core_groups', 'core_sessions.member_group=core_groups.g_id' ) as $row )
		{
			switch ( $row['login_type'] )
			{
				case \IPS\Session\Front::LOGIN_TYPE_MEMBER:
					$members[ $row['member_id'] ] = $row;
					break;
				case \IPS\Session\Front::LOGIN_TYPE_ANONYMOUS:
					$anonymous += 1;
					break;
				case \IPS\Session\Front::LOGIN_TYPE_GUEST:
					$guests += 1;
					break;
			}
		}
		
		$memberCount = count( $members );
		
		if( \IPS\Member::loggedIn()->member_id )
		{
			if( !\IPS\Member::loggedIn()->group['g_hide_online_list'] )
			{
				if( !isset( $members[ \IPS\Member::loggedIn()->member_id ] ) )
				{
					$memberCount++;
				}
				
				$members[ \IPS\Member::loggedIn()->member_id ]	= array(
					'member_id'			=> \IPS\Member::loggedIn()->member_id,
					'member_name'		=> \IPS\Member::loggedIn()->name,
					'seo_name'			=> \IPS\Member::loggedIn()->members_seo_name,
					'member_group'		=> \IPS\Member::loggedIn()->member_group_id
				);
			}
		}
		
		/* Display */
		return $this->output( $members, $memberCount, $guests, $anonymous );
	}
}
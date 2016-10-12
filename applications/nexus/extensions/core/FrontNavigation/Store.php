<?php
/**
 * @brief		Front Navigation Extension: Store
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Store
 */
class _Store
{
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		return !\IPS\Application::load('nexus')->hide_tab and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'nexus', 'store' ) );
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('module__nexus_store');
	}
	
	/**
	 * Get Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
		return \IPS\Http\Url::internal( "app=nexus&module=store&controller=store", 'front', 'store' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
		return \IPS\Dispatcher::i()->application->directory === 'nexus' and \IPS\Dispatcher::i()->module and \IPS\Dispatcher::i()->module->key === 'store';
	}
	
	/**
	 * Children
	 *
	 * @return	array
	 */
	public function children()
	{
		return array();
	}
}
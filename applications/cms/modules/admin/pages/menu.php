<?php
/**
 * @brief		Page menu Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		15 Sept 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Page management
 */
class _menu extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\cms\Pages\Menu';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'menu_manage' );
		parent::execute();
	}
	
	/**
	 * Show the pages tree
	 *
	 * @return	string
	 */
	protected function manage()
	{
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__cms_pages_menu');

		if ( ! \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack('cms_menu_information'), 'information', NULL, FALSE );
		}

		return parent::manage();
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		unset( \IPS\Data\Store::i()->cms_menu );
		parent::delete();
	}
}
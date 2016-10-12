<?php
/**
 * @brief		Gallery Application Class 
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2014 Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		
 */
 
namespace IPS\gallery;

/**
 * Gallery Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* If the viewing member cannot view the board (ex: guests must login first), then send a 404 Not Found header here, before the Login page shows in the dispatcher */
		if ( \IPS\Dispatcher::hasInstance() AND \IPS\Dispatcher::i()->controllerLocation === 'front')
		{
			if ( !\IPS\Member::loggedIn()->group['g_view_board'] and ( \IPS\Request::i()->module == 'gallery' and \IPS\Request::i()->controller == 'browse' and \IPS\Request::i()->do == 'rss' )
			or ( \IPS\Member::loggedIn()->members_bitoptions['remove_gallery_access'] )
			)
			{
				\IPS\Output::i()->error( 'node_error', '2G218/1', 404, '' );
			}
		}
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'camera';
	}
}
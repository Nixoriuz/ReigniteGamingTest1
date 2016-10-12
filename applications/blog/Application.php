<?php
/**
 * @brief		Blog Application Class
 * @author		<a href=''>Invision Power Services, Inc.</a>
 * @copyright	(c) 2014 Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		3 Mar 2014
 * @version		
 */
 
namespace IPS\blog;

/**
 * Blog Application Class
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
		if ( !\IPS\Member::loggedIn()->group['g_view_board'] and ( \IPS\Request::i()->module == 'blogs' and \IPS\Request::i()->controller == 'view' and \IPS\Request::i()->do == 'rss' ) )
		{
			\IPS\Output::i()->error( 'node_error', '2B221/1', 404, '' );
		}
	}

	public function installOther()
	{
		/* Allow non guests to create and comment on Blogs */
		foreach( \IPS\Member\Group::groups( TRUE, FALSE ) as $group )
		{
			$group->g_blog_allowlocal = TRUE;
			$group->g_blog_allowcomment = TRUE;
			$group->save();
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
		return 'file-text-o';
	}
}
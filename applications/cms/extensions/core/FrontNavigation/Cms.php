<?php
/**
 * @brief		Front Navigation Extension: Forums
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		08 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Content
 */
class _Cms
{
	/**
	 * @brief	ID
	 */
	protected $id;
	
	/**
	 * @brief	Parent ID
	 */
	protected $parent;

	/**
	 * Generate a number of classes for this extension
	 *
	 * @return array
	 */
	public static function generate()
	{
		/* Toggling mod_rewrite doesn't update cms_menu cache automatically */
		if ( isset( \IPS\Data\Store::i()->cms_menu ) )
		{
			if ( ! isset( \IPS\Data\Store::i()->cms_menu['htaccess_mod_rewrite'] ) OR \IPS\Data\Store::i()->cms_menu['htaccess_mod_rewrite'] !== \IPS\Settings::i()->htaccess_mod_rewrite )
			{
				unset( \IPS\Data\Store::i()->cms_menu );
			}
			
			if ( ! isset( \IPS\Data\Store::i()->cms_menu['pages_is_default'] ) OR \IPS\Data\Store::i()->cms_menu['pages_is_default'] != \IPS\Application::load('cms')->default )
			{
				unset( \IPS\Data\Store::i()->cms_menu );
			}
		}
		
		/* Make a tree if we don't have one */
		if ( !isset( \IPS\Data\Store::i()->cms_menu ) )
		{
			$menu = array();
			foreach( \IPS\Db::i()->select( '*', 'cms_page_menu', NULL, 'menu_position' ) as $row )
			{
				/* Construct the node */
				$node = \IPS\cms\Pages\Menu::constructFromData( $row );

				/* Can we see the parent? */
				$permission = $node->permission;
				if ( $node->type === 'page' and $node->permission === 'page' )
				{
					try
					{
						$perms = \IPS\cms\Pages\Page::load( $node->content )->permissions();

						$permission = $perms['perm_view'];
					}
					catch( \OutOfRangeException $ex )
					{
						continue;
					}
				}

				/* Work out the parent */
				$parent = $node->parent_id ?: 0;

				/* Add it */
				$menu[ $parent ][ $node->_id ] = array(
					'id'         => $node->_id,
					'type'       => $node->type,
					'content'    => $node->content,
					'title'      => $node->title,
					'url'        => ( string ) $node->_url,
					'permission' => $permission
				);
			}
			
			try
			{
				$defaultUrl = \IPS\cms\Pages\Page::getDefaultPage()->url();
			}
			catch( \OutOfRangeException $ex )
			{
				$defaultUrl = \IPS\Http\Url::internal( 'app=cms&module=pages&controller=page', 'front', 'content' );
			}
			
			\IPS\Data\Store::i()->cms_menu = array(
				'menu'				   => $menu,
				'default'			   => (string) $defaultUrl,
				'htaccess_mod_rewrite' => \IPS\Settings::i()->htaccess_mod_rewrite,
				'pages_is_default'     => \IPS\Application::load('cms')->default
			);
		}

		$return = array();
		if ( count( \IPS\Data\Store::i()->cms_menu['menu'] ) )
		{
			foreach( \IPS\Data\Store::i()->cms_menu['menu'][0] as $id => $data )
			{
				$return[] = new self( $id );
			}
		}
		else
		{
			$return[] = new self(0);
		}
		return $return;
	}

	/**
	 * Constructor
	 *
	 * @param	int	$id	    The ID (0 for root)
	 * @param	int	$parent	The Parent ID (0 for root)
	 * @return	void
	 */
	public function __construct( $id = 0, $parent = 0 )
	{
		/* Set ID */
		$this->id = $id;
		$this->parent = $parent;
	}
	
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		if ( $this->id )
		{
			return \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['permission'] === '*' or \IPS\Member::loggedIn()->inGroup( explode( ',', \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['permission'] ) );
		}
		else
		{
			return !\IPS\Application::load('cms')->hide_tab and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'cms', 'pages' ) );
		}
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		if ( isset( \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ] ) )
		{
			$item = \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ];
			return \IPS\Member::loggedIn()->language()->addToStack( $this->id ? ( ( ! $item['title'] and $item['type'] === 'page' ) ? 'cms_page_' . $item['content'] : 'cms_menu_title_' . $this->id ) : '__app_cms' );
		}
		
		return \IPS\Member::loggedIn()->language()->addToStack('__app_cms');
	}
	
	/**
	 * Get Root Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
		if ( $this->id )
		{
			return \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['url'] ?: '#';
		}
		else
		{
			return \IPS\Data\Store::i()->cms_menu['default'];
		}
	}
	
	/**
	 * Get children
	 *
	 * @return	array
	 */
	public function children()
	{
		if ( isset( \IPS\Data\Store::i()->cms_menu['menu'][ $this->id ] ) and count( \IPS\Data\Store::i()->cms_menu['menu'][ $this->id ] ) )
		{
			$return = array();
			foreach ( \IPS\Data\Store::i()->cms_menu['menu'][ $this->id ] as $child )
			{
				$return[] = new Cms( $child['id'], $this->id );
			}
			return $return;
		}
		
		return array();
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
		if ( \IPS\Dispatcher::i()->application->directory === 'cms' )
		{
			if ( ! count( \IPS\Data\Store::i()->cms_menu['menu'] ) )
			{
				return TRUE;
			}

			if ( \IPS\cms\Pages\Page::$currentPage )
			{
				/* A page... ? */
				if ( \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['type'] === 'page' AND \IPS\cms\Pages\Page::$currentPage->id == \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['content'] )
				{
					return TRUE;
				}

				/* A folder that has the page inside? */
				if ( \IPS\Data\Store::i()->cms_menu['menu'][ $this->parent ][ $this->id ]['type'] === 'folder' )
				{
					if ( isset( \IPS\Data\Store::i()->cms_menu['menu'][ $this->id ] ) )
					{
						foreach( \IPS\Data\Store::i()->cms_menu['menu'][ $this->id ] as $id => $data )
						{
							if ( $this->checkInsideFolderForCurrentPage( $this->id ) === TRUE )
							{
								return TRUE;
							}
						}
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Check recursively to see if the current page is in side the folder
	 *
	 * @param $parent
	 *
	 * @return bool
	 */
	protected function checkInsideFolderForCurrentPage( $parent )
	{
		if ( isset( \IPS\Data\Store::i()->cms_menu['menu'][ $parent ] ) )
		{
			foreach( \IPS\Data\Store::i()->cms_menu['menu'][ $parent ] as $id => $data )
			{
				if ( \IPS\cms\Pages\Page::$currentPage->id == $data['content'] )
				{
					return TRUE;
				}
				else
				{
					if ( $this->checkInsideFolderForCurrentPage( $id ) === TRUE )
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}
}
<?php
/**
 * @brief		[Front] Page Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		25 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\front\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class _page extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		$this->view();
	}
	
	/**
	 * Display a page. Sounds simple doesn't it? Well it's not.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$page = $this->getPage();
		
		/* Database specific checks */
		if ( isset( \IPS\Request::i()->advancedSearchForm ) AND isset( \IPS\Request::i()->d ) )
		{
			/* showTableSearchForm just triggers __call which returns the database dispatcher HTML as we
			 * do not want the page content around the actual database */
			\IPS\Output::i()->output = $this->showTableSearchForm();
			return;
		}

		$includes = $page->getIncludes();

		if ( isset( $includes['js'] ) and is_array( $includes['js'] ) )
		{
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, array_values( $includes['js'] ) );
		}

		/* Display */
		if ( $page->ipb_wrapper or $page->type === 'builder' )
		{
			$page->setTheme();
			$nav = array();

			\IPS\Output::i()->title  = $page->getHtmlTitle();

			/* This has to be done after setTheme(), otherwise \IPS\Theme::switchTheme() can wipe out CSS includes */
			if ( isset( $includes['css'] ) and is_array( $includes['css'] ) )
			{
				\IPS\Output::i()->cssFiles  = array_merge( \IPS\Output::i()->cssFiles, array_values( $includes['css'] ) );
			}

			if ( $page->type === 'builder' )
			{
				list( $group, $name, $key ) = explode( '__', $page->template );
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('pages')->globalWrap( $nav, \IPS\cms\Theme::i()->getTemplate($group, 'cms', 'page')->$name( $page, $page->getWidgets() ), $page );
			}
			else
			{
				/* Populate \IPS\Output::i()->sidebar['widgets'] sidebar/header/footer widgets */
				$page->getWidgets();
				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('pages')->globalWrap( $nav, $page->getHtmlContent(), $page );
			}
			
			\IPS\Output::i()->sidebar['enabled'] = true;

			/* Set the meta tags, but do not reset them if they are already set - articles can define custom meta tags and this code
				overwrites the ones set by articles if we don't verify they aren't set first */
			if ( $page->meta_keywords AND ( !isset( \IPS\Output::i()->metaTags['keywords'] ) OR !\IPS\Output::i()->metaTags['keywords'] ) )
			{
				\IPS\Output::i()->metaTags['keywords'] = $page->meta_keywords;
			}

			if ( $page->meta_description AND ( !isset( \IPS\Output::i()->metaTags['description'] ) OR !\IPS\Output::i()->metaTags['description'] ) )
			{
				\IPS\Output::i()->metaTags['description'] = $page->meta_description;
			}

			/* Can only disable sidebar if HTML page */
			if ( ! $page->show_sidebar and $page->type === 'html' )
			{
				\IPS\Output::i()->sidebar['enabled'] = false;
			}

			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'pages/page.css', 'cms', 'front' ) );

			if ( \IPS\Request::i()->path == $page->full_path )
			{
				/* Just viewing this page, no database categories or records */
				$permissions = $page->permissions();
				\IPS\Session::i()->setLocation( $page->url(), explode( ",", $permissions['perm_view'] ), 'loc_cms_viewing_page', array( 'cms_page_' . $page->_id => TRUE ) );
			}

			if ( ! ( \IPS\Application::load('cms')->default AND ! $page->folder_id AND $page->default ) )
			{
				\IPS\Output::i()->breadcrumb['module'] = array( $page->url(), $page->_title );
			}
			
			\IPS\Output::i()->allowDefaultWidgets = FALSE;
			/* Let the dispatcher finish off and show page */
			return;
		}
		else
		{
			if ( isset( $includes['css'] ) and is_array( $includes['css'] ) )
			{
				\IPS\Output::i()->cssFiles  = array_merge( \IPS\Output::i()->cssFiles, array_values( $includes['css'] ) );
			}

			if ( $page->wrapper_template and $page->wrapper_template !== '_none_' and ! \IPS\Request::i()->isAjax() )
			{
				try
				{
					list( $group, $name, $key ) = explode( '__', $page->wrapper_template );
					\IPS\Output::i()->sendOutput( \IPS\cms\Theme::i()->getTemplate($group, 'cms', 'page')->$name( $page->getHtmlContent(), $page->getHtmlTitle() ), 200, $page->getContentType(), \IPS\Output::i()->httpHeaders );
				}
				catch( \OutOfRangeException $e )
				{

				}
			}

			/* Set the title */
			\IPS\Output::i()->title = $page->getHtmlTitle();
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( \IPS\Output::i()->title );

			/* Send straight to the output engine */
			\IPS\Output::i()->sendOutput( $page->getHtmlContent(), 200, $page->getContentType(), \IPS\Output::i()->httpHeaders );
		}
	}
	
	/**
	 * Get the current page
	 * 
	 * @return \IPS\cms\Pages\Page
	 */
	public function getPage()
	{
		$page = null;
		if ( isset( \IPS\Request::i()->page_id ) )
		{
			try
			{
				$page = \IPS\cms\Pages\Page::load( \IPS\Request::i()->page_id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T187/1', 404, '' );
			}
		}
		else if ( isset( \IPS\Request::i()->path ) AND  \IPS\Request::i()->path != '/' )
		{
			try
			{
				$page = \IPS\cms\Pages\Page::loadFromPath( \IPS\Request::i()->path );
			}
			catch ( \OutOfRangeException $e )
			{
				try
				{
					\IPS\Output::i()->redirect( \IPS\cms\Pages\Page::getUrlFromHistory( \IPS\Request::i()->path, ( isset( \IPS\Request::i()->url()->data['query'] ) ? \IPS\Request::i()->url()->data['query'] : NULL ) ), NULL, 301 );
				}
				catch( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'content_err_page_404', '2T187/2', 404, '' );
				}
			}
		}
		else
		{
            try
            {
                $page = \IPS\cms\Pages\Page::getDefaultPage();
            }
            catch ( \OutOfRangeException $e )
            {
                \IPS\Output::i()->error( 'content_err_page_404', '2T257/1', 404, '' );
            }
		}
		
		if ( $page === NULL )
		{
            \IPS\Output::i()->error( 'content_err_page_404', '2T257/2', 404, '' );
		}

		if ( ! $page->can('view') )
		{
			\IPS\Output::i()->error( 'content_err_page_403', '2T187/3', 403, '' );
		}
		
		/* Set the current page, so other blocks, DBs, etc don't have to figure out where they are */
		\IPS\cms\Pages\Page::$currentPage = $page;
		
		return $page;
	}
	
	/**
	 * Capture database specific things
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$page = $this->getPage();
		
		$databaseId = ( isset( \IPS\Request::i()->d ) ) ? \IPS\Request::i()->d : $page->getDatabase()->_id;
		
		if ( $databaseId !== NULL )
		{
			try
			{
				return \IPS\cms\Databases\Dispatcher::i()->setDatabase( $databaseId )->run();
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T257/3', 404, '' );
			}
		}
	}

	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed()
	{
		return $this->__call( 'embed', func_get_args() );
	}
}
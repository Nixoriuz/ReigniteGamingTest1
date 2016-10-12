<?php
/**
 * @brief		[Database] Category Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		Content
 * @since		16 April 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class _category extends \IPS\cms\Databases\Controller
{

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
	 * Clear any filters
	 *
	 * @return void
	 */
	public function clearFilters()
	{
		$catClass = 'IPS\cms\Categories' .  \IPS\cms\Databases\Dispatcher::i()->databaseId;

		try
		{
			$category = $catClass::loadAndCheckPerms( \IPS\cms\Databases\Dispatcher::i()->categoryId );
			$category->saveFilterCookie( FALSE );

			\IPS\Output::i()->redirect( $category->url(), 'cms_filters_cleared' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2T254/1', 403, '' );
		}
	}

	/**
	 * Display a category. Please.
	 *
	 * @return	void
	 */
	public function view()
	{
		$category     = NULL;
		$fieldClass   = 'IPS\cms\Fields' .  \IPS\cms\Databases\Dispatcher::i()->databaseId;
		$catClass     = 'IPS\cms\Categories' .  \IPS\cms\Databases\Dispatcher::i()->databaseId;
		$database     = \IPS\cms\Databases::load( \IPS\cms\Databases\Dispatcher::i()->databaseId );
		$breadcrumbs  = NULL;

		try
		{
			$category = $catClass::loadAndCheckPerms( \IPS\cms\Databases\Dispatcher::i()->categoryId );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2T254/2', 403, '' );
		}
		
		$customFields = $fieldClass::data( 'view', $category, $fieldClass::FIELD_SKIP_TITLE_CONTENT );

		if ( ! $database->use_categories )
		{
			$breadcrumbs = \IPS\Output::i()->breadcrumb;
		}
				
		/* Check cookie */
		$activeFilters = array();
		$where = array();
		$cookie = $category->getFilterCookie();
		if ( $cookie !== NULL )
		{
			foreach( $cookie as $f => $v )
			{
				$k = 'content_field_' . $f;
				if ( isset( $customFields[ $f ] ) and !isset( \IPS\Request::i()->$k ) and $v !== '___any___' )
				{
					if ( is_array( $v ) )
					{
						$like = array();
						foreach( $v as $val )
						{
							if ( $val === 0 or ! empty( $val ) )
							{
								$like[]  = "CONCAT( ',', " .  mb_substr( $k, 8 ) . ", ',') LIKE '%," . \IPS\Db::i()->real_escape_string( $val ) . ",%'";
							}
						}
						
						$where[] = array( '( ' . \IPS\Db::i()->in( mb_substr( $k, 8 ), $v ) .  ( count( $like ) ? " OR (" . implode( ' OR ', $like ) . ') )' : ')' ) );
					}
					else
					{
						if ( $v !== 0 and ! $v )
						{
							$where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
						}
						else
						{
							$where[] = array( mb_substr( $k, 8 ) . "=?", $v );
						}
					}
					$activeFilters[ $f ] = array( 'field' => $customFields[ $f ], 'value' => $customFields[ $f ]->displayValue( $v ) );
				}
			}
		}
		
		if ( ! count( $where ) )
		{
			$where = NULL;
		}
		
		$table = new \IPS\Helpers\Table\Content( 'IPS\cms\Records' . \IPS\cms\Databases\Dispatcher::i()->databaseId, $category->url(), $where, $category, NULL );
		$table->tableTemplate = array( \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' ), 'categoryTable' );
		$table->rowsTemplate = array( \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' ), 'recordRow' );
		$table->baseUrl = $table->baseUrl->setQueryString( 'd', \IPS\cms\Databases\Dispatcher::i()->databaseId );
		$table->hover = TRUE;
		$table->sortBy		  = ( isset( \IPS\Request::i()->sortby ) ) ? \IPS\Request::i()->sortby  : ( $database->field_sort ? $database->field_sort : 'record_last_comment' );
		$table->sortDirection = ( isset( \IPS\Request::i()->sortdirection ) ) ? \IPS\Request::i()->sortdirection : ( $database->field_direction ? $database->field_direction : 'desc' );
		$table->limit		  = $database->field_perpage   ? $database->field_perpage   : 25;
		$table->title = \IPS\Member::loggedIn()->language()->addToStack( $database->use_categories ? 'x_records_in_this_category' : 'x_records' , FALSE, array( 'sprintf' => array( $category->records, $database->recordWord( $category->records ) ) ) );

		/* Make sure table doesn't add breadcrumbs if we're not using categories */
		if ( ! $database->use_categories )
		{
			\IPS\Output::i()->breadcrumb = $breadcrumbs;
		}

		/* Custom Search */
		$filterOptions = array(
				'all'			=> 'content_all_records',
				'open'			=> 'content_open_records',
				'locked'		=> 'content_locked_records',
		);
		$timeFrameOptions = array(
				'show_all'			=> 'show_all',
				'today'				=> 'today',
				'last_5_days'		=> 'last_5_days',
				'last_7_days'		=> 'last_7_days',
				'last_10_days'		=> 'last_10_days',
				'last_15_days'		=> 'last_15_days',
				'last_20_days'		=> 'last_20_days',
				'last_25_days'		=> 'last_25_days',
				'last_30_days'		=> 'last_30_days',
				'last_60_days'		=> 'last_60_days',
				'last_90_days'		=> 'last_90_days',
		);

		if ( \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->last_visit)
		{
			$timeFrameOptions['since_last_visit'] = \IPS\Member::loggedIn()->language()->addToStack('since_last_visit', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( \IPS\Member::loggedIn()->last_visit ) ) ) );
		}

		$sortBy = array(
			'record_last_comment'	=> 'content_record_last_comment',
			'record_comments'		=> 'content_record_comments',
			'record_views'			=> 'content_record_views',
			'field_' . $database->field_title	=> 'content_record_title',
			'record_publish_date'	=> 'content_record_publish_date'
		);
		if ( !isset( $sortBy[ $database->field_sort ] ) )
		{
			switch ( $database->field_sort )
			{
				case 'primary_id_field':
					$sortBy[ $database->field_sort ] = 'database_field__id';
					$table->sortOptions['database_field__id'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__id'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__id');
					break;
				case 'member_id':
					$sortBy[ $database->field_sort ] = 'database_field__member';
					$table->sortOptions['database_field__member'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__member'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__member');
					break;
				case 'rating_real':
					$sortBy[ $database->field_sort ] = 'database_field__rating';
					$table->sortOptions['database_field__rating'] = $database->field_sort;
					\IPS\Member::loggedIn()->language()->words['sort_database_field__rating'] = \IPS\Member::loggedIn()->language()->addToStack('database_field__rating');
					break;
			}
		}
		
		if ( !$database->options['comments'] )
		{
			unset ( $sortBy['record_last_comment'] );
			unset ( $sortBy['record_comments'] );
			unset ( $table->sortOptions['record_last_comment'] );
			unset ( $table->sortOptions['record_comments'] );
			unset ( $table->sortOptions['last_comment'] );
			unset ( $table->sortOptions['num_comments'] );
		}
		else
		{
			unset( $table->sortOptions['updated'] );
		}

		if ( !$database->options['reviews'] )
		{
			unset ( $table->sortOptions['num_reviews'] );
			unset( $table->sortOptions['rating'] );
		}

		/* If the sort field isn't one of the above, best add it */
		if ( mb_substr( $database->field_sort, 0, 6 ) === 'field_' )
		{
			if ( $database->field_title !== mb_substr( $database->field_sort, 6 ) )
			{
				$sortBy[ $database->field_sort ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_field_' . mb_substr( $database->field_sort, 6 ) );
			}
		}

		$table->advancedSearch = array(
			'record_type'	 => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $filterOptions ) ),
			'sortby'		 => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $sortBy ) ),
			'sortdirection' => array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => array(
				'asc'			=> 'asc',
				'desc'			=> 'desc',
			) )
			),
			'time_frame'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $timeFrameOptions ) ),
		);

		foreach( $customFields as $obj )
		{
			if ( $obj->filter )
			{
				\IPS\Member::loggedIn()->language()->words['content_field_' . $obj->id ] = $obj->_title;
				$table->advancedSearch[ 'content_field_' . $obj->id ] = array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $obj->extra, 'multiple' => TRUE, 'noDefault' => true ) );
				$table->advancedSearch['sortby'][1]['options']['field_' . $obj->id ] = 'content_field_' . $obj->id;
			}

			$table->sortOptions[ 'field_' . $obj->id ] = 'field_' . $obj->id;
		}

		$table->advancedSearchCallback = function( $table, $values ) use ( $database, $sortBy )
		{
			/* Type */
			foreach( $values as $k => $v )
			{
				if ( mb_substr( $k, 0, 14 ) === 'content_field_' )
				{
					if ( is_array( $v ) )
					{
						$like = array();
						foreach( $v as $val )
						{
							if ( $val === 0 or ! empty( $val ) )
							{
								$like[]  = "CONCAT( ',', " .  mb_substr( $k, 8 ) . ", ',') LIKE '%," . \IPS\Db::i()->real_escape_string( $val ) . ",%'";
							}
						}
						
						$table->where[] = array( '( ' . \IPS\Db::i()->in( mb_substr( $k, 8 ), $v ) . ( count( $like ) ? " OR (" . implode( ' OR ', $like ) . ') )' : ')' ) );
					}
					else
					{
						if ( $v !== '___any___' )
						{
							if ( $v !== 0 and ! $v )
							{
								$table->where[] = array( mb_substr( $k, 8 ) . " IS NULL" );
							}
							else
							{
								$table->where[] = array( mb_substr( $k, 8 ) . "=?", $v );
							}
						}
					}
				}
			}

			if ( isset( $values['record_type'] ) )
			{
				switch ( $values['record_type'] )
				{
					case 'open':
						$table->where[] = 'record_locked=0';
						break;
					case 'locked':
						$table->where[] = 'record_locked=1';
						break;
				}
			}

			/* Sort */
			if ( isset( $values['sortby'] ) and isset( $sortBy[ $values['sortby'] ] ) )
			{
				$table->sortBy = $values['sortby'];
				$table->sortDirection = $values['sortdirection'];
			}
			
			/* Cutoff */
			$days = NULL;
			if ( isset( $values['time_frame'] ) )
			{
				switch ( $values['time_frame'] )
				{
					case 'today':
						$days = 1;
						break;
					case 'last_5_days':
						$days = 5;
						break;
					case 'last_7_days':
						$days = 7;
						break;
					case 'last_10_days':
						$days = 10;
						break;
					case 'last_15_days':
						$days = 15;
						break;
					case 'last_20_days':
						$days = 20;
						break;
					case 'last_25_days':
						$days = 25;
						break;
					case 'last_30_days':
						$days = 30;
						break;
					case 'last_60_days':
						$days = 60;
						break;
					case 'last_90_days':
						$days = 90;
						break;
					case 'since_last_visit':
						$table->where[] = array( 'record_last_comment>?', \IPS\Member::loggedIn()->last_visit );
						break;
				}
				if ( $days !== NULL )
				{
					$table->where[] = array( 'record_last_comment>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $days . 'D' ) ) );
				}
			}
		};

		/* RSS */
		if ( $database->rss )
		{
			$rssUrl  = $table->baseUrl->setQueryString('rss', 1 );
			$rssName = $database->_title . ': ' . $category->_title;
			\IPS\Output::i()->rssFeeds[ $rssName ] = $rssUrl;
			
			/* Show RSS feed */
			if ( isset( \IPS\Request::i()->rss ) )
			{
				$rssName = \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id ) . ': ' . \IPS\Member::loggedIn()->language()->get('content_cat_name_' . $category->id );
				$document     = \IPS\Xml\Rss::newDocument( $table->baseUrl, $rssName, $rssName );
				$contentField = 'field_' . $database->field_content;
				
				foreach ( $table->getRows( array() ) as $record )
				{
					if ( ! $record->hidden() )
					{
						$document->addItem( $record->_title, $record->url(), $record->$contentField, \IPS\DateTime::ts( $record->record_last_comment ), $record->_id );
					}
				}
		
				/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			}
		}

		/* Update location */
		$permissions = $category->permissions();
		\IPS\Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_cms_viewing_db_cat', array( 'content_db_' . $database->id => TRUE, 'content_cat_name_' . $category->id => TRUE ) );

		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/list.css', 'cms', 'front' ) );

		\IPS\cms\Databases\Dispatcher::i()->output .= \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' )->categoryHeader( $category, (string) $table, $activeFilters );
		
		if ( ( $category->hasChildren() AND $category->show_records ) OR ! $category->hasChildren() )
		{
			\IPS\cms\Databases\Dispatcher::i()->output .= (string) $table;
		}
		
		\IPS\cms\Databases\Dispatcher::i()->output .= \IPS\cms\Theme::i()->getTemplate( $category->_template_listing, 'cms', 'database' )->categoryFooter( $category, (string) $table, $activeFilters );
	}
	
	/**
	 * Form
	 *
	 * @return	void
	 */
	public function form()
	{
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_records.js', 'cms' ) );

		$database		= \IPS\cms\Databases::load( \IPS\cms\Databases\Dispatcher::i()->databaseId );
		$recordClass	= '\IPS\cms\Records' . \IPS\cms\Databases\Dispatcher::i()->databaseId;
		$categoryClass	= '\IPS\cms\Categories' . \IPS\cms\Databases\Dispatcher::i()->databaseId;
		$category		= $categoryClass::loadAndCheckPerms( \IPS\cms\Databases\Dispatcher::i()->categoryId );
		$fieldsClass	= '\IPS\cms\Fields' . \IPS\cms\Databases\Dispatcher::i()->databaseId;
		$title			= \IPS\Member::loggedIn()->language()->addToStack( 'content_record_form_new_record', FALSE, array( 'sprintf' => array( $database->recordWord( 1, TRUE ) ) ) );
		
		$form = $recordClass::create( $category );
		$form->class = 'ipsForm_vertical';
	
		$hasModOptions = FALSE;
		
		if ( $recordClass::modPermission( 'lock', NULL, $category ) or
			 $recordClass::modPermission( 'pin', NULL, $category ) or
			 $recordClass::modPermission( 'hide', NULL, $category ) or
			 $recordClass::modPermission( 'feature', NULL, $category ) )
		{
			$hasModOptions = TRUE;
		}
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\cms\Theme::i(), 'getTemplate' ), array( $database->template_form, 'cms', 'database' ) ), 'recordForm' ), NULL, $category, $database, \IPS\cms\Pages\Page::$currentPage, $title, $hasModOptions );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( $title );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/form.css', 'cms', 'front' ) );

		try
		{
			if ( $database->use_categories )
			{
				foreach( $category->parents() AS $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( $category->url(), $category->_title );
			}
		}
		catch( \Exception $e ) {}
	
		\IPS\Output::i()->breadcrumb[] = array( NULL, $title );
	}
	
	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$meowBreed = '\IPS\cms\Categories' . \IPS\cms\Databases\Dispatcher::i()->databaseId;
			$meow      = $meowBreed::load( \IPS\cms\Databases\Dispatcher::i()->categoryId );
			\IPS\cms\Records::markContainerRead( $meow );
			\IPS\Output::i()->redirect( $meow->url() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'module_no_permission', '2T254/3', 403, '' );
		}
	}

}
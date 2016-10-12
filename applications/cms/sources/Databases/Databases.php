<?php
/**
 * @brief		Databases Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		31 March 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Databases Model
 */
class _Databases extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'database_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static $databaseTable = 'cms_databases';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'database_key', 'database_page_id' );
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnOrder = 'id';
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static $nodeSortable = FALSE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = '';
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
			'view' 				=> 'view',
			'read'				=> 2,
			'add'				=> 3,
			'edit'				=> 4,
			'reply'				=> 5,
			'review'            => 7,
			'rate'				=> 6
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'cms';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'databases';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_content_';
		
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = FALSE;

	/**
	 * [Brief]	Bump on edit only
	 */
	const BUMP_ON_EDIT = 1;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const BUMP_ON_COMMENT = 2;
	
	/**
	 * [Brief]	Bump on edit only
	 */
	const CATEGORY_VIEW_CATEGORIES = 0;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const CATEGORY_VIEW_FEATURED = 1;

	/**
	 * [Brief] Database template groups
	 */
	public static $templateGroups = array(
		'categories' => 'category_index',
		'featured'   => 'category_articles',
		'listing'    => 'listing',
		'display'    => 'display',
		'form'       => 'form'
	);

	/**
	 * @brief	Bitwise values for database_options field
	 */
	public static $bitOptions = array(
		'options' => array(
			'options' => array(
				'comments'              => 1,   // Enable comments?
				'reviews'               => 2,   // Enable reviews?
				'comments_mod'          => 4,   // Enable comment moderation?
				'reviews_mod'           => 8,   // Enable reviews moderation?
			    'indefinite_own_edit'   => 16,  // Enable authors to indefinitely edit their own articles
			)
		)
	);

	/**
	 * Return all databases
	 *
	 * @return	array
	 */
	public static function databases()
	{
		if ( ! static::$gotAll )
		{
            if ( \IPS\Db::i()->checkForTable( static::$databaseTable ) )
            {
                foreach( \IPS\Db::i()->select( '*', static::$databaseTable ) as $db )
                {
                    $id = $db[ static::$databasePrefix . static::$databaseColumnId ];
                    static::$multitons[ $id ] = static::constructFromData( $db );
                }
            }
				
			static::$gotAll = true;
		}
	
		return static::$multitons;
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		$obj->preLoadWords();

		return $obj;
	}

	/**
	 * Return data for the ACP Menu
	 * 
	 * @return array
	 */
	public static function acpMenu()
	{
		$menu = array();

		foreach(
			\IPS\Db::i()->select( '*, core_sys_lang_words.word_custom as database_name', 'cms_databases', NULL, 'core_sys_lang_words.word_custom' )->join(
				'core_sys_lang_words', "core_sys_lang_words.word_key=CONCAT( 'content_db_', cms_databases.database_id ) AND core_sys_lang_words.lang_id=" . \IPS\Member::loggedIn()->language()->id
			)
			as $row )
		{
			$menu[] = array(
				'id'             => $row['database_id'],
				'title'          => $row['database_name'],
				'use_categories' => $row['database_use_categories']
			);
		}

        return $menu;
	}

	/**
	 * Checks and fixes existing DB
	 *
	 * @param   int     $id     Database ID
	 * @return  int     $fixes  Number of fixes made (0 if none)
	 *
	 * @throws \OutOfRangeException
	 */
	public static function checkandFixDatabaseSchema( $id )
	{
		$fixes     = 0;
		$json      = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/cms/data/databaseschema.json" ), true );
		$table     = $json['cms_custom_database_1'];
		$tableName = 'cms_custom_database_' . $id;

		if ( ! \IPS\Db::i()->checkForTable( $tableName ) )
		{
			throw new \OutOfRangeException;
		}

		$schema		= \IPS\Db::i()->getTableDefinition( $tableName );
		$changes	= array();

		/* Colums */
		foreach( $table['columns'] as $key => $data )
		{
			if ( ! isset( $schema['columns'][ $key ] ) )
			{
				$changes[] = "ADD COLUMN " . \IPS\Db::i()->compileColumnDefinition( $data );
				$fixes++;
			}
		}

		/* Indexes */
		foreach( $table['indexes'] as $key => $data )
		{
			/* No index */
			if ( ! isset( $schema['indexes'][ $key ] ) )
			{
				$changes[] = \IPS\Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
			else if ( implode( '.', $data['columns'] ) != implode( '.', $schema['indexes'][ $key ]['columns'] ) )
			{
				/* Check columns */
				if( $key == 'PRIMARY KEY' )
				{
					$changes[] = "DROP PRIMARY KEY";
				}
				else
				{
					$changes[] = "DROP " . \IPS\Db::i()->escape_string( $key );
				}

				$changes[] =  \IPS\Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
		}

		/* We collect all the changes so we can run one database query instead of, potentially, dozens */
		if( count( $changes ) )
		{
			\IPS\Db::i()->query( "ALTER TABLE " . \IPS\Db::i()->prefix . $tableName . " " . implode( ', ', $changes ) );
		}

		return $fixes;
	}

	/**
	 * Create a new database
	 * 
	 * @param 	\IPS\cms\Databases 	$database		ID of database to create
	 * @return	void
	 */
	public static function createDatabase( $database )
	{
		$json  = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/cms/data/databaseschema.json" ), true );
		$table = $json['cms_custom_database_1'];
	
		$table['name'] = 'cms_custom_database_' . $database->id;
		
		foreach( $table['columns'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['columns'][ $name ] );
			}
		}
		
		foreach( $table['indexes'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['indexes'][ $name ] );
			}
		}
		
		try
		{
			if ( ! \IPS\Db::i()->checkForTable( $table['name'] ) )
			{
				\IPS\Db::i()->createTable( $table );
			}
		}
		catch( \IPS\Db\Exception $ex )
		{
			throw new \LogicException( $ex );
		}

		/* Populate default custom fields */
		$fieldsClass = 'IPS\cms\Fields' . $database->id;
		$fieldTitle   = array();
		$fieldContent = array();
		$catTitle     = array();
		$catDesc      = array();

		foreach( \IPS\Lang::languages() as $id => $lang )
		{
			$fieldTitle[ $id ]   = $lang->get('content_fields_is_title');
			$fieldContent[ $id ] = $lang->get('content_fields_is_content');
			$catTitle[ $id ]     = $lang->get('content_database_noun_pu');
			$catDesc[ $id ]      = '';
		}

		/* Title */
		$titleField = new $fieldsClass;
		$titleField->saveForm( $titleField->formatFormValues( array(
			'field_title'			=> $fieldTitle,
			'field_type'			=> 'Text',
			'field_key'				=> 'titlefield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1,
			'field_max_length'		=> 255
	       ) ) );

		$database->field_title = $titleField->id;
		$perms = $titleField->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Content */
		$contentField = new $fieldsClass;
		$contentField->saveForm( $contentField->formatFormValues( array(
			'field_title'			=> $fieldContent,
			'field_type'			=> 'Editor',
			'field_key'				=> 'contentfield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_truncate'		=> 100,
			'field_topic_format'	=> '{value}',
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1
         ) ) );

		$database->field_content = $contentField->id;
		$perms = $contentField->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Create a category */
		$category = new \IPS\cms\Categories;
		$category->database_id = $database->id;

		$category->saveForm( $category->formatFormValues( array(
             'category_name'		 => $catTitle,
             'category_description'  => $catDesc,
             'category_parent_id'    => 0,
             'category_has_perms'    => 0,
             'category_show_records' => 1
         ) ) );

		$perms = $category->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		$database->options['comments'] = 1;
		$database->save();
	}

	/**
	 * @brief   Language strings pre-loaded
	 */
	protected $langLoaded = FALSE;

	/**
	 * Get database id
	 * 
	 * @return string
	 */
	public function get__id()
	{
		return $this->id;
	}

	/**
	 * Get comment bump
	 *
	 * @return int
	 */
	public function get__comment_bump()
	{
		if ( $this->comment_bump === 0 )
		{
			return static::BUMP_ON_EDIT;
		}
		else if ( $this->comment_bump === 1 )
		{
			return static::BUMP_ON_COMMENT;
		}
		else if ( $this->comment_bump === 2 )
		{
			return static::BUMP_ON_EDIT + static::BUMP_ON_COMMENT;
		}
	}
	
	/**
	 * Get database name
	 *
	 * @return string
	 */
	public function get__title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('content_db_' . $this->id);
	}
	
	/**
	 * Get database description
	 *
	 * @return string
	 */
	public function get__description()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('content_db_' . $this->id . '_desc');
	}

	/**
	 * Get default category
	 *
	 * @return string
	 */
	public function get__default_category()
	{
		$categoryClass = '\IPS\cms\Categories' . $this->id;
		if ( $this->default_category )
		{
			try
			{
				$categoryClass::load( $this->default_category );
				return $this->default_category;
			}
			catch( \OutOfRangeException $e )
			{
				$this->default_category = NULL;
			}
		}

		if ( ! $this->default_category )
		{
			$roots = $categoryClass::roots( NULL );

			if ( ! count( $roots ) )
			{
				/* Create a category */
				$category = new \IPS\cms\Categories;
				$category->database_id = $this->id;

				$catTitle = array();
				$catDesc  = array();

				foreach( \IPS\Lang::languages() as $id => $lang )
				{
					$catTitle[ $id ] = $lang->get('content_database_noun_pu');
					$catDesc[ $id ]  = '';
				}

				$category->saveForm( $category->formatFormValues( array(
                  'category_name'		  => $catTitle,
                  'category_description'  => $catDesc,
                  'category_parent_id'    => 0,
                  'category_has_perms'    => 0,
                  'category_show_records' => 1
                ) ) );

				$perms = $category->permissions();

				\IPS\Db::i()->update( 'core_permission_index', array(
					'perm_view'	 => '*',
					'perm_2'	 => '*',
					'perm_3'     => '*'
				), array( 'perm_id=?', $perms['perm_id']) );

				$roots = $categoryClass::roots( NULL );
			}

			$category = array_shift( $roots );

			$this->default_category = $category->id;
			$this->save();

			/* Update records */
			\IPS\Db::i()->update( 'cms_custom_database_' . $this->id, array( 'category_id' => $category->id ), array( 'category_id=0' ) );
		}

		return $this->default_category;
	}

	/**
	 * Get fixed field data
	 * 
	 * @return array
	 */
	public function get_fixed_field_perms()
	{
		if ( ! is_array( $this->_data['fixed_field_perms'] ) )
		{
			$this->_data['fixed_field_perms'] = json_decode( $this->_data['fixed_field_perms'], true );
		}
		
		if ( is_array( $this->_data['fixed_field_perms'] ) )
		{
			return $this->_data['fixed_field_perms'];
		}
		
		return array();
	}

	/**
	 * Set the "fixed field" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_fixed_field_perms( $value )
	{
		$this->_data['fixed_field_perms'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get fixed field settings
	 *
	 * @return array
	 */
	public function get_fixed_field_settings()
	{
		if ( ! is_array( $this->_data['fixed_field_settings'] ) )
		{
			$this->_data['fixed_field_settings'] = json_decode( $this->_data['fixed_field_settings'], true );
		}

		if ( is_array( $this->_data['fixed_field_settings'] ) )
		{
			return $this->_data['fixed_field_settings'];
		}

		return array();
	}

	/**
	 * Set the "fixed field" settings field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_fixed_field_settings( $value )
	{
		$this->_data['fixed_field_settings'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get feature settings, settings
	 *
	 * @return array
	 */
	public function get_featured_settings()
	{
		if ( ! is_array( $this->_data['featured_settings'] ) )
		{
			$this->_data['featured_settings'] = json_decode( $this->_data['featured_settings'], true );
		}

		if ( is_array( $this->_data['featured_settings'] ) )
		{
			return $this->_data['featured_settings'];
		}

		return array();
	}

	/**
	 * Set the "featured settings" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_featured_settings( $value )
	{
		$this->_data['featured_settings'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission		A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member			The member or group to check (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( $permission, $member=NULL )
	{
		/* If we're looking from the front, make sure the database page also passes */
		if ( $permission === 'view' and \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' and $this->page_id )
		{
			try
			{
				return parent::can( 'view', $member ) AND \IPS\cms\Pages\Page::load( $this->page_id )->can( 'view', $member );
			}
			catch( \OutOfRangeException $ex )
			{
				return parent::can( 'view', $member );
			}
		}

		return parent::can( $permission, $member );
	}

	/**
	 * Sets up and preloads some words
	 *
	 * @return void
	 */
	public function preLoadWords()
	{
		/* Skip this during installation / uninstallation as the words won't be loaded */
		if ( !\IPS\Dispatcher::hasInstance() or \IPS\Dispatcher::i()->controllerLocation === 'setup' OR ( \IPS\Dispatcher::i()->controllerLocation === 'admin' AND ( !\IPS\Dispatcher::i()->module OR \IPS\Dispatcher::i()->module->key === 'applications' ) ) )
		{
			$this->langLoaded = TRUE;
			return;
		}
		
		if ( ! $this->langLoaded )
		{
			\IPS\Member::loggedIn()->language()->words['__indefart_content_record_comments_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( '__indefart_content_record_comments_title' );
			\IPS\Member::loggedIn()->language()->words['__indefart_content_record_reviews_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( '__indefart_content_record_reviews_title' );

			\IPS\Member::loggedIn()->language()->words['content_record_comments_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_record_comments_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE ) ) ) );
			\IPS\Member::loggedIn()->language()->words['content_record_reviews_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_record_reviews_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE ) ) ) );
			\IPS\Member::loggedIn()->language()->words['content_record_comments_title_' . $this->id . '_pl' ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_record_comments_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE ) ) ) );
			\IPS\Member::loggedIn()->language()->words['content_record_reviews_title_' . $this->id . '_pl' ] = \IPS\Member::loggedIn()->language()->addToStack( 'content_record_reviews_title', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE ) ) ) );

			$fieldsClass = '\IPS\cms\Fields' . $this->id;
			$customFields = $fieldsClass::data( 'view', $this );

			foreach ( $customFields AS $id => $field)
			{
				\IPS\Member::loggedIn()->language()->words['sort_field_' . $id] = $field->_title;
			}

			if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' )
			{
				/* Moderator tools */
				\IPS\Member::loggedIn()->language()->words['modperms__core_Content_cms_Records' . $this->id ] = $this->_title;

				/* Editor Areas */
				\IPS\Member::loggedIn()->language()->words['editor__cms_Records' . $this->id ] = $this->_title;

				foreach( array( 'pin', 'unpin', 'feature', 'unfeature', 'edit', 'hide', 'unhide', 'view_hidden', 'future_publish', 'view_future', 'move', 'lock', 'unlock', 'reply_to_locked', 'delete' ) as $lang )
				{
					\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_db_lang_sl_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_record', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );

					if ( in_array( $lang, array( 'edit', 'hide', 'unhide', 'view_hidden', 'delete' ) ) )
					{
						\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_record_comments_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rcomment', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );
						\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_record_reviews_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rreview', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );

					}
				}
			}

			$this->langLoaded = true;
		}
	}

	/**
	 * "Records" / "Record" word
	 *
	 * @param	int	    $number	Number
	 * @param   bool    $upper  ucfirst string
	 * @return	string
	 */
	public function recordWord( $number = 2, $upper = FALSE )
	{
		$case = $upper ? 'u' : 'l';
		return $number == 1 ? \IPS\Member::loggedIn()->language()->addToStack("content_db_lang_s{$case}_{$this->id}") : \IPS\Member::loggedIn()->language()->addToStack("content_db_lang_p{$case}_{$this->id}");
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		$fieldsClass = '\IPS\cms\Fields' . $this->id;

		$class = '\IPS\cms\Categories' . $this->id;
		foreach( $class::roots( NULL, NULL, array(), $this->id ) as $id => $cat )
		{
			$cat->delete();
		}

		foreach( $fieldsClass::roots( NULL ) as $id => $field )
		{
			$field->delete( TRUE );
		}

		/* Delete comments */
		\IPS\Db::i()->delete( 'cms_database_comments', array( 'comment_database_id=?', $this->id ) );
		
		/* Delete records */
		\IPS\Db::i()->dropTable( 'cms_custom_database_' . $this->id, TRUE );
		
		/* Delete revisions */
		\IPS\Db::i()->delete( 'cms_database_revisions', array( 'revision_database_id=?', $this->id ) );

		/* Delete notifications */
		$memberIds	= array();

		foreach( \IPS\DB::i()->select( 'member', 'core_notifications', array( 'item_class=? ', 'IPS\cms\Records' . $this->id ) ) as $member )
		{
			$memberIds[ $member ]	= $member;
		}

		\IPS\Db::i()->delete( 'core_notifications', array( 'item_class=? ', 'IPS\cms\Records' . $this->id ) );
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=?', 'cms', 'records' . $this->id ) );

		foreach( $memberIds as $member )
		{
			\IPS\Member::load( $member )->recountNotifications();
		}

		/* Remove from search */
		\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\cms\Records' . $this->id );

		/* Delete custom languages */
		\IPS\Lang::deleteCustom( 'cms', "content_db_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_" . $this->id . '_desc');
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_sl_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_pl_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_su_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_pu_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_ia_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "content_db_lang_sl_" . $this->id . '_pl' );
		\IPS\Lang::deleteCustom( 'cms', "__indefart_content_db_lang_sl_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "cms_create_menu_records_" . $this->id );
		\IPS\Lang::deleteCustom( 'cms', "cms_records" . $this->id . '_pl' );
		\IPS\Lang::deleteCustom( 'cms', "module__cms_records" . $this->id );

		/*  Unclaim attachments */
		\IPS\File::unclaimAttachments( 'content_Records_' . $this->id );

		/* Remove widgets */
		$this->removeWidgets();

		parent::delete();
	}

	/**
	 * Remove any database widgets
	 *
	 * @return void
	 */
	public function removeWidgets()
	{
		$databaseWidgets = array( 'Database', 'LatestArticles' );

		foreach ( \IPS\Db::i()->select( '*', 'cms_page_widget_areas' ) as $item )
		{
			$pageBlocks   = json_decode( $item['area_widgets'], TRUE );
			$resaveBlock  = NULL;
			foreach( $pageBlocks as $id => $pageBlock )
			{
				if( $pageBlock['app'] == 'cms' AND in_array( $pageBlock['key'], $databaseWidgets ) AND ! empty( $pageBlock['configuration']['database'] ) )
				{
					if ( $pageBlock['configuration']['database'] == $this->id )
					{
						$resaveBlock = $pageBlocks;
						unset( $resaveBlock[ $id ] );
					}
				}
			}

			if ( $resaveBlock !== NULL )
			{
				\IPS\Db::i()->update( 'cms_page_widget_areas', array( 'area_widgets' => json_encode( $resaveBlock ) ), array( 'area_page_id=? and area_area=?', $this->id, $item['area_area'] ) );
			}
		}
	}

	/**
	 * Set the permission index permissions
	 *
	 * @param	array	$insert	Permission data to insert
	 * @param	\IPS\Helpers\Form\Matrix	$matrix \IPS\Helpers\Form\Matrix
	 * @return  void
	 */
	public function setPermissions( $insert, \IPS\Helpers\Form\Matrix $matrix )
	{
		parent::setPermissions( $insert, $matrix );
		
		/* Clone these permissions to all categories that do not have permissions */
		$class = '\IPS\cms\Categories' . $this->id;
		foreach( $class::roots( NULL, NULL, array(), $this->id ) as $category )
		{
			$this->setPermssionsRecursively( $category );
		}
	}
	
	/**
	 * Recursively set permissions
	 *
	 * @param	\IPS\cms\Categrories	$category		Category object
	 * @return	void
	 */
	protected function setPermssionsRecursively( $category )
	{
		if ( ! $category->has_perms )
		{
			$category->cloneDatabasePermissions();
		}
		
		foreach( $category->children() as $child )
		{
			$this->setPermssionsRecursively( $child );
		}
	}

	
	/**
	 * @brief	Number of categories
	 */
	protected $_numberOfCategories = NULL;
	
	/**
	 * Get the number of categories in this database
	 *
	 * @return  int
	 */
	public function numberOfCategories()
	{
		if ( $this->_numberOfCategories === NULL )
		{
			$this->_numberOfCategories = \IPS\Db::i()->select( 'count(*)', 'cms_database_categories', array( 'category_database_id=?', $this->_id ) )->first();
		}
		return $this->_numberOfCategories;
	}

}
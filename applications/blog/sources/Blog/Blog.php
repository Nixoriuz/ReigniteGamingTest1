<?php
/**
 * @brief		Blog Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		3 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Blog Node
 */
class _Blog extends \IPS\Node\Model implements \IPS\Node\Ratings, \IPS\Content\Embeddable
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'blog_blogs';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'blog_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'author'				=> 'member_id',
		'title'					=> 'name',
		'views'					=> 'num_views',
		'pinned'				=> 'pinned',
		'featured'				=> 'featured',
		'date'					=> 'last_edate',
		'cover_photo'			=> 'cover_photo',
		'cover_photo_offset'	=> 'cover_photo_offset'
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'blogs';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'blogs_blog_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static $modPerm = 'blogs';
	
	/**
	 * @brief	Content Item Class
	 */
	public static $contentItemClass = 'IPS\blog\Entry';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'file-text-o';
	
	/**
	* @brief	[Node] If the node can be "owned", the owner "type" (typically "member" or "group") and the associated database column
	*/
	public static $ownerTypes = array( 'member' => 'member_id', 'group' => array( 'ids' => 'groupblog_ids', 'name' => 'groupblog_name' ) );
	
	/**
	 * @brief	[Node] By mapping appropriate columns (rating_average and/or rating_total + rating_hits) allows to cache rating values
	 */
	public static $ratingColumnMap	= array(
			'rating_average'	=> 'rating_average',
			'rating_total'		=> 'rating_total',
			'rating_hits'		=> 'rating_count',
	);
	
	/**
	 * @brief	Cover Photo Storage Extension
	 */
	public static $coverPhotoStorageExtension = 'blog_Blogs';
	
	/**
	 * Get template for content tables
	 *
	 * @return	callable
	 */
	public static function contentTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'browse', 'blog' ), 'rows' );
	}
	
	/**
	 * Can create blog?
	 *
	 * @param	\IPS\Member|NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		if ( $member->member_id and $member->group['g_blog_allowlocal'] )
		{
			if ( $member->group['g_blog_maxblogs'] )
			{
				return ( \IPS\Db::i()->select( 'COUNT(*)', 'blog_blogs', array( 'blog_member_id=?', $member->member_id ) )->first() < $member->group['g_blog_maxblogs'] );
			}
			
			return TRUE;
		}
		
		return FALSE;
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
		if ( $this->social_group )
		{
			if ( !\IPS\Member::loggedIn()->member_id )
			{
				return FALSE;
			}
			
			if ( \IPS\Member::loggedIn()->member_id !== $this->member_id )
			{
				try
				{
					\IPS\Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=? AND member_id=?', $this->social_group, \IPS\Member::loggedIn()->member_id ) )->first();
				}
				catch ( \UnderflowException $e )
				{
					return FALSE;
				}
			}
		}
		
		if ( $permission === 'add' )
		{
			if ( !\IPS\Member::loggedIn()->member_id )
			{
				return FALSE;
			}
			elseif ( \IPS\Member::loggedIn()->member_id === $this->member_id )
			{
				return TRUE;
			}
			else
			{
				if ( $this->groupblog_ids )
				{
					return \IPS\Member::loggedIn()->inGroup( explode( ',', $this->groupblog_ids ) );
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		return parent::can( $permission, $member );
	}
	
	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		$return = parent::searchIndexPermissions();
		
		if ( $this->social_group )
		{
			$return = ( $return === '*' ) ? array() : explode( ',', $return );
			
			if ( $this->member_id )
			{
				$return[] = "m{$this->member_id}";
			}
			$return[] = "s{$this->social_group}";
			
			$return = implode( ',', array_unique( $return ) );
		}
		
		return $return;
	}
	
	/**
	 * Additional WHERE clauses for Follow view
	 *
	 * @return	array
	 */
	public static function followWhere()
	{
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$where = array( array( '( blog_blogs.blog_social_group IS NULL OR blog_blogs.blog_member_id=' . \IPS\Member::loggedIn()->member_id . ' OR ( ' . \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', \IPS\Member::loggedIn() ) . ' ) )' ) );
		}
		else
		{
			$where = array( \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', \IPS\Member::loggedIn() ) );
		}
		
		return $where;
	}
		
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		foreach ( \IPS\Db::i()->select( 'data', 'core_queue', array( 'app=? AND `key`=?', 'core', 'DeleteOrMoveContent' ) ) as $row )
		{
			$data = json_decode( $row, TRUE );
			if ( $data['class'] === get_class( $this ) and $data['id'] == $this->_id )
			{
				return FALSE;
			}
		}
		
		return static::restrictionCheck( 'delete' ) or ( $this->member_id === \IPS\Member::loggedIn()->member_id and \IPS\Member::loggedIn()->group['g_blog_allowdelete'] );
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form, $public=FALSE )
	{
		if( $public )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'blog_name', $this->id ? $this->_title : NULL, TRUE ) );
			$form->add( new \IPS\Helpers\Form\Editor( 'blog_desc', $this->id ? $this->description : NULL, FALSE, array( 'app' => 'blog', 'key' => 'Blogs', 'autoSaveKey' => ( $this->id ? "blogs-blog-{$this->id}" : "blogs-new-blog" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'blog_desc_placeholder' ) ) );
		}
		else
		{
			$form->addHeader( 'blog_settings' );
			$form->add( new \IPS\Helpers\Form\Translatable( 'blog_name', NULL, TRUE, array( 'app' => 'blog', 'key' => ( $this->id ? "blogs_blog_{$this->id}" : NULL ) ) ) );
			$form->add( new \IPS\Helpers\Form\Translatable( 'blog_desc', NULL, FALSE, array( 'app' => 'blog', 'key' => ( $this->id ? "blogs_blog_{$this->id}_desc" : NULL ), 'editor' => array( 'app' => 'blog', 'key' => 'Blogs', 'autoSaveKey' => ( $this->id ? "blogs-blog-{$this->id}" : "blogs-new-blog" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'blog_desc_placeholder' ) ) ) );
		
			$groups = array();
			foreach ( \IPS\Member\Group::groups() as $k => $v )
			{
				$groups[ $k ] = $v->name;
			}
			
			$type = 'member';
			if ( $this->id )
			{
				if ( $this->groupblog_ids )
				{
					$type = 'group';
				}
			}
			
			$id = $this->id ?: 'new';
	
			$form->add( new \IPS\Helpers\Form\Radio( 'blog_type', $type, TRUE, array(
					'options' => array(
							'member' 	=> 'blog_type_normal',
							'group' 	=> 'blog_type_group'
					),
					'toggles'	=> array(
							'member'	=> array( 'blog_member_id' ),
							'group'		=> array( 'blog_groupblog_ids', 'blog_groupblog_name' )
					)
			) ) );
			
			$form->add( new \IPS\Helpers\Form\Member( 'blog_member_id', $this->member_id ? \IPS\Member::load( $this->member_id ) : NULL, FALSE, array(), function( $member ) use ( $form )
			{
				if ( \IPS\Request::i()->blog_type === 'member' )
				{
					if( !is_object( $member ) or !$member->member_id )
					{
						throw new \InvalidArgumentException( 'no_blog_author_selected' );
					}
				}
			},
			NULL, NULL, 'blog_member_id' ) );
	
			$form->add( new \IPS\Helpers\Form\Select( 'blog_groupblog_ids', $this->id ? explode( ',', $this->groupblog_ids ) : array(), FALSE, array( 'options' => $groups, 'multiple' => TRUE ), NULL, NULL, NULL, 'blog_groupblog_ids' ) );
			$form->add( new \IPS\Helpers\Form\Translatable( 'blog_groupblog_name', NULL, FALSE, array( 'app' => 'blog', 'key' => ( $this->id ? "blogs_groupblog_name_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'blog_groupblog_name' ) );				
		}

		if ( \IPS\Member::loggedIn()->group['g_blog_allowprivate'] )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'blog_privacy', $this->social_group ? 'private' : 'open', FALSE, array(
				'options' => array(
					'open' 		=> 'blog_privacy_open',
					'private' 	=> 'blog_privacy_private'
				),
				'toggles'	=> array(
					'private'		=> array( 'blog_social_group' )
				)
			) ) );
			$form->add( new \IPS\Helpers\Form\SocialGroup( 'blog_social_group', $this->social_group, NULL, array( 'owner' => $this->owner() ), NULL, NULL, NULL, 'blog_social_group' ) );
		}
		
		if( \IPS\Settings::i()->blog_allow_rss )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'blog_enable_rss', $this->id ? $this->settings['allowrss'] : TRUE ) );
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		$this->member_id = 0;
		if( isset( $values['blog_type'] ) and $values['blog_type'] == 'member' and isset( $values['blog_member_id'] ) and is_object( $values['blog_member_id'] ) )
		{
			$values['blog_member_id']->create_menu = NULL;
			$values['blog_member_id']->save();
			$values['blog_member_id'] = $values['blog_member_id']->member_id;
			$this->member_id = $values['blog_member_id'];
		}
		else if ( !isset( $values['blog_type'] ) )
		{
			$values['blog_member_id'] = \IPS\Member::loggedIn()->member_id;
			\IPS\Member::loggedIn()->create_menu = NULL;
			\IPS\Member::loggedIn()->save();
			$this->member_id = $values['blog_member_id'];
		}
		
		if( isset( $values['blog_type'] ) )
		{
			unset( $values['blog_type'] );
		}
		
		$this->massUpdateIndex = FALSE;
		if ( isset( $values['blog_privacy'] ) and $values['blog_privacy'] === 'private' )
		{
			if ( $this->id and !$this->social_group )
			{
				$this->massUpdateIndex = TRUE;
			}
		}
		else
		{
			if ( $this->id and $this->social_group )
			{
				$this->massUpdateIndex = TRUE;

				\IPS\Db::i()->delete( 'core_sys_social_groups', array( 'group_id=?', $this->social_group ) );
				\IPS\Db::i()->delete( 'core_sys_social_group_members', array( 'group_id=?', $this->social_group ) );
			}
			
			$values['blog_social_group'] = NULL;
		}

		if( isset($values['blog_privacy'] ) )
		{
			unset( $values['blog_privacy'] );
		}

		if ( !$this->id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'blogs-new-blog', $this->id, NULL, 'description', TRUE );
		}

		foreach ( array( 'blog_name' => "blogs_blog_{$this->id}", 'blog_desc' => "blogs_blog_{$this->id}_desc", 'blog_groupblog_name' => "blogs_groupblog_name_{$this->id}" ) as $fieldKey => $langKey )
		{
			if ( isset( $values[ $fieldKey ] ) )
			{
				\IPS\Lang::saveCustom( 'blog', $langKey, $values[ $fieldKey ] );
	
				if ( $fieldKey === 'blog_name' )
				{
					$values['seo_name'] = \IPS\Http\Url::seoTitle( ( is_array( $values[ $fieldKey ] ) ) ? $values[ $fieldKey ][ \IPS\Lang::defaultLanguage() ] : $values[ $fieldKey ] );
				}
	
				unset( $values[ $fieldKey ] );
			}
		}

		if( isset( $values['blog_enable_rss'] ) )
		{
			$values['settings'] =  array( 'allowrss' => $values['blog_enable_rss'] );
			unset( $values['blog_enable_rss'] );
		}

		/* Send to parent */
		if( isset( $values['blog_member_id'] ) )
		{
			unset( $values['blog_member_id'] );
		}

		return $values;
	}
	
	/**
	 * @brief	Mass update search index after changes
	 */
	protected $massUpdateIndex	= FALSE;

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		/* Update index? */
		if ( $this->massUpdateIndex )
		{
			\IPS\Content\Search\Index::i()->massUpdate( 'IPS\blog\Entry', $this->id, NULL, $this->searchIndexPermissions() );
		}
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_seo_name()
	{
		if( !$this->_data['seo_name'] )
		{
			$this->seo_name	= \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'blogs_blog_' . $this->id ) );
			$this->save();
		}

		return $this->_data['seo_name'] ?: \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'blogs_blog_' . $this->id ) );
	}

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			$this->_url = \IPS\Http\Url::internal( "app=blog&module=blogs&controller=view&id={$this->_id}", 'front', 'blogs_blog', array( $this->seo_name ) );
		}

		return $this->_url;
	}
	
	/**
	 * @brief	Cached latest entry
	 */
	protected $latestEntry = NULL;

	/**
	 * Get latest entry
	 *
	 * @return	\IPS\blog\Entry|NULL
	 */
	public function latestEntry()
	{
		if( $this->latestEntry !== NULL )
		{
			return $this->latestEntry;
		}

		try
		{
			/* @note entry_hidden is flipped to map to "approved" */
			$this->latestEntry = \IPS\blog\Entry::constructFromData( \IPS\Db::i()->select( '*', 'blog_entries', array( 'entry_blog_id=? AND entry_is_future_entry=0 AND entry_hidden=1 AND entry_status!=?', $this->_id, 'draft' ), 'entry_date DESC', 1 )->first() );
			return $this->latestEntry;
		}
		catch ( \UnderflowException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Contributors
	 *
	 * @return	array
	 */
	public function contributors()
	{
		$contributors = array();
		
		try 
		{
			$select = \IPS\Db::i()->select(
					"core_members.*, count( blog_entries.entry_id ) as contributions",
					'blog_entries',
					array( "entry_blog_id=?", $this->id ),
					"contributions DESC",
					NULL,
					array( 'member_id' )
			)->join(
					'core_members',
					"blog_entries.entry_author_id=core_members.member_id"
			);
			
			/* Get em! */
			foreach( $select as $row )
			{
				$contributors[] = array ( 'member' => \IPS\Member::constructFromData( $row ), 'contributions' => $row['contributions'] );
			}
		}
		catch ( \UnderflowException $e ) {}

		return $contributors;
	}
	
	/**
	 * Retrieve recent entries
	 *
	 * @return	\IPS\Patterns\ActiveRecordIterator
	 */
	public function get__recentEntries()
	{
		return \IPS\blog\Entry::getItemsWithPermission( array( array( 'entry_blog_id=? AND entry_is_future_entry=0 AND entry_status!=?', $this->id, 'draft' ) ), NULL, 5 );
	}
	
	/**
	 * [Node] Get number of content items
	 *
	 * @return	int
	 */
	protected function get__items()
	{
		return $this->count_entries;
	}
	
	/**
	 * [Node] Get number of content comments
	 *
	 * @return	int
	 */
	protected function get__comments()
	{
		return $this->count_comments;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int
	 */
	protected function get__unnapprovedItems()
	{
		return $this->count_entries_hidden;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return	int
	 */
	protected function get__unapprovedComments()
	{
		return $this->count_comments_hidden;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	void
	 */
	protected function set__items( $val )
	{
		$this->count_entries = (int) $val;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__comments( $val )
	{
		$this->count_comments = (int) $val;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__unapprovedItems( $val )
	{
		$this->count_entries_hidden = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @param	int	$val	Unapproved Comments
	 * @return	void
	 */
	protected function set__unapprovedComments( $val )
	{
		$this->count_comments_hidden = $val;
	}
	
	/**
	 * [Node] Get number of future publishing items
	 *
	 * @return	int
	 */
	protected function get__futureItems()
	{
		return $this->count_entries_future;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__futureItems( $val )
	{
		$this->count_entries_future = $val;
	}
	
	/**
	 * Returns the title
	 *
	 * @return string
	 */
	protected function get__title()
	{
		return htmlentities( \IPS\Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id ), ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );
	}

	/**
	 * Get settings
	 *
	 * @return	array
	 */
	public function get_settings()
	{
		$settings = json_decode( $this->_data['settings'], TRUE );

		return $settings;
	}
	
	/**
	 * Set settings
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function set_settings( $values )
	{
		$this->_data['settings'] = json_encode( $values );
	}
	
	/**
	 * Ping Ping-o-matic
	 *
	 * @return	void
	 */
	public function ping()
	{		
		$xml = \IPS\Xml\SimpleXML::create('methodCall');
	
		$methodName = $xml->addChild( 'methodName', 'weblogUpdates.ping' );
		$params = $xml->addChild( 'params' );
 		$params->addChild( 'param' )->addChild( 'value', $this->_title );
 		$params->addChild( 'param' )->addChild( 'value', $this->url() );
		
		try
		{
	 		\IPS\Http\Url::external( 'http://rpc.pingomatic.com/RPC2' )
			->request()
			->setHeaders( array( 'Content-Type' => 'text/xml', 'User-Agent' => "InvisionPowerServices/" . \IPS\Application::load('core')->long_version ) )
			->post( $xml->asXML() );
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::i( LOG_ERR )->write( $e->getMessage() );
		}
	}

	/**
	 * Get template for node tables
	 *
	 * @return	callable
	 */
	public static function nodeTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'browse', 'blog' ), 'rows' );
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Db::i()->delete( 'blog_rss_import', array( "rss_blog_id=?", $this->_id ) );

		foreach ( array( 'blog_groupblog_name' => "blogs_groupblog_name_{$this->id}" ) as $fieldKey => $langKey )
		{
			\IPS\Lang::deleteCustom( 'blog', $langKey );
		}
		
		$member = \IPS\Member::load( $this->member_id );
		$member->create_menu = NULL;
		$member->save();

		\IPS\File::unclaimAttachments( 'blog_Blogs', $this->id );

		return parent::delete();
	}
	
	/**
	 * Cover Photo
	 *
	 * @return	\IPS\Helpers\CoverPhoto
	 */
	public function coverPhoto()
	{
		$photo = parent::coverPhoto();
		$photo->overlay = \IPS\Theme::i()->getTemplate( 'view', 'blog' )->coverPhotoOverlay( $this );
		return $photo;
	}
	
	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( $params )
	{
		return \IPS\Theme::i()->getTemplate( 'global', 'blog' )->embedBlogs( $this, $this->url()->setQueryString( $params ) );
	}

	/**
	 * Set last comment
	 *
	 * @param	\IPS\Content\Comment	$comment	The latest comment or NULL to work it out
	 * @return	void
	 */
	public function setLastComment( \IPS\Content\Comment $comment=NULL )
	{
		if ( $comment === NULL )
		{
			try
			{
				/* @note entry_hidden is flipped to map to "approved" */
				$entry	= \IPS\blog\Entry::constructFromData( \IPS\Db::i()->select( '*', 'blog_entries', array( 'entry_blog_id=? AND entry_status=? AND entry_hidden=1 and entry_is_future_entry=0', $this->id, 'published' ), 'entry_date DESC', 1 )->first() );
			}
			catch ( \UnderflowException $e )
			{
				$this->last_edate		= 0;
				return;
			}
		}
		else
		{
			$entry = $comment->item();
		}

		$this->last_edate		= $entry->mapped('date');
	}
}
<?php
/**
 * @brief		Entry Model
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
 * Entry Model
 */
class _Entry extends \IPS\Content\Item implements
	\IPS\Content\Pinnable, \IPS\Content\Lockable, \IPS\Content\Hideable, \IPS\Content\Featurable,
	\IPS\Content\Tags,
	\IPS\Content\Followable,
	\IPS\Content\Shareable,
	\IPS\Content\ReportCenter,
	\IPS\Content\ReadMarkers,
	\IPS\Content\Views,
	\IPS\Content\Polls,
	\IPS\Content\Ratings,
	\IPS\Content\EditHistory,
	\IPS\Content\Reputation,
	\IPS\Content\Searchable,
	\IPS\Content\Embeddable,
	\IPS\Content\FuturePublishing
{	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	Application
	 */
	public static $application = 'blog';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'blogs';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'blog_entries';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'entry_';
		
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'author'				=> 'author_id',
		'author_name'			=> 'author_name',
		'content'				=> 'content',
		'container'				=> 'blog_id',
		'date'					=> 'date',
		'updated'				=> 'last_update',
		'title'					=> 'name',
		'num_comments'			=> 'num_comments',
		'unapproved_comments'	=> 'queued_comments',
		'hidden_comments'		=> 'hidden_comments',
		'last_comment_by'		=> 'last_comment_mid',
		'last_comment'			=> 'last_update',	// Same as updated above
		'views'					=> 'views',
		'approved'				=> 'hidden',
		'pinned'				=> 'pinned',
		'poll'					=> 'poll_state',
		'featured'				=> 'featured',
		'ip_address'			=> 'ip_address',
		'locked'				=> 'locked',
		'cover_photo'			=> 'cover_photo',
		'cover_photo_offset'	=> 'cover_offset',
		'is_future_entry'		=> 'is_future_entry',
        'future_date'           => 'publish_date'
	);
	
	/**
	 * @brief	Title
	*/
	public static $title = 'blog_entry';
	
	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = 'IPS\blog\Blog';
	
	/**
	 * @brief	[Content\Item]	Comment Class
	 */
	public static $commentClass = 'IPS\blog\Entry\Comment';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = FALSE;
	
	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static $formLangPrefix = 'blog_entry_';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'file-text-o';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
			'view' 				=> 'view',
			'read'				=> 2,
			'add'				=> 3,
			'reply'				=> 4,
	);
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'entry_id';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'blog-entry';
	
	/**
	 * Set the title
	 *
	 * @param	string	$title	Title
	 * @return	void
	 */
	public function set_name( $name )
	{
		$this->_data['name'] = $name;
		$this->_data['name_seo'] = \IPS\Http\Url::seoTitle( $name );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_seo()
	{
		if( !$this->_data['name_seo'] )
		{
			$this->name_seo	= \IPS\Http\Url::seoTitle( $this->name );
			$this->save();
		}

		return $this->_data['name_seo'] ?: \IPS\Http\Url::seoTitle( $this->name );
	}

	/**
	 * Get the album HTML, if there is one associated
	 *
	 * @return	string
	 */
	public function get__album()
	{
		if( \IPS\Application::appIsEnabled( 'gallery' ) AND $this->gallery_album )
		{
			try
			{
				$album = \IPS\gallery\Album::loadAndCheckPerms( $this->gallery_album );
	
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'gallery.css', 'gallery', 'front' ) );
	
				if ( \IPS\Theme::i()->settings['responsive'] )
				{
					\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'gallery_responsive.css', 'gallery', 'front' ) );
				}
	
				return \IPS\Theme::i()->getTemplate( 'browse', 'gallery', 'front' )->miniAlbum( $album );
			}
			catch( \OutOfRangeException $e ){}
			catch( \UnderflowException $e ){}
		}
	
		return '';
	}
	
	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=blog&module=blogs&controller=entry&id={$this->id}", 'front', 'blog_entry', $this->name_seo );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/**
	 * Can view this entry
	 *
	 * @param	\IPS\Member|NULL	$member		The member or NULL for currently logged in member.
	 * @return	bool
	 */
	public function canView( $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		$return = parent::canView( $member );

		if ( $this->status == 'draft' AND !static::canViewHiddenItems( $member, $this->container() ) AND !in_array( $this->container()->id, array_keys( \IPS\blog\Blog::loadByOwner( $member ) ) ) )
		{
			$return = FALSE;
		}
		
		/* Is this a future publish entry and we are the owner of the blog? */
		if ( $this->status == 'draft' AND $this->is_future_entry == 1 AND in_array( $this->container()->id, array_keys( \IPS\blog\Blog::loadByOwner( $member ) ) ) )
		{
			$return = TRUE;
		}

		/* Private blog */
		if( $this->container()->social_group != 0 AND $this->container()->owner() != $member )
		{
			/* This will throw an exception of the row does not exist */
			try
			{
				$member	= \IPS\Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=? AND member_id=?', $this->container()->social_group, $member->member_id ) )->first();
			}
			catch( \UnderflowException $e )
			{
				return FALSE;
			}
		}
		
		return $return;
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string|NULL	$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param	bool|NULL	$includeHiddenItems	Include hidden files? Boolean or NULL to detect if currently logged member has permission
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly			If true will return the count
	 * @param	array|null	$joins				Additional arbitrary joins for the query
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param	bool		$joinTags			If true, will join the tags table
	 * @param	bool		$joinAuthor			If true, will join the members table for the author
	 * @param	bool		$joinLastCommenter	If true, will join the members table for the last commenter
	 * @return	\IPS\Patterns\ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=NULL, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL, $skipPermission=FALSE, $joinTags=TRUE, $joinAuthor=TRUE, $joinLastCommenter=TRUE )
	{
		if ( in_array( $permissionKey, array( 'view', 'read' ) ) )
		{
			$joinContainer = TRUE;
			$member = $member ?: \IPS\Member::loggedIn();
            if ( $member->member_id )
            {
                $where[] = array( '( blog_blogs.blog_member_id=' . $member->member_id . ' OR ( ' . \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', $member ) . ' ) OR blog_blogs.blog_social_group IS NULL )' );
            }
            else
            {
                $where[] = array( "(" . \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', $member ) . " OR blog_blogs.blog_social_group IS NULL )" );
            }
		}
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter );
	}
	
	/**
	 * Additional WHERE clauses for New Content view
	 *
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	array		$joins				Other joins
	 * @return	array
	 */
	public static function vncWhere( &$joinContainer, &$joins )
	{
		$joinContainer = TRUE;
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$where = array( array( '( blog_blogs.blog_social_group IS NULL OR blog_blogs.blog_member_id=' . \IPS\Member::loggedIn()->member_id . ' OR ( ' . \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', \IPS\Member::loggedIn() ) . ' ) )' ) );
		}
		else
		{
			$where = array( \IPS\Content::socialGroupGetItemsWithPermissionWhere( 'blog_blogs.blog_social_group', \IPS\Member::loggedIn() ) );
		}

		return array_merge( parent::vncWhere( $joinContainer, $joins ), $where );
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	int						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{
		$return = parent::formElements( $item, $container );
		$return['entry'] = new \IPS\Helpers\Form\Editor( 'blog_entry_content', $item ? $item->content : NULL, TRUE, array( 'app' => 'blog', 'key' => 'Entries', 'autoSaveKey' => 'blog-entry-' . $container->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id ) ) ) );

		/* Gallery album association */
		if( \IPS\Application::appIsEnabled( 'gallery' ) )
		{
			$return['album']	= new \IPS\Helpers\Form\Node( 'entry_gallery_album', ( $item AND $item->gallery_album ) ? $item->gallery_album : NULL, FALSE, array(
					'url'					=> \IPS\Http\Url::internal( 'app=blog&module=blogs&controller=submit', 'front', 'blog_submit' ),
					'class'					=> 'IPS\gallery\Album',
					'permissionCheck'		=> 'add',
			) );
		}
		
		$return['publish'] = new \IPS\Helpers\Form\YesNo( 'blog_entry_publish', $item ? $item->status : TRUE, FALSE, array( 'togglesOn' => array( 'blog_entry_date' ) ) );
		
		/* Publish date needs to go near the bottom */
		$date = NULL;
		if ( isset( $return['date'] ) )
		{
			$date = $return['date'];
			unset( $return['date'] );
			
			$return['date'] = $date;
		}
		
		/* Poll always needs to go on the end */
		$poll = NULL;
		if ( isset( $return['poll'] ) )
		{
			$poll = $return['poll'];
			unset( $return['poll'] );
			
			$return['poll'] = $poll;
		}
		
		
		return $return;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		parent::processForm( $values );
		
		if ( !$this->_new )
		{
			$oldContent = $this->content;
		}
		$this->content	= $values['blog_entry_content'];
		if ( !$this->_new )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}
		
		$this->status = $values['blog_entry_publish'] ? 'published' : 'draft';
		
		if ( isset( $values['blog_entry_date'] ) )
		{
			$this->date = ( $values['blog_entry_date'] AND $values['blog_entry_publish'] ) ? $values['blog_entry_date']->getTimestamp() : time();
		}
		
		/* Gallery album association */
		if( \IPS\Application::appIsEnabled( 'gallery' ) AND $values['entry_gallery_album'] instanceof \IPS\gallery\Album )
		{
			$this->gallery_album = $values['entry_gallery_album']->_id;
		}
		else
		{
			$this->gallery_album = NULL;
		}
		
		if ( $this->date > time() )
		{
			$this->status = 'draft';
		}
		
		/* Ping */
		$this->container()->ping();
	}
	
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		parent::canCreate( $member, $container, $showError );
		
		$return = TRUE;

		$blogs = \IPS\blog\Blog::loadByOwner( $member );

		if ( $container )
		{
			if ( !in_array( $container->id, array_keys( $blogs ) ) )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
			
			if ( $container->disabled )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
		}
		else
		{
			if( !count( $blogs ) )
			{
				$return = FALSE;
				$error = 'no_module_permission';
			}
		}
				
		/* Return */
		if ( $showError and !$return )
		{
			\IPS\Output::i()->error( $error, '1B203/1', 403, '' );
		}
		
		return $return;
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( $comment, $values )
	{
		parent::processAfterCreate( $comment, $values );

		\IPS\File::claimAttachments( 'blog-entry-' . $this->container()->id, $this->id );

		if ( $this->status == 'published' )
		{
			$blog						= $this->container();
			$lastUpdateColumn			= $blog::$databaseColumnMap['date'];
			$blog->$lastUpdateColumn	= time();
			$blog->save();
		}
	}
	
	/**
	 * Syncing to run when publishing something previously pending publishing
	 *
	 * @return	void
	 */
	public function onPublish()
	{
		$this->status = 'published';
		$this->save();
		
		parent::onPublish();
	}
	
	/**
	 * Syncing to run when unpublishing an item (making it a future dated entry when it was already published)
	 *
	 * @return	void
	 */
	public function onUnpublish()
	{
		$this->status = 'draft';
		$this->save();
		
		parent::onUnpublish();
	}
	
	/**
	 * Can comment?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canComment( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return parent::canComment( $member ) and $member->group['g_blog_allowcomment'];
	}
	
	/**
	 * Can set items to be published in the future?
	 *
	 * @param	\IPS\Member|NULL	    $member	        The member to check for (NULL for currently logged in member)
	 * @param   \IPS\Node\Model|null    $container      Container
	 * @return	bool
	 */
	public static function canFuturePublish( $member=NULL, \IPS\Node\Model $container = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return (boolean) $member->member_id > 0;
	}
	
	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	\IPS\Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	\IPS\Node\Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( $type, \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		$result = parent::modPermission( $type, $member, $container );
		
		if ( $result !== TRUE )
		{
			if ( in_array( $type, array( 'edit', 'delete', 'lock', 'unlock' ) ) and $container and $container->member_id === $member->member_id )
			{
				$result = $member->group['g_blog_allowownmod'];
			}
		}
		
		return $result;
	}

	/**
	 * Get template for content tables
	 *
	 * @return	callable
	 */
	public static function contentTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'global', 'blog' ), 'rows' );
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
        if ( $this->status == 'draft' )
        {
            return '0';
        }
        
        return parent::searchIndexPermissions();
    }
}
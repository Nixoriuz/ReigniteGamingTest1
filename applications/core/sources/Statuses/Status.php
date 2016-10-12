<?php
/**
 * @brief		Status Update Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Statuses;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Status Update Model
 */
class _Status extends \IPS\Content\Item implements \IPS\Content\ReportCenter, \IPS\Content\Reputation, \IPS\Content\Lockable, \IPS\Content\Hideable
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_member_status_updates';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'status_';
	
	/**
	 * @brief	[Content\Comment]	Icon
	 */
	public static $icon = 'comment-o';
	
	/**
	 * @brief	Number of comments per page
	 */
	public static $commentsPerPage = 3;

	/**
	 * @brief	Number of comments per page when requesting previous replies (ajax)
	 */
	public static $commentsPerPageAjax = 25;

	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		\IPS\Widget::deleteCaches( 'recentStatusUpdates', 'core' );	
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_member_status_replies', array( 'reply_status_id=?', $this->id ) ), 'IPS\core\Statuses\Reply' ) AS $reply )
		{
			$reply->delete();
		}
		
		parent::delete();
		\IPS\Widget::deleteCaches( 'recentStatusUpdates', 'core' );	
	}
	
	/* !\IPS\Content\Item */

	/**
	 * @brief	Title
	 */
	public static $title = 'member_status';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'date'			=> 'date',
		'author'		=> 'author_id',
		'num_comments'	=> 'replies',
		'locked'		=> 'is_locked',
		'approved'		=> 'approved',
		'ip_address'	=> 'author_ip',
		'content'		=> 'content',
		'title'			=> 'content',
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'members';
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static $formLangPrefix = 'status_';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\core\Statuses\Reply';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = FALSE;
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'status_id';
	
		/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'status_status';
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function get_title()
	{
		return strip_tags( $this->mapped('content') );
	}
	
	/**
	 * Get mapped value
	 *
	 * @param	string	$key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( $key )
	{
		if ( $key === 'title' )
		{
			return $this->title;
		}
		
		return parent::mapped( $key );
	}

	/**
	 * Can a given member create a status update?
	 *
	 * @param \IPS\Member $member
	 * @return bool
	 */
	public static function canCreateFromCreateMenu( \IPS\Member $member = null)
	{
		if ( !$member )
		{
			$member = \IPS\Member::loggedIn();
		}

		/* Can we access the module? */
		if ( !parent::canCreate( $member, NULL, FALSE ) )
		{
			return FALSE;
		}

		/* We have to be logged in */
		if ( !$member->member_id )
		{
			return FALSE;
		}

		/* Disabled at the group level? */
		if( \IPS\Member::loggedIn()->group['gbw_no_status_update'] )
		{
			return FALSE;
		}

		/* Or at the profile level? */
		if( \IPS\Member::loggedIn()->members_bitoptions['bw_no_status_update'] )
		{
			return FALSE;
		}

		if ( !$member->pp_setting_count_comments or !\IPS\Settings::i()->profile_comments )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		$profileOwner = isset( \IPS\Request::i()->id ) ? \IPS\Member::load( \IPS\Request::i()->id ) : \IPS\Member::loggedIn();
		
		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{			
			return FALSE;
		}
		
		/* We have to be logged in */
		if ( !$member->member_id )
		{
			return FALSE;
		}
		
		/* Is the user being ignored */
		if ( $profileOwner->isIgnoring( $member, 'messages' ) )
		{
			return FALSE;
		}

		/* Disabled at the group level? */
		if( \IPS\Member::loggedIn()->group['gbw_no_status_update'] )
		{
			return FALSE;
		}

		/* Or at the profile level? */
		if( \IPS\Member::loggedIn()->members_bitoptions['bw_no_status_update'] )
		{
			return FALSE;
		}

		if ( !$profileOwner->pp_setting_count_comments and $member->member_id != \IPS\Request::i()->id )
		{	
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item				The current item if editing or NULL if creating
	 * @param	\IPS\Node\Model|NULL	$container			Container (e.g. forum), if appropriate
	 * @param	bool 					$fromCreateMenu		false to deactivate the minimize feature
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL , $fromCreateMenu=FALSE)
	{
		$formElements = parent::formElements( $item, $container );
		
		unset( $formElements['title'] );

		if ( $fromCreateMenu )
		{
			$minimize = NULL;
		}
		else
		{
			$member = isset( \IPS\Request::i()->id ) ? \IPS\Member::load( \IPS\Request::i()->id ) : \IPS\Member::loggedIn();

			$minimize = ( $member->member_id != \IPS\Member::loggedIn()->member_id ) ?
				\IPS\Member::loggedIn()->language()->addToStack( static::$formLangPrefix . '_update_placeholder_other', FALSE, array( 'sprintf' => array( $member->name ) ) ) :
				static::$formLangPrefix . '_update_placeholder';
		}

		$formElements['status_content'] = new \IPS\Helpers\Form\Editor( static::$formLangPrefix . 'content' . ( $fromCreateMenu ? '_ajax' : '' ), ( $item ) ? $item->content : NULL, TRUE, array(
				'app'			=> static::$application,
				'key'			=> 'Members',
				'autoSaveKey' 	=> 'status',
				'minimize'		=> $minimize,
			), '\IPS\Helpers\Form::floodCheck' );
				
		return $formElements;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array					$values				Values from form
	 * @param	\IPS\Node\Model|NULL	$container			Container (e.g. forum), if appropriate
	 * @param	bool					$sendNotification	TRUE to automatically send new content notifications (useful for items that may be uploaded in bulk)
	 * @return	\IPS\Content\Item
	 */
	public static function createFromForm( $values, \IPS\Node\Model $container = NULL, $sendNotification = TRUE )
	{
		/* Create */
		$status = parent::createFromForm( $values, $container, $sendNotification );
		\IPS\File::claimAttachments( 'status', $status->id );

		/* Sync */
		if ( $status->member_id == $status->author_id and $syncSettings = json_decode( \IPS\Member::loggedIn()->profilesync, TRUE ) )
		{
			foreach ( $syncSettings as $provider => $settings )
			{
				if ( isset( $settings['status'] ) and $settings['status'] === 'export' )
				{
					$class= 'IPS\core\ProfileSync\\' . ucfirst( $provider );
					$sync = new $class( \IPS\Member::loggedIn() );
					if ( method_exists( $sync, 'exportStatus' ) )
					{
						try
						{
							$sync->exportStatus( $status );
						}
						catch ( \Exception $e ) { }
					}
				}
			}
		}
		
		/* Return */
		return $status;
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
	
		$this->member_id = isset( \IPS\Request::i()->id ) ? \IPS\Request::i()->id : \IPS\Member::loggedIn()->member_id;
		
		if ( !$this->_new )
		{
			$oldContent = $this->content;
		}
		$this->content	= $values['status_content'];
		if ( !$this->_new )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}		
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
			$member = \IPS\Member::load( $this->member_id );
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&status={$this->id}&type=status", 'front', 'profile', array( $member->members_seo_name ) );
		
			if ( $action )
			{
				if ( $action == 'edit' )
				{
					$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', 'editStatus' );
				}
				else
				{
					$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( array( 'do' => $action, 'type' => 'status' ) );
				}

				if ( $action == 'moderate' AND \IPS\Request::i()->controller == 'feed' )
				{
					$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( '_fromFeed', 1 );
				}
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{
		parent::sendNotifications();

		/* Notify when somebody comments on my profile */
		if( $this->author()->member_id != $this->member_id )	
		{
			$notification = new \IPS\Notification( \IPS\Application::load( 'core' ), 'profile_comment', $this, array( $this ) );
			$member = \IPS\Member::load( $this->member_id );
			$notification->recipients->attach( $member );
			
			$notification->send();
		}

		/* Notify when a follower posts a status update */
		if ( $this->author()->member_id == $this->member_id )
		{
			$notification	= new \IPS\Notification( \IPS\Application::load( 'core' ), 'new_status', $this, array( $this ) );
			$followers		= \IPS\Member::load( $this->member_id )->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL );

			if( is_array( $followers ) )
			{
				foreach( $followers AS $follower )
				{
					$notification->recipients->attach( \IPS\Member::load( $follower['follow_member_id'] ) );
				}
			}
			
			$notification->send();
		}
	}
	
	/**
	 * Should new items be moderated?
	 *
	 * @param	\IPS\Member		$member		The member posting
	 * @param	\IPS\Node\Model	$container	The container
	 * @return	bool
	 */
	public static function moderateNewItems( \IPS\Member $member, \IPS\Node\Model $container = NULL )
	{
		if ( $member->moderateNewContent() or \IPS\Settings::i()->profile_comment_approval )
		{
			return TRUE;
		}

		return parent::moderateNewItems( $member, $container );
	}
	
	/**
	 * Should new comments be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewComments( \IPS\Member $member )
	{
		return ( $member->moderateNewContent() or \IPS\Settings::i()->profile_comment_approval );
	}
	
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
	
		/* Profile owner should always be able to delete */
		if ( $member->member_id == $this->member_id )
		{
			return TRUE;
		}
		
		return parent::canDelete( $member );
	}
	
	/**
	 * Get comments to display
	 *
	 * @return	array
	 */
	public function commentsForDisplay()
	{
		/* Init */
		$limit = static::$commentsPerPage;
		$numberOfComments = $this->commentCount();
		
		/* If there is more than 3 comments, we want to display the LAST 3 on page 1, the 3 before that on page 2, etc */
		if ( $numberOfComments >= static::$commentsPerPage )
		{
			/* Work out what page we're looking at, but only if we are actually paginating through comments */
			/* @future we should probably remove the dependancy on \IPS\Request::i()->page here, as the status may be display on a page that isn't necessarily the profile (ex: My Activity) */
			$page = ( isset( \IPS\Request::i()->page ) AND isset( \IPS\Request::i()->status ) ) ? intval( \IPS\Request::i()->page ) : 1;
			if( $page < 1 )
			{
				$page = 1;
			}
			
			/* Start by making the offset to be the $numberOfComments - ( 3 * $page )
				For example, if there's 5 comments, and we're on page 1, the offset will be 2 */
			$offset = $numberOfComments - ( static::$commentsPerPage * $page );
			
			/* However, if we've got to the start, set teh offset to 0 and adjust the limit to get whatever is left */
			if ( $offset < 0 )
			{
				$limit += $offset;
				$offset = 0;
			}
		}
		
		/* If there's less than 3 comments, just display those */
		else
		{
			$offset = 0;
		}
		
		/* Return */
		$return = parent::comments( $limit, $offset );
		
		/* If the limit is 1, comments() returns an object, but we want an array */
		return ( $limit == 1 ) ? array( $return ) : $return;
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	callable
	 */
	public static function contentTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'profile', 'core' ), 'statusContentRows' );
	}

	/**
	 * Get number of comments to show per page
	 *
	 * @return int
	 */
	public static function getCommentsPerPage()
	{
		return static::$commentsPerPage;
	}
}
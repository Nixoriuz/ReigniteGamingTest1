<?php
/**
 * @brief		Status Update Reply Model
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
 * Status Update Reply
 */
class _Reply extends \IPS\Content\Comment implements \IPS\Content\ReportCenter, \IPS\Content\Hideable, \IPS\Content\Reputation
{
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_member_status_replies';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'reply_';
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[Content\Comment]	Title
	 */
	public static $title = 'status_reply';
	
	/**
	 * @brief	[Content\Comment]	Icon
	 */
	public static $icon = 'comment-o';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static $itemClass = 'IPS\core\Statuses\Status';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Content\Comment]	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'			=> 'status_id',
		'date'			=> 'date',
		'content'		=> 'content',
		'author'		=> 'member_id',
		'approved'		=> 'approved',
		'ip_address'	=> 'ip_address',
	);

	/**
	 * @brief	[Content\Comment]	Language prefix for forms
	 */
	public static $formLangPrefix = 'status_';
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'status_reply_id';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'profile', 'core', 'front' ), 'statusReplyContainer' );
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'status_reply';
	
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
		if ( $action === 'find' )
		{
			return $this->item()->url();
		}
		
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$member = \IPS\Member::load( $this->item()->member_id );
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&status={$this->id}", 'front', 'profile', array( $member->members_seo_name ) )->setQueryString( 'type', 'status'  );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( array( 'do' => $action, 'type' => 'reply' ) );
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
		
		/* Notify when somebody replies to status updates I am connected to */
		$notification = new \IPS\Notification( \IPS\Application::load( 'core' ), 'profile_reply', $this, array( $this ) );
		
		foreach ( 
			\IPS\Db::i()->select( 
				'core_members.*', 'core_member_status_replies',
				array( 'reply_status_id=? and reply_member_id !=?', $this->item()->id, $this->author()->member_id ) 
			)->join(
				'core_members',
				'core_members.member_id=core_member_status_replies.reply_member_id'
			) as $member
		)
		{
			$notification->recipients->attach( \IPS\Member::constructFromData( $member ) );
		}
		
		if( $this->author()->member_id != $this->item()->author()->member_id )
		{
			$notification->recipients->attach( $this->item()->author() );
		}
		
		if( $this->author()->member_id != $this->item()->member_id )
		{
			$notification->recipients->attach( \IPS\Member::load( $this->item()->member_id ) );
		}
		
		$notification->send();
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
		if ( $member->member_id == $this->item()->member_id )
		{
			return TRUE;
		}
	
		return parent::canDelete( $member );
	}
}
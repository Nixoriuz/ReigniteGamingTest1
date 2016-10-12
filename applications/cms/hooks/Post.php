//<?php

class cms_hook_Post extends _HOOK_CLASS_
{
	/**
	 * Create comment
	 *
	 * @param	\IPS\Content\Item		$item		The content item just created
	 * @param	string					$comment	The comment
	 * @param	bool					$first		Is the first comment?
	 * @param	string					$guestName	If author is a guest, the name to use
	 * @param	bool|NULL				$incrementPostCount	Increment post count? If NULL, will use static::incrementPostCount()
	 * @param	\IPS\Member|NULL		$member				The author of this comment. If NULL, uses currently logged in member.
	 * @param	\IPS\DateTime|NULL		$time				The time
	 * @return	static
	 */
	public static function create( $item, $comment, $first=FALSE, $guestName=NULL, $incrementPostCount=NULL, $member=NULL, \IPS\DateTime $time=NULL )
	{
		$comment = parent::create( $item, $comment, $first, $guestName, $incrementPostCount, $member, $time );
		
		static::recordSync( $item );
		
		return $comment;
	}
	
	/**
     * Delete Post
     *
     * @return	void
     */
    public function delete()
    {
		parent::delete();
		
		static::recordSync( $this->item() );
	}
	
		/**
	 * Syncing to run when hiding
	 *
	 * @return	void
	 */
	public function onHide()
	{
		parent::onHide();
		
		static::recordSync( $this->item() );
	}
	
	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool	$approving	If true, is being approved for the first time
	 * @return	void
	 */
	public function onUnhide( $approving )
	{
		parent::onUnhide( $approving );
		
		static::recordSync( $this->item() );
	}
	
	/**
	 * Sync up the topic
	 * 
	 * @param	\IPS\forums\Topic	$item		Topic object
	 *
	 * @return void
	 */
	protected static function recordSync( $item )
	{
		$synced = array();
		
		foreach( \IPS\Db::i()->select( '*', 'cms_database_categories', array( 'category_forum_record=? AND category_forum_forum=? AND category_forum_comments=?', 1, $item->forum_id, 1 ) ) as $category )
		{
			try
			{
				$synced[] = $category['category_database_id'];
				$class    = '\IPS\cms\Records' . $category['category_database_id'];
				$class::load( $item->tid, 'record_topicid' )->syncRecordFromTopic( $item );
			}
			catch( \Exception $ex )
			{
			}
		}
		
		foreach( \IPS\Db::i()->select( '*', 'cms_databases', array( 'database_forum_record=? AND database_forum_forum=? AND database_forum_comments=?', 1, $item->forum_id, 1 ) ) as $database )
		{
			try
			{
				if ( ! in_array( $database['database_id'], $synced ) )
				{
					$class = '\IPS\cms\Records' . $database['database_id'];
					$class::load( $item->tid, 'record_topicid' )->syncRecordFromTopic( $item );
				}
			}
			catch( \Exception $ex )
			{
			}
		}
		
		
	}
}
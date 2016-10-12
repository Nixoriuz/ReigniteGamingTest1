//<?php

class cms_hook_Topic extends _HOOK_CLASS_
{
	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	\IPS\Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @return	void
	 * @throws	\OutOfRangeException|\InvalidArgumentException|\RuntimeException
	 */
	public function modAction( $action, \IPS\Member $member = NULL, $reason = NULL )
	{
		if ( $action === 'delete' )
		{
			$databaseId = NULL;
			
			try
			{
				$database = \IPS\Db::i()->select( '*', 'cms_databases', array( array( 'database_forum_forum=? AND database_forum_comments=1', $this->forum_id ) ) )->first();
				$databaseId = $database['database_id'];
			}
			catch( \UnderflowException $ex )
			{
				try
				{
					$category   = \IPS\Db::i()->select( '*', 'cms_database_categories', array( array( 'category_forum_forum=? AND category_forum_comments=1', $this->forum_id ) ) )->first();
					$databaseId = $category['category_database_id'];
				}
				catch( \UnderflowException $ex )
				{
					return parent::modAction( $action, $member, $reason );
				}
			}
			
			/* Still here? */
			$class = '\IPS\cms\Records' . $databaseId;
			
			try
			{
				$class::load( $this->tid, 'record_topicid' );
				
				$database = \IPS\cms\Databases::load( $databaseId );
				\IPS\Member::loggedIn()->language()->words['cms_delete_linked_topic'] = sprintf( \IPS\Member::loggedIn()->language()->get('cms_delete_linked_topic'), $database->recordWord( 1 ) );
				
				\IPS\Output::i()->error( 'cms_delete_linked_topic', '1T281/1', 403, '' );
			}
			catch( \OutOfRangeException $ex )
			{
				/* Not attached to a database record */
				return parent::modAction( $action, $member, $reason );
			}
		}
		
		return parent::modAction( $action, $member, $reason );
	}

}
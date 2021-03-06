<?php
/**
 * @brief		Background Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Forums
 * @since		18 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class _DeleteLegacyPosts
{
	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$select = \IPS\Db::i()->select( '*', 'forums_posts', 'queued > 2', 'pid ASC', array( 0, 5 ) );
		if ( !count( $select ) )
		{
			throw new \OutOfRangeException;
		}
		
		$done = 0;
		foreach( new \IPS\Patterns\ActiveRecordIterator( $select, 'IPS\forums\Topic\Post' ) as $post )
		{
			$post->delete();
			$done++;
		}
		
		return $offset + $done;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaning task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$count = \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts', 'queued > 2' )->first();
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('queue_deleting_legacy_posts'), 'complete' => 100 / ( $count ?: 1 + $offset ) * $offset );
	}	
}
<?php
/**
 * @brief		Background Task: Rebuild database records
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		13 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database records
 */
class _RebuildRecords
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= 50;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		
		\IPS\Log::i( LOG_DEBUG )->write( "Getting preQueueData for " . $classname, 'rebuildRecords' );

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'cms_custom_database_' . $databaseId )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		return $data;
	}

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
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		
		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \OutOfRangeException;
		}

		$parsed	= 0;
		$class  = '\IPS\cms\Records' . $databaseId;
		
		if ( \IPS\Db::i()->checkForTable( 'cms_custom_database_' . $databaseId ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'cms_custom_database_' . $databaseId, NULL, 'primary_id_field asc', array( $offset, $this->rebuild ) ) as $row )
			{
				$record = $class::constructFromData( $row );
				$record->resetLastComment();
				
				if ( $record->record_image and file_exists( \IPS\ROOT_PATH . '/uploads/' . $record->record_image ) )
				{ 
					$record->record_image = (string) \IPS\File::create( 'content_Records', $record->record_image, file_get_contents( \IPS\ROOT_PATH . '/uploads/' . $record->record_image ) );
					$record->save();
				}
				
				if ( ! $record->record_publish_date )
				{
					$record->record_publish_date = $record->record_saved;
					$record->save();
				}
			
				$parsed++;
			}
		}
		
		return ( $parsed == $this->rebuild ) ? ( $offset + $this->rebuild ) : null;
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
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 18 );
				
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_cms_database_records', FALSE, array( 'sprintf' => array( \IPS\cms\Databases::load( $databaseId )->_title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}
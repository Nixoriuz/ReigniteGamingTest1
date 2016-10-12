<?php
/**
 * @brief		Queue Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		04 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Queue Task
 */
class _queue extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		/* Work out the maximum execution time */
		$timeLeft = 45;
		if ( $phpMaxExecutionTime = ini_get('max_execution_time') and $phpMaxExecutionTime <= $timeLeft )
		{
			$timeLeft = $phpMaxExecutionTime - 2;
		}

		/* Factor in wait_timeout if possible */
		try
		{
			$mysqlTimeout = \IPS\Db::i()->query( "SHOW SESSION VARIABLES LIKE 'wait_timeout'" )->fetch_assoc();
			$mysqlTimeout = $mysqlTimeout['Value'];

			if( $mysqlTimeout <= $timeLeft )
			{
				$timeLeft = $mysqlTimeout - 2;
			}
		}
		catch( \IPS\Db\Exception $e ){}

		$timeTheLastRunTook = 0;
				
		/* Work out the memory limit */
		$memoryLeft = 0;
		$memoryUnlimited = FALSE;
		if ( function_exists( 'memory_get_usage' ) )
		{
			$memory_limit = ini_get('memory_limit');
			if ( $memory_limit == -1 )
			{
				$memoryUnlimited = TRUE;
			}
			else
			{
				if ( preg_match('/^(\d+)(.)$/', $memory_limit, $matches ) )
				{
				    if ( $matches[2] == 'M' )
				    {
				        $memory_limit = $matches[1] * 1024 * 1024;
				    }
				    elseif ( $matches[2] == 'K' )
				    {
				        $memory_limit = $matches[1] * 1024;
				    }
				}
				$memoryLeft = $memory_limit - memory_get_usage( TRUE );
			}
		}
		$memoryTheLastRunTook = 0;
		
		/* Run until we run out of time */
		do
		{				
			/* Try and get a queue item */
			try
			{
				/* Get it */
				$queueData = \IPS\Db::i()->select( '*', 'core_queue', NULL, 'priority ASC, RAND()', 1 )->first();
												
				/* Start a timer */
				$timer = microtime( TRUE );
				$memoryTimer = function_exists( 'memory_get_usage' ) ? memory_get_usage( TRUE ) : 0;
				
				/* Got one, try to run it */
				try
				{
					$extensions = \IPS\Application::load( $queueData['app'] )->extensions( 'core', 'Queue', FALSE );
					if ( !isset( $extensions[ $queueData['key'] ] ) )
					{
						throw new \OutOfRangeException;
					}
					
					$class = new $extensions[ $queueData['key'] ];
					$data  = json_decode( $queueData['data'], TRUE );
					$newOffset = $class->run( $data, $queueData['offset'] );
					
					if ( is_null( $newOffset ) )
					{
						\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) );
					}
					else
					{
						\IPS\Db::i()->update( 'core_queue', array( 'offset' => $newOffset ), array( 'id=?', $queueData['id'] ) );
						
						$newData = json_encode( $data );
						
						/* Did it change?? */
						if ( $newData !== $data )
						{
							\IPS\Db::i()->update( 'core_queue', array( 'data' => $newData ), array( 'id=?', $queueData['id'] ) );
						}
					}
				}
				/* An error in running - delete it */
				catch ( \OutOfRangeException $e )
				{
					\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) ); 
				}
				
				/* Decrease the time left */
				$timeTheLastRunTook = round( ( microtime( TRUE ) - $timer ), 2 );
				$timeLeft -= $timeTheLastRunTook;
				$memoryTheLastRunTook = function_exists( 'memory_get_usage' ) ? ( memory_get_usage( TRUE ) - $memoryTimer ) : 0;
				if ( !$memoryUnlimited )
				{
					$memoryLeft = $memory_limit - memory_get_usage( TRUE );;
				}
			}
			/* If there's no queue items left, disable this task and return */
			catch ( \UnderflowException $e )
			{				
				$this->enabled = FALSE;
				$this->save();
				return;
			}
		}
		while ( $timeLeft > $timeTheLastRunTook and ( $memoryUnlimited or $memoryLeft > $memoryTheLastRunTook ) );
	}
		
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}
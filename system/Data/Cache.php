<?php
/**
 * @brief		Abstract Storage Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		07 May 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Data;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Storage Class
 */
abstract class _Cache extends AbstractData
{
	/**
	 * @brief	Instance
	 */
	protected static $instance;

	/**
	 * @brief	Caches already retrieved this instance
	 */
	protected $cache	= array();
	
	/**
	 * @brief	Log
	 */
	public $log	= array();

	/**
	 * Get instance
	 *
	 * @return	\IPS\Data\Cache
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = 'IPS\Data\Cache\\' . \IPS\CACHE_METHOD;
			
			if ( $classname::supported() )
			{
				static::$instance = new $classname( json_decode( \IPS\CACHE_CONFIG, TRUE ) );
			}
			else
			{
				static::$instance = new \IPS\Data\Cache\None( array() );
			}
		}
		
		return static::$instance;
	}
	
	/**
	 * Store value using cache method if available or falling back to the database
	 *
	 * @param	string			$key		Key
	 * @param	mixed			$value		Value
	 * @param	\IPS\DateTime	$expire		Expiration if using database
	 * @param	bool			$fallback	Use database if no caching method is available?
	 * @return	void
	 */
	public function storeWithExpire( $key, $value, \IPS\DateTime $expire, $fallback=FALSE )
	{
		if ( \IPS\CACHE_METHOD === 'None' )
		{
			if ( $fallback )
			{
				\IPS\Db::i()->replace( 'core_cache', array(
					'cache_key'		=> $key,
					'cache_value'	=> json_encode( $value ),
					'cache_expire'	=> $expire->getTimestamp()
				) );
			}
		}
		else
		{
			$this->$key = array( 'value' => $value, 'expires' => $expire->getTimestamp() );
		}
	}
	
	/**
	 * Get value using cache method if available or falling back to the database
	 *
	 * @param	string	$key	Key
	 * @param	bool	$fallback	Use database if no caching method is available?
	 * @return	mixed
	 * @throws	\OutOfRangeException
	 */
	public function getWithExpire( $key, $fallback=FALSE )
	{
		if ( \IPS\CACHE_METHOD === 'None' )
		{
			if ( $fallback )
			{
				try
				{
					return json_decode( \IPS\Db::i()->select( 'cache_value', 'core_cache', array( 'cache_key=? AND cache_expire>?', $key, time() ) )->first(), TRUE );
				}
				catch ( \UnderflowException $e )
				{
					throw new \OutOfRangeException;
				}
			}
			else
			{
				throw new \OutOfRangeException;
			}
		}
		else
		{
			if ( !isset( $this->$key ) )
			{
				throw new \OutOfRangeException;
			}
			
			$data = $this->$key;
			if( count( $data ) and isset( $data['value'] ) and isset( $data['expires'] ) )
			{
				/* Is it expired? */
				if( $data['expires'] AND time() < $data['expires'] )
				{
					return $data['value'];
				}
				else
				{
					unset( $this->$key );
					throw new \OutOfRangeException;
				}
			}
			else
			{
				unset( $this->$key );
				throw new \OutOfRangeException;
			}
		}
	}
	
}
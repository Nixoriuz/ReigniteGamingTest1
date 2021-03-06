<?php
/**
 * @brief		IP Address Lookup extension
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	
 * @since		30 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup extension
 */
class _DownloadLog
{
	/** 
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	\IPS\Http\Url	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	\IPS\Helpers\Table|int|null
	 */
	public function findByIp( $ip, \IPS\Http\Url $baseUrl = NULL )
	{
		/* Return count */
		if ( $baseUrl === NULL )
		{
			return \IPS\Db::i()->select( 'COUNT(*)', 'downloads_downloads', array( "dip LIKE CONCAT( ?, '%' )", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new \IPS\Helpers\Table\Db( 'downloads_downloads', $baseUrl, array( "dip LIKE CONCAT( ?, '%' )", $ip ) );

		$table->tableTemplate  = array( \IPS\Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( \IPS\Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
		
		$table->include = array( 'dfid', 'dtime', 'dsize', 'dua', 'dmid' );
		$table->sortBy = $table->sortBy ?: 'dtime';
		
		/* Parsers */
		$table->parsers = array(
			'dfid'	=> function( $val )
			{
				try
				{
					$file = \IPS\downloads\File::load( $val );
					return "<a href='" . $file->url() . "' target='_blank'>" . htmlentities( $file->name, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . '</a>';
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('content_deleted');
				}
			},
			'dtime'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			},
			'dsize'	=> function( $val )
			{
				return \IPS\Output\Plugin\Filesize::humanReadableFilesize( $val );
			},
			'dua'	=> function( $val )
			{
				return (string) \IPS\Http\Useragent::parse( $val );
			},
			'dmid'	=> function( $val )
			{
				$member = \IPS\Member::load( $val );
				return \IPS\Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
		);
		
		/* Return */
		return (string) $table;
	}
	
	/**
	 * Find IPs by Member
	 *
	 * @code
	 	return array(
	 		'::1' => array(
	 			'ip'		=> '::1'// string (IP Address)
		 		'count'		=> ...	// int (number of times this member has used this IP)
		 		'first'		=> ... 	// int (timestamp of first use)
		 		'last'		=> ... 	// int (timestamp of most recent use)
		 	),
		 	...
	 	);
	 * @endcode
	 * @param	\IPS\Member	$member	The member
	 * @return	array
	 */
	public function findByMember( $member )
	{
		return \IPS\Db::i()->select( "dip AS ip, COUNT(*) AS count, MIN(dtime) AS first, MAX(dtime) AS last", 'downloads_downloads', array( "dmid=?", $member->member_id ), NULL, NULL, 'dip' )->setKeyField( 'ip' );
	}	
}
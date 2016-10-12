<?php
/**
 * @brief		Search Results
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Results
 */
class _Results extends \ArrayIterator
{	
	/**
	 * @brief	Count
	 */
	protected $countAllRows;
	
	/**
	 * @brief	Has this been initated?
	 */
	protected $initated = FALSE;
		
	/**
	 * Constructor
	 *
	 * @param	array	$results		The results
	 * @param	int		$countAllRows	Count for all rows
	 * @return	void
	 */
	public function __construct( $results, $countAllRows )
	{
		$this->countAllRows = $countAllRows;
		parent::__construct( $results );
	}
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init( $loadReputation=FALSE )
	{
		$classesToLoad = array( 'IPS\Member' => array() );
		$reputationIds = array();
		
		foreach ( $this->getArrayCopy() as $row )
		{
			$classesToLoad[ 'IPS\Member' ][ $row['index_author'] ] = $row['index_author'];
			
			$classesToLoad[ $row['index_class'] ][ $row['index_object_id'] ] = $row['index_object_id'];
			$itemClass = NULL;
			if ( $row['index_item_id'] and in_array( 'IPS\Content\Comment', class_parents( $row['index_class'] ) ) )
			{
				$itemClass = $row['index_class']::$itemClass;
				$classesToLoad[ $itemClass ][ $row['index_item_id'] ] = $row['index_item_id'];
			}
			if ( $row['index_container_id'] )
			{
				$containerClass = $itemClass ? $itemClass::$containerNodeClass : $row['index_class']::$containerNodeClass;
				$classesToLoad[ $containerClass ][ $row['index_container_id'] ] = $row['index_container_id'];
			}
			
			if ( $loadReputation and in_array( 'IPS\Content\Reputation', class_implements( $row['index_class'] ) ) )
			{
				$reputationIds[ $row['index_class'] ][ $row['index_object_id'] ] = $row['index_object_id'];
			}
		}
				
		$reputation = array();
		if ( count( $reputationIds ) )
		{			
			$clause = array();
			$binds = array();
			foreach ( $reputationIds as $class => $ids )
			{
				$clause[] = "( app=? AND type=? AND " . \IPS\Db::i()->in( 'type_id', $ids ) . " )";
				$binds[] = $class::$application;
				$binds[] = $class::$reputationType;
			}
			
			$where = array( array_merge( array( implode( ' OR ', $clause ) ), $binds ) );
			switch( \IPS\Settings::i()->reputation_point_types )
			{
				case 'positive':
				case 'like':
					$where[] = array( 'rep_rating=?', "1" );
					break;					
				case 'negative':
					$where[] = array( 'rep_rating=?', "-1" );
					break;
			}
			
			foreach ( \IPS\Db::i()->select( '*', 'core_reputation_index', $where ) as $rep )
			{
				$reputation[ $rep['app'] ][ $rep['type'] ][ $rep['type_id'] ][ $rep['member_id'] ] = $rep;
			}
		}		
				
		foreach ( $classesToLoad as $class => $ids )
		{
			$ids = array_diff( $ids, $class::multitonIds() );
			$idColumn = $class::$databaseColumnId;
			
			if ( !empty( $ids ) )
			{
				foreach ( \IPS\Db::i()->select( '*', $class::$databaseTable, \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnId, $ids ) ) as $row )
				{
					$obj = $class::constructFromData( $row );
					if ( $loadReputation and $obj instanceof \IPS\Content\Reputation )
					{
						if ( isset( $reputation[ $class::$application ][ $class::$reputationType ][ $obj->$idColumn ] ) )
						{
							$obj->reputation = $reputation[ $class::$application ][ $class::$reputationType ][ $obj->$idColumn ];
						}
						else
						{
							$obj->reputation = array();
						}
					}
				}
			}
		}
		
		$this->initated = TRUE;
	}
	
	/**
	 * Get current
	 *
	 * @return	\IPS\Patterns\ActiveRecord
	 */
	public function current()
	{
		$row = parent::current();
		return call_user_func( array( $row['index_class'], 'load' ), $row['index_object_id'] );
	}
	
	/**
	 * Rewind
	 *
	 * @return	void
	 */
	public function rewind()
	{
		if ( !$this->initated )
		{
			$this->init();
		}
		return parent::rewind();
	}
	
	/**
	 * Get count
	 *
	 * @param	bool	$allRows	If TRUE, will get the number of rows ignoring the limit
	 * @return	int
	 */
	public function count( $allRows = FALSE )
	{
		if ( $allRows )
		{
			return $this->countAllRows;
		}
		else
		{
			return parent::count();
		}
	}
}
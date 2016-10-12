<?php
/**
 * @brief		MySQL Search Query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search\Mysql;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MySQL Search Query
 */
class _Query extends \IPS\Content\Search\Query
{	
	/**
     * @brief       The WHERE clause
     */
    protected $where = array();
    
    /**
     * @brief       The WHERE clause for hidden/unhidden
     */
    protected $hiddenClause = NULL;
    
    /**
     * @brief       The offset
     */
    protected $offset = 0;
    
    /**
     * @brief       The ORDER BY clause
     */
    protected $order = NULL;
    
    /**
     * @brief       Joins
     */
    protected $joins = array();
    
    /**
	 * Filter by content type
	 *
	 * @param	string		$class 				The type of content to search (including all comment/review classes)
	 * @param	array|NULL	$containers			An array of container IDs to filter by, or NULL to not filter by containers
	 * @param	int|NULL	$minimumComments	The minimum number of comments
	 * @param	int|NULL	$minimumReviews		The minimum number of reviews
	 * @param	int|NULL	$minimumViews		The minimum number of views
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByContent( $class, $containers = NULL, $minimumComments = NULL, $minimumReviews = NULL, $minimumViews = NULL )
	{
		$classes = array( $class );
		if ( isset( $class::$commentClass ) )
		{
			$classes[] = $class::$commentClass;
		}
		if ( isset( $class::$reviewClass ) )
		{
			$classes[] = $class::$reviewClass;
		}
		$this->where[] = array( \IPS\Db::i()->in( 'index_class', $classes ) );
		
		if ( $containers !== NULL )
		{
			$this->where[] = array( \IPS\Db::i()->in( 'index_container_id', $containers ) );
		}
		
		if ( $minimumComments !== NULL or $minimumReviews !== NULL or $minimumViews !== NULL )
		{
			$this->joins[ $class::$databaseTable ] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId . '=core_search_index.index_item_id' );
			
			if ( $minimumComments !== NULL )
			{
				$this->where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>=?', $minimumComments );
			}
			if ( $minimumReviews !== NULL )
			{
				$this->where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['num_reviews'] . '>=?', $minimumReviews );
			}
			if ( $minimumViews !== NULL )
			{
				$this->where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['views'] . '>=?', $minimumViews );
			}
		}
				
		return $this;
	}
	
	/**
	 * Filter by content type to exclude
	 *
	 * @param	array		$classes 		The types of content to exclude (not including all comment/review classes)
	 * @param	bool		$allowedContiners		If TRUE, $class should be an array of allowed containers for that class - e.g. array( 'IPS\forums\Topic' => array( 1, 2 ) - otherwise, a simple array - e.g. array( 'IPS\forums\Topic' )
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByExcludeContent( array $classes, $allowedContiners=FALSE )
	{		
		if ( $allowedContiners )
		{
			$where = array();
			$params = array();
			$excludeAll = array();
			foreach ( $classes as $class => $allowed )
			{
				if ( count( $allowed ) )
				{
					$where[] = '( index_class<>? OR ' . \IPS\Db::i()->in( 'index_container_id', $allowed ) . ' )';
					$params[] = $class;
				}
				else
				{
					$excludeAll[] = $class;
				}
			}
			
			$finalWhere = array();
			if ( count( $excludeAll ) )
			{
				$finalWhere[] = \IPS\Db::i()->in( 'index_class', $excludeAll, TRUE );
			}
			if ( count( $where ) )
			{
				$finalWhere[] = implode( ' OR ', $where );
			}
			
			$this->where[] = array_merge( array( implode( ' AND ', $finalWhere ) ), $params );
		}
		else
		{
			$this->where[] = array( \IPS\Db::i()->in( 'index_class', $classes, TRUE ) );
		}
		return $this;
	}
	
	/**
	 * Filter by content item
	 *
	 * @param	\IPS\Content\Item	$item		The item
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByItem( \IPS\Content\Item $item )
	{
		$classes = array();
		if ( isset( $item::$commentClass ) )
		{
			$classes[] = $item::$commentClass;
		}
		if ( isset( $item::$reviewClass ) )
		{
			$classes[] = $item::$reviewClass;
		}
		
		$this->where[] = array( \IPS\Db::i()->in( 'index_class', $classes ) );
		
		$idColumn = $item::$databaseColumnId;
		$this->where[] = array( 'index_item_id=?', $item->$idColumn );
		
		return $this;
	}
	
	/**
	 * Filter by author
	 *
	 * @param	\IPS\Member	$author		The author
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByAuthor( \IPS\Member $author )
	{
		 $this->where[] = array( 'index_author=?', $author->member_id );
		 
		 return $this;
	}
		
	/**
	 * Filter by start date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByCreateDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL )
	{
		if ( $start )
		{
			$this->where[] = array( 'index_date_created>?', $start->getTimestamp() );
		}
		if ( $end )
		{
			$this->where[] = array( 'index_date_created<?', $end->getTimestamp() );
		}
		return $this;
	}
	
	/**
	 * Filter by last updated date
	 *
	 * @param	\IPS\DateTime|NULL	$start		The start date (only results AFTER this date will be returned)
	 * @param	\IPS\DateTime|NULL	$end		The end date (only results BEFORE this date will be returned)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function filterByLastUpdatedDate( \IPS\DateTime $start = NULL, \IPS\DateTime $end = NULL )
	{
		if ( $start )
		{
			$this->where[] = array( 'index_date_updated>?', $start->getTimestamp() );
		}
		if ( $end )
		{
			$this->where[] = array( 'index_date_updated<?', $end->getTimestamp() );
		}
		return $this;
	}
	
	/**
	 * Set hidden status
	 *
	 * @param	int|array	$statuses	The statuses (array of HIDDEN_ constants)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setHiddenFilter( $statuses )
	{
		if ( is_null( $statuses ) )
		{
			$this->hiddenClause = NULL;
		}
		if ( is_array( $statuses ) )
		{
			$this->hiddenClause = array( \IPS\Db::i()->in( 'index_hidden', $statuses ) );
		}
		else
		{
			$this->hiddenClause = array( 'index_hidden=?', $statuses );
		}
		
		return $this;
	}
	
	/**
	 * Set page
	 *
	 * @param	int		$page	The page number
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setPage( $page )
	{
		$this->offset = ( $page - 1 ) * $this->resultsToGet;
		
		return $this;
	}
	
	/**
	 * Set order
	 *
	 * @param	int		$order	Order (see ORDER_ constants)
	 * @return	\IPS\Content\Search\Query	(for daisy chaining)
	 */
	public function setOrder( $order )
	{
		switch ( $order )
		{
			case static::ORDER_NEWEST_UPDATED:
				$this->order = 'index_date_updated DESC';
				break;
			
			case static::ORDER_NEWEST_CREATED:
				$this->order = 'index_date_created DESC';
				break;

			case static::ORDER_RELEVANCY:
				$this->order = NULL;
				break;
		}
		
		return $this;
	}
	
	/**
	 * Build where
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	array
	 */
	protected function _searchWhereClause( $term = NULL, $tags = NULL, $method = 1 )
	{
		/* Do we have a term? */
		$where = array();
		if ( $term !== NULL )
		{
			if( mb_strpos( $term, ' ' ) !== FALSE AND mb_strpos( $term, '+' ) === FALSE AND mb_strpos( $term, '-' ) === FALSE AND mb_strpos( $term, '"' ) === FALSE )
			{
				$terms		= explode( ' ', $term );
				$newTerms	= array();

				foreach( $terms as $_term )
				{
					if( mb_strlen( $_term ) > 2 )
					{
						$newTerms[] = "+" . $_term;
					}
					else
					{
						$newTerms[] = $_term;
					}
				}

				$term	= implode( ' ', $newTerms );
			}

			/* Try to guess what mode will be best for this query. If it looks like a MySQL boolean mode search, use that */
			if ( mb_substr( $term, 0, 1 ) === '+' or mb_substr( $term, -1, 1 ) === '*' or preg_match( '/^".*"$/', $term ) )
			{
				$mode = 'IN BOOLEAN MODE';
			}
			/* Otherwise, we're just using the default mode - we tried using query expansion for short queries, but it didn't work well (lots of irrelevant results) */
			else
			{
				$mode = '';
			}
			
			/* If we also have tags, create a combined where */
			if ( $tags !== NULL )
			{
				if ( $method === static::TERM_OR_TAGS )
				{
					$where[] = array( "( MATCH(index_content,index_title) AGAINST (? {$mode}) OR " . \IPS\Db::i()->findInSet( 'index_tags', $tags ) . ' )', $term );
				}
				else
				{
					$where[] = array( "MATCH(index_content,index_title) AGAINST (? {$mode})", $term );
					$where[] = array( \IPS\Db::i()->findInSet( 'index_tags', $tags ) );
				}
			}
			/* Or just use the term */
			else
			{
				$where[] = array( "MATCH(index_content,index_title) AGAINST (? {$mode})", $term );
			}
		}
		/* Or do we have tags? */
		elseif ( $tags !== NULL )
		{
			//$this->where[] = array( \IPS\Db::i()->findInSet( 'index_tags', $tags ) );

			$tagWhere	= array();
			$params		= array();

			foreach( $tags as $tag )
			{
				array_push( $params, $tag, $tag, $tag, $tag );
				$tagWhere[] = "( index_tags=? OR index_tags LIKE CONCAT( ?, ',%' ) OR index_tags LIKE CONCAT( '%,', ? ) OR index_tags LIKE CONCAT( '%,', ?, ',%' ) )";
			}

			$where[] = array_merge( array( '(' . implode( ' OR ', $tagWhere ) . ')' ), $params );
		}
		
		/* Only get stuff we have permission for */
		$where[] = array( "( index_permissions = '*' OR " . \IPS\Db::i()->findInSet( 'index_permissions', $this->permissionArray() ) . ' )' );
		if ( $this->hiddenClause )
		{
			$where[] = $this->hiddenClause;
		}
		
		/* Return */
		return $where;
	}
	
	/**
	 * Search
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	\IPS\Content\Search\Results
	 */
	public function search( $term = NULL, $tags = NULL, $method = 1 )
	{
		$query = \IPS\Db::i()->select( '*', 'core_search_index', array_merge( $this->where, $this->_searchWhereClause( $term, $tags, $method ) ), $this->order, array( $this->offset, $this->resultsToGet ), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
		foreach ( $this->joins as $table => $where )
		{
			$query->join( $table, $where );
		}
		
		$count = $query->count( TRUE );
		return new \IPS\Content\Search\Results( iterator_to_array( $query ), $count );
	}
	
	/**
	 * Get count
	 *
	 * @param	string|null	$term	The term to search for
	 * @param	array|null	$tags	The tags to search for
	 * @param	int			$method	\IPS\Content\Search\Index::i()->TERM_OR_TAGS or \IPS\Content\Search\Index::i()->TERM_AND_TAGS
	 * @return	int
	 */
	public function count( $term = NULL, $tags = NULL, $method = 1 )
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'core_search_index', array_merge( $this->where, $this->_searchWhereClause( $term, $tags, $method ) ) )->first();
	}
}
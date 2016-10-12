<?php
/**
 * @brief		Content Item Feed Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	forums
 * @since		16 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Item Feed Widget
 */
abstract class _Widget extends \IPS\Widget\PermissionCache
{
	/**
	 * Class
	 */
	protected static $class;
			
	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
		/* Init */
		$class = static::$class;
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}
 		
 		/* Block title */ 		
		$form->add( new \IPS\Helpers\Form\Text( 'widget_feed_title', isset( $this->configuration['widget_feed_title'] ) ? $this->configuration['widget_feed_title'] : \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) ) );
		
		/* Container */
		if ( isset( $class::$containerNodeClass ) )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'widget_feed_container_' . $class::$title, isset( $this->configuration['widget_feed_container'] ) ? $this->configuration['widget_feed_container'] : 0, FALSE, array(
				'class'           => $class::$containerNodeClass,
				'zeroVal'         => 'all',
				'permissionCheck' => 'view',
				'multiple'        => true
			) ) );
		}
		
		/* Use permissions? */
		if ( in_array( 'IPS\Content\Permissions', class_implements( $class ) ) )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'widget_feed_use_perms', isset( $this->configuration['widget_feed_use_perms'] ) ? $this->configuration['widget_feed_use_perms'] : TRUE, FALSE ) );
		}
		
		/* Types */
		if ( in_array( 'IPS\Content\Lockable', class_implements( $class ) ) or in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) or in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			$types = array();
			if ( in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
			{
				$types['open'] = 'mod_confirm_unlock';
				$types['closed'] = 'mod_confirm_lock';
			}
			if ( in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
			{
				$types['pinned'] = 'mod_confirm_pin';
				$types['notpinned'] = 'mod_confirm_unpin';
			}
			if ( in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
			{
				$types['featured'] = 'mod_confirm_feature';
				$types['notfeatured'] = 'mod_confirm_unfeature';
			}
			
			$form->add( new \IPS\Helpers\Form\CheckboxSet( 'widget_feed_status', isset( $this->configuration['widget_feed_status'] ) ? $this->configuration['widget_feed_status'] : array_keys( $types ), FALSE, array( 'options' => $types ) ) );
		}
		
		/* Author */
		$author = NULL;
		try
		{
			if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) )
			{
				foreach( $this->configuration['widget_feed_author']  as $id )
				{
					$author[ $id ] = \IPS\Member::load( $id );
				}
			}
		}
		catch( \OutOfRangeException $ex ) { }
		$form->add( new \IPS\Helpers\Form\Member( 'widget_feed_author', $author, FALSE, array( 'multiple' => true ) ) );
		
		/* Minimum comments/reviews */
		if ( isset( $class::$commentClass ) )
		{
			if ( $class::$firstCommentRequired )
			{
				$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_posts', isset( $this->configuration['widget_feed_min_posts'] ) ? $this->configuration['widget_feed_min_posts'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
			}
			else
			{
				$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_comments', isset( $this->configuration['widget_feed_min_comments'] ) ? $this->configuration['widget_feed_min_comments'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
			}
		}
		if ( isset( $class::$reviewClass ) )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_reviews', isset( $this->configuration['widget_feed_min_reviews'] ) ? $this->configuration['widget_feed_min_reviews'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
		}
		
		/* Rating */
		if ( in_array( 'IPS\Content\Ratings', class_implements( $class ) ) and isset( $class::$databaseColumnMap['rating_average'] ) )
		{
			$form->add( new \IPS\Helpers\Form\Rating( 'widget_feed_min_rating', isset( $this->configuration['widget_feed_min_rating'] ) ? $this->configuration['widget_feed_min_rating'] : 0, FALSE, array() ) );
		}
		
		/* Number to show */
 		$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_show', isset( $this->configuration['widget_feed_show'] ) ? $this->configuration['widget_feed_show'] : 5, TRUE ) );
 		
 		/* Sort */
 		$sortOptions = array();
 		foreach ( array( 'updated', 'title', 'num_comments', 'date', 'views', 'rating_average' ) as $k )
 		{
	 		if ( isset( $class::$databaseColumnMap[ $k ] ) )
	 		{
		 		$sortOptions[ $class::$databaseColumnMap[ $k ] ] = 'sort_' . $k;
	 		}
 		}
		$form->add( new \IPS\Helpers\Form\Select( 'widget_feed_sort_on', isset( $this->configuration['widget_feed_sort_on'] ) ? $this->configuration['widget_feed_sort_on'] : $class::$databaseColumnMap['updated'], FALSE, array( 'options' => $sortOptions ) ), NULL, NULL, NULL, 'widget_feed_sort_on' );

		$form->add( new \IPS\Helpers\Form\Select( 'widget_feed_sort_dir', isset( $this->configuration['widget_feed_sort_dir'] ) ? $this->configuration['widget_feed_sort_dir'] : 'desc', FALSE, array(
            'options' => array(
	            'desc'   => 'descending',
	            'asc'    => 'ascending'
            )
        ) ) );

 		return $form;
 	}
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( $values )
 	{
	 	$class = static::$class;
	 	
 		if ( is_array( $values[ 'widget_feed_container_' . $class::$title ] ) )
 		{
	 		$values['widget_feed_container'] = array_keys( $values[ 'widget_feed_container_' . $class::$title ] );
 		}
 		
 		if ( is_array( $values['widget_feed_author'] ) )
 		{
	 		$members = array();
	 		foreach( $values['widget_feed_author'] as $member )
	 		{
		 		$members[] = $member->member_id;
	 		}
	 		
	 		$values['widget_feed_author'] = $members;
 		}
 		
 		return $values;
 	}
 	
 	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere()
	{
		$class = static::$class;
		$where = array();
				
		/* Container */
		if ( isset( $class::$containerNodeClass ) and !empty( $this->configuration['widget_feed_container'] ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['container'], $this->configuration['widget_feed_container'] ) );
		}
		
		/* Status */
		if ( isset( $this->configuration['widget_feed_status'] ) and in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			if ( in_array( 'closed', $this->configuration['widget_feed_status'] ) AND !in_array( 'open', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = isset( $class::$databaseColumnMap['locked'] ) ? array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', 1 ) : array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['status'] . '=?', 'closed' );
			}
			elseif ( in_array( 'open', $this->configuration['widget_feed_status'] ) AND !in_array( 'closed', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = isset( $class::$databaseColumnMap['locked'] ) ? array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', 0 ) : array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['status'] . '=?', 'open' );
			}
		}

		if ( isset( $this->configuration['widget_feed_status'] ) and in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			if ( in_array( 'notfeatured', $this->configuration['widget_feed_status'] ) AND !in_array( 'featured', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['featured'] . '=0' );
			}
			elseif ( in_array( 'featured', $this->configuration['widget_feed_status'] ) AND !in_array( 'notfeatured', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['featured'] . '=1' );
			}
		}

		if ( isset( $this->configuration['widget_feed_status'] ) and in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			if ( in_array( 'notpinned', $this->configuration['widget_feed_status'] ) AND !in_array( 'pinned', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . '=0' );
			}
			elseif ( in_array( 'pinned', $this->configuration['widget_feed_status'] ) AND !in_array( 'notpinned', $this->configuration['widget_feed_status'] ) )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . '=1' );
			}
		}

		/* Author */
		if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['author'], $this->configuration['widget_feed_author'] ) );
		}
		
		/* Min comments/reviews */
		if ( isset( $this->configuration['widget_feed_min_posts'] ) and $this->configuration['widget_feed_min_posts'] )
		{
			$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>=?', (int) $this->configuration['widget_feed_min_posts'] );
		}
		if ( isset( $this->configuration['widget_feed_min_comments'] ) and $this->configuration['widget_feed_min_comments'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>?', (int) $this->configuration['widget_feed_min_comments'] );
		}
		if ( isset( $this->configuration['widget_feed_min_reviews'] ) and $this->configuration['widget_feed_min_reviews'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_reviews'] . '>?', (int) $this->configuration['widget_feed_min_reviews'] );
		}
		
		/* Rating */
		if ( isset( $this->configuration['widget_feed_min_rating'] ) and $this->configuration['widget_feed_min_rating'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['rating_average'] . '>?', (int) $this->configuration['widget_feed_min_rating'] );
		}

		/* Start date */
		if ( isset( $this->configuration['widget_feed_start_date'] ) and isset( $this->configuration['widget_feed_start_date'] ) and $this->configuration['widget_feed_start_date'] > 0 )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['date'] . '>?',  \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $this->configuration['widget_feed_start_date'] . 'D' ) )->getTimestamp() );
		}

		return $where;
	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$class = static::$class;
						
		$items = $class::getItemsWithPermission(
			$this->buildWhere(),
			( isset( $this->configuration['widget_feed_sort_on'] ) and isset( $this->configuration['widget_feed_sort_dir'] ) ) ? ( $class::$databasePrefix . $this->configuration['widget_feed_sort_on'] . ' ' . $this->configuration['widget_feed_sort_dir'] ) : NULL,
			isset( $this->configuration['widget_feed_show'] ) ? $this->configuration['widget_feed_show'] : 5,
			( !isset( $this->configuration['widget_feed_use_perms'] ) or $this->configuration['widget_feed_use_perms'] ) ? 'read' : NULL,
			FALSE
		);
				
		if ( count( $items ) )
		{
			return $this->output( $items, isset( $this->configuration['widget_feed_title'] ) ? $this->configuration['widget_feed_title'] : \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) );
		}
		else
		{
			return '';
		}
	}
}
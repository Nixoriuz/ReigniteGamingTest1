<?php
/**
 * @brief		RecordFeed Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	cms
 * @since		24 Nov 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * RecordFeed Widget
 */
class _RecordFeed extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'RecordFeed';
	
	/**
	 * @brief	App
	 */
	public $app = 'cms';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init()
	{ 
		parent::init();
		// Use this to perform any set up and to assign a template that is not in the following format:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
	}

	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}

		$databases = array();
		$database  = NULL;
		foreach( \IPS\cms\Databases::databases() as $obj )
		{
			if ( $obj->page_id )
			{
				$databases[ $obj->_id ] = $obj->_title;
			}
			
			if ( isset( $this->configuration['cms_rf_database'] ) AND $obj->id == $this->configuration['cms_rf_database'] )
			{
				$database = $obj;
			}
		}

		$form->add( new \IPS\Helpers\Form\Select( 'cms_rf_database', isset( $this->configuration['cms_rf_database'] ) ? $this->configuration['cms_rf_database'] : 0, FALSE, array(
            'disabled' => isset( $this->configuration['cms_rf_database'] ) ? true : false,
			'options'  => $databases
		) ) );

		$form->add( new \IPS\Helpers\Form\Node( 'cms_rf_category', isset( $this->configuration['cms_rf_category'] ) ? $this->configuration['cms_rf_category'] : 0, FALSE, array(
			'class'           => '\IPS\cms\Categories' . ( isset( $this->configuration['cms_rf_database'] ) ? $this->configuration['cms_rf_database'] : '' ),
			'zeroVal'         => 'cms_rf_all_categories',
			'permissionCheck' => 'view',
			'multiple'        => true
		) ) );


		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'cms_rf_record_status', isset( $this->configuration['cms_rf_record_status'] ) ? $this->configuration['cms_rf_record_status'] : array( 'open', 'pinned', 'notpinned', 'visible', 'featured', 'notfeatured' ), FALSE, array(
			'options' => array(
				'open'        => 'cms_rf_open_status_open',
				'closed'      => 'cms_rf_open_status_closed',
				'pinned'      => 'cms_rf_open_status_pinned',
				'notpinned'   => 'cms_rf_open_status_notpinned',
				'visible'     => 'cms_rf_open_status_visible',
				'hidden'      => 'cms_rf_open_status_hidden',
				'featured'    => 'cms_rf_open_status_featured',
				'notfeatured' => 'cms_rf_open_status_notfeatured'
			)
		) ) );

		/* Tags */
		if ( $database and $database->tags_enabled )
		{
			$options = array( 'autocomplete' => array( 'unique' => TRUE, 'source' => NULL, 'freeChoice' => TRUE ) );

			if ( \IPS\Settings::i()->tags_force_lower )
			{
				$options['autocomplete']['forceLower'] = TRUE;
			}

			if ( \IPS\Settings::i()->tags_clean )
			{
				$options['autocomplete']['filterProfanity'] = TRUE;
			}

			$options['autocomplete']['prefix'] = FALSE;

			$form->add( new \IPS\Helpers\Form\Text( 'cms_rf_tags', ( isset( $this->configuration['cms_rf_tags'] ) ? $this->configuration['cms_rf_tags'] : array( 'tags' => NULL ) ), FALSE, $options ) );
		}

		$author = NULL;

		try
		{
			if ( isset( $this->configuration['cms_rf_author'] ) and is_array( $this->configuration['cms_rf_author'] ) )
			{
				foreach( $this->configuration['cms_rf_author']  as $id )
				{
					$author[ $id ] = \IPS\Member::load( $id );
				}
			}
		}
		catch( \OutOfRangeException $ex ) { }

		$form->add( new \IPS\Helpers\Form\Member( 'cms_rf_author', $author, FALSE, array( 'multiple' => true ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cms_rf_min_posts', isset( $this->configuration['cms_rf_min_posts'] ) ? $this->configuration['cms_rf_min_posts'] : 0, FALSE, array( 'unlimitedLang' => 'cms_rf_min_posts_any', 'unlimited' => 0 ) ) );

		$form->add( new \IPS\Helpers\Form\Number( 'cms_rf_show', isset( $this->configuration['cms_rf_show'] ) ? $this->configuration['cms_rf_show'] : 5, TRUE ) );

		$form->add( new \IPS\Helpers\Form\Select( 'cms_rf_sort_on', isset( $this->configuration['cms_rf_sort_on'] ) ? $this->configuration['cms_rf_sort_on'] : 'record_last_comment', FALSE, array(
			'options' => array(
				'record_last_comment'   => 'cms_rf_record_last_comment',
				'record_title'          => 'cms_rf_title',
				'record_comments'       => 'cms_rf_record_comments',
				'record_publish_date'   => 'cms_rf_record_publish_date',
				'record_views'          => 'cms_rf_record_views'
			)
		) ), NULL, NULL, NULL, 'cms_rf_sort_on' );

		$form->add( new \IPS\Helpers\Form\Select( 'cms_rf_sort_dir', isset( $this->configuration['cms_rf_sort_dir'] ) ? $this->configuration['cms_rf_sort_dir'] : 'desc', FALSE, array(
			'options' => array(
				'desc'   => 'cms_rf_sort_dir_dsc',
				'asc'    => 'cms_rf_sort_dir_asc'
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
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if( isset( $this->configuration['cms_rf_database'] ) )
		{
			try
			{
				$database = \IPS\cms\Databases::load($this->configuration['cms_rf_database']);
				
				if ( ! $database->page_id )
				{
					throw new \OutOfRangeException;
				}
			}
			catch ( \OutOfRangeException $e )
			{
				return '';
			}
		}
		else
		{
			return '';
		}

		$where = array();
		$order = NULL;
		$limit = isset( $this->configuration['cms_rf_show'] ) ? $this->configuration['cms_rf_show'] : 5;
		$permissionKey = 'read';
		$includeHidden = false;

		if ( isset( $this->configuration['cms_rf_category'] ) and is_array( $this->configuration['cms_rf_category'] ) )
		{
			$where[] = array( 'category_id IN (' . implode( ",", array_values( $this->configuration['cms_rf_category'] ) ) . ')' );
		}

		if ( isset( $this->configuration['cms_rf_record_status'] ) and is_array( $this->configuration['cms_rf_record_status'] ) )
		{
			$status = array_values( $this->configuration['cms_rf_record_status'] );

			if ( ! in_array( 'open', $status ) or ! in_array( 'closed', $status ) )
			{
				if ( ! in_array( 'open', $status ) )
				{
					$where[] = array( "record_locked=1" );
				}
				else if ( ! in_array( 'closed', $status ) )
				{
					$where[] = array( "record_locked=0" );
				}
			}

			if ( in_array( 'hidden', $status ) )
			{
				$includeHidden = true;
			}

			if ( ! in_array( 'featured', $status ) or ! in_array( 'notfeatured', $status ) )
			{
				if ( ! in_array( 'featured', $status ) )
				{
					$where[] = array( 'record_featured=0' );
				}
				else if ( ! in_array( 'notfeatured', $status ) )
				{
					$where[] = array( 'record_featured=1' );
				}
			}

			if ( ! in_array( 'pinned', $status ) or ! in_array( 'notpinned', $status ) )
			{
				if ( ! in_array( 'pinned', $status ) )
				{
					$where[] = array( 'record_pinned=0' );
				}
				else if ( ! in_array( 'notpinned', $status ) )
				{
					$where[] = array( 'record_pinned=1' );
				}
			}
		}

		if ( isset( $this->configuration['cms_rf_author'] ) and !empty( $this->configuration['cms_rf_author'] ) )
		{
			$where[] = array( "starter_id IN(" . implode( ',', $this->configuration['cms_rf_author'] ) . ")" );
		}

		if ( isset( $this->configuration['cms_rf_sort_on'] ) and isset( $this->configuration['cms_rf_sort_on'] ) )
		{
			if ( $this->configuration['cms_rf_sort_on'] == 'record_title' )
			{
				$this->configuration['cms_rf_sort_on'] =  $database->field_title;
			}

			$order = $this->configuration['cms_rf_sort_on'] . ' ' . $this->configuration['cms_rf_sort_dir'];
		}

		/* Tags */
		if ( isset( $this->configuration['cms_rf_tags'] ) and $this->configuration['cms_rf_tags'] )
		{
			$tagsWhere = array( array( 'tag_meta_app=? AND tag_meta_area=?', 'cms', 'records' . $database->_id ) );

			if ( isset( $this->configuration['cms_rf_category'] ) and is_array( $this->configuration['cms_rf_category'] ) )
			{
				$tagsWhere[] = array( 'tag_meta_parent_id IN (' . implode( array_values( $this->configuration['cms_rf_category'] ) ) . ')' );
			}

			$ids = array();
			foreach( \IPS\Db::i()->select('tag_meta_id', 'core_tags', $tagsWhere, 'tag_added DESC', array(0, 250) ) as $tag )
			{
				$ids[] = $tag['tag_meta_id'];
			}

			if ( count( $ids ) )
			{
				$where[] = array( \IPS\Db::i()->in('primary_id_field', $ids ) );
			}
		}

		$class   = '\IPS\cms\Records' . $database->_id;
		$records = $class::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHidden  );
		if ( count( $records ) )
		{
			return $this->output( $database, $records );
		}
		else
		{
			return '';
		}
	}
}
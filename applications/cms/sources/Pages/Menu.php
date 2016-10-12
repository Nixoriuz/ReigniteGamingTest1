<?php
/**
 * @brief		Menu Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		15 Sept 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Menu Model
 */
class _Menu extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'cms_page_menu';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'menu_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static $databaseColumnParentRootValue = 0;
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'cms_menu';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'cms_menu_title_';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	array(
	'app'		=> 'core',				// The application key which holds the restrictrions
	'module'	=> 'foo',				// The module key which holds the restrictions
	'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	'add'			=> 'foo_add',
	'edit'			=> 'foo_edit',
	'permissions'	=> 'foo_perms',
	'delete'		=> 'foo_delete'
	),
	'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
 		'app'		=> 'cms',
 		'module'	=> 'pages',
 		'all'		=> 'page_manage',
 		'prefix'	=> 'page_'
	);

	/**
	 * Form elements
	 *
	 * @param   \IPS\cms\Pages\Menu|null    $node       Menu item
	 * @return array
	 */
	public static function formElements( $node=NULL, $forceType=NULL )
	{
		$form = array();

		if ( $forceType === NULL )
		{
			$form['menu_type'] = new \IPS\Helpers\Form\Select( 'menu_type', ( $node !== NULL ? $node->type : 'page' ), TRUE, array(
				'options'	=> array(
					'page'      => 'cms_menu_form_type_page',
					'url'       => 'cms_menu_form_type_url',
					'folder'    => 'cms_menu_form_type_folder',
					#'separator' => 'cms_menu_form_type_separator'
				),
				'toggles'	    => array(
					'page'	    => array( 'menu_content_page', 'menu_title_page', 'menu_permission_from_page'),
					'url'       => array( 'menu_content_url', 'menu_title_url', 'menu_permission' ),
					'folder'    => array( 'menu_title_folder', 'menu_permission')
				)
			) );
		}

		/* Page fields */
		if ( $forceType !== 'page' )
		{
			$pages = array();
			foreach( \IPS\cms\Pages\Page::roots() as $id => $page )
			{
				$pages[ $id ] = $page->_title . ' (' . $page->full_path . ')';
			}

			$form['menu_content_page'] = new \IPS\Helpers\Form\Select( 'menu_content_page',  $node !== NULL ? ( $node->type === 'page' ? $node->content : NULL ) : 0, FALSE, array(
				'options' => $pages
			), NULL, NULL, NULL, 'menu_content_page' );
		}

		$form['menu_title_page'] = new \IPS\Helpers\Form\Translatable( 'menu_title_page',  NULL, FALSE, array( 'app' => 'cms', 'key' => ( ( ! empty( $node ) and $node->type === 'page' ) ? "cms_menu_title_" . $node->id : NULL ), 'maxLength' => 64 ), NULL, NULL, NULL, 'menu_title_page' );

		/* URL Fields */
		if ( $forceType === NULL or $forceType === 'url' )
		{
			$form['menu_content_url'] = new \IPS\Helpers\Form\Text( 'menu_content_url',  $node !== NULL ? ( $node->type === 'url' ? $node->content : NULL ) : '', FALSE, array(), NULL, NULL, NULL, 'menu_content_url' );

			$form['menu_title_url'] = new \IPS\Helpers\Form\Translatable( 'menu_title_url',  NULL, FALSE, array( 'app' => 'cms', 'key' => ( ( ! empty( $node ) and $node->type === 'url' ) ? "cms_menu_title_" . $node->id : NULL ), 'maxLength' => 64 ), NULL, NULL, NULL, 'menu_title_url' );
		}

		/* Folder fields */
		if ( $forceType === NULL or $forceType === 'folder' )
		{
			$form['menu_title_folder'] = new \IPS\Helpers\Form\Translatable( 'menu_title_folder', NULL, FALSE, array( 'app' => 'cms', 'key' => ( ( ! empty( $node ) and $node->type === 'folder' ) ? "cms_menu_title_" . $node->id : NULL ), 'maxLength' => 64 ), NULL, NULL, NULL, 'menu_title_folder' );
		}

		/* Shared fields */
		$form['menu_parent_id'] = new \IPS\Helpers\Form\Node( 'menu_parent_id',  $node !== NULL ? intval( $node->parent_id ) : 0, FALSE, array(
			'class'         => '\IPS\cms\Pages\Menu',
			'zeroVal'         => 'node_no_parent',
			'permissionCheck' => function( $node )
				{
					return $node->type === 'folder';
				}
		), NULL, NULL, NULL, 'menu_parent_id' );

		$form['menu_permission_from_page'] = new \IPS\Helpers\Form\Radio( 'menu_permission_type',  $node !== NULL ? ( $node->permission === 'page' ? 'page' : 'manual' ) : ( $forceType === 'page' ? 'page' : 'manual' ), FALSE, array(
			'options'    => array(
				'page'   => 'cms_menu_form_perm_page',
				'manual' => 'cms_menu_form_perm_manual'
			),
			'toggles' => array(
				'manual'    => array( 'menu_permission' )
			)
		), NULL, NULL, NULL, 'menu_permission_from_page' );

		$form['menu_permission'] = new \IPS\Helpers\Form\Select( 'menu_permission', ( $node !== NULL ) ? ( ( $node->permission ===  '*' ) ? '*' : explode( ',', $node->permission ) ) : NULL, FALSE, array(
			'options' => array_combine( array_keys( \IPS\Member\Group::groups() ), array_map( function( $_group ) { return (string) $_group; }, \IPS\Member\Group::groups() ) ),
			'multiple' => true,
			'unlimited' => '*',
			'unlimitedLang' => 'cms_menu_perm_any'
		), NULL, NULL, NULL, 'menu_permission' );

		return $form;
	}

	/**
	 * Clear caches
	 *
	 * @return void
	 */
	public static function clearCaches()
	{
		unset( \IPS\Data\Store::i()->cms_menu );
	}

	/**
	 * [Node] Set Title
	 *
	 * @param	string	$title	Title
	 * @return	string|null
	 */
	protected function set__title( $title )
	{
		$this->title = $title; 
	}

	/**
	 * [Node] Get Title
	 *
	 * @return	string|null
	 */
	protected function get__title()
	{
		if ( ! $this->title AND $this->type === 'page' )
		{
			try
			{
				$this->title = \IPS\cms\Pages\Page::load( $this->content )->_title;
			}
			catch( \OutOfRangeException $ex )
			{
				$this->title = '';
			}
		}
		else if ( ! $this->title )
		{
			/* This is stored in a separate \Data\Store cache, so we can't return the hash */
			$this->title = \IPS\Member::loggedIn()->language()->get( 'cms_menu_title_' . $this->id );
		}

		return $this->title;
	}

	/**
	 * [Node] Get URL
	 *
	 * @return	string|null
	 */
	protected function get__url()
	{
		if ( $this->type === 'page' )
		{
			try
			{
				return \IPS\cms\Pages\Page::load( $this->content )->url();
			}
			catch( \OutOfRangeException $ex )
			{
				return '';
			}
		}

		return $this->content;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge()
	{
		if ( ! $this->permission )
		{
			return array( 'ipsBadge ipsBadge_negative', 'cms_menu_no_item_perm' );
		}

		return ( $this->type === 'folder' ) ? NULL : array( 'ipsBadge ipsBadge_intermediary ipsPos_right', 'cms_menu_form_type_' . $this->type );
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	array(
	array(
	'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	'title'	=> 'foo',		// Language key to use for button's title parameter
	'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	),
	...							// Additional buttons
	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );

		if ( isset( $buttons['copy'] ) and $this->type === 'folder' )
		{
			unset( $buttons['copy'] );
		}

		return $buttons;
	}

	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName()
	{
		return $this->name;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Build form */
		foreach( static::formElements( $this ) as $element )
		{
			$form->add( $element );
		}

		if ( $this->id )
		{
			\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('cms_menu_edit', FALSE, array( 'sprintf' => array( $this->_title ) ) );
		}
		else
		{
			\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('cms_menu_adding');
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( ! $this->id )
		{
			$this->parent_id = 0;
			$this->save();
		}

		if( isset( $values['menu_type'] ) )
		{
			$title   = '';
			$content = NULL;

			switch ( $values['menu_type'] )
			{
				case 'page':
					$title   = $values['menu_title_page'];
					$content = $values['menu_content_page'];
				break;
				case 'url':
					$title   = $values['menu_title_url'];
					$content = $values['menu_content_url'];
				break;
				case 'folder':
					$title   = $values['menu_title_folder'];
				break;
			}

			/* We need to store something in menu_title field in the database as we can leave this blank to get the current page title. We can't examine an addToStack to see if any data was returned */
			$values['title']   = current( $title );
			$values['content'] = $content;

			\IPS\Lang::saveCustom( 'cms', "cms_menu_title_" . $this->id, $title );

			unset( $values['menu_title_folder'], $values['menu_title_url'], $values['menu_title_page'], $values['menu_content_page'], $values['menu_content_url'] );
		}

		if( isset( $values['menu_permission'] ) )
		{
			$values['menu_permission'] = ( is_array( $values['menu_permission'] ) ) ? implode( ',', $values['menu_permission'] ) : $values['menu_permission'];
		}

		if ( isset( $values['menu_type'] ) AND $values['menu_type'] === 'page' and $values['menu_permission_type'] === 'page' )
		{
			$values['menu_permission'] = 'page';
		}

		if ( isset( $values['menu_parent_id'] ) AND ( ! empty( $values['menu_parent_id'] ) OR $values['menu_parent_id'] === 0 ) )
		{
			$values['menu_parent_id'] = ( $values['menu_parent_id'] === 0 ) ? 0 : $values['menu_parent_id']->id;
		}

		if( isset( $values['menu_permission_type'] ) )
		{
			unset( $values['menu_permission_type'] );
		}
	
		return $values;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		if ( parent::canAdd() )
		{
			return $this->type === 'folder';
		}

		return FALSE;
	}


	/**
	 * Save
	 *
	 * @return void
	 */
	public function save()
	{
		static::clearCaches();
		parent::save();
	}
}
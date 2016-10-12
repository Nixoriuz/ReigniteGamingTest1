<?php
/**
 * @brief		Revisions Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		29 April 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class _Revisions extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'cms_database_revisions';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'revision_';

	/**
	 * @brief	Unpacked data
	 */
	protected $_dataJson = NULL;
	
	/**
	 * Constructor - Create a blank object with default values
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		if ( $this->_new )
		{
			$this->member_id = \IPS\Member::loggedIn()->member_id;
			$this->date      = time();
		}
	}
	
	/**
	 * Get a value by key
	 * 
	 * @param   string $key	Key of value to return
	 * @return	mixed
	 */
	public function get( $key )
	{
		if ( $this->_dataJson === NULL )
		{
			$this->_dataJson = $this->data;
		}
		
		if ( isset( $this->_dataJson[ $key ] ) )
		{
			return $this->_dataJson[ $key ];
		}
		
		return NULL;
	}

	/**
	 *  Compute differences
	 *
	 * @param   int                 $databaseId     Database ID
	 * @param   \IPS\cms\Records    $record         Record
	 * @param   boolean             $justChanged    Get changed only
	 * @return array
	 */
	public function getDiffHtmlTables( $databaseId, $record, $justChanged=FALSE )
	{
		$fieldsClass  = 'IPS\cms\Fields' .  $databaseId;
		$customFields = $fieldsClass::data( 'view' );
		$conflicts    = array();

		require_once \IPS\ROOT_PATH . "/system/3rd_party/Diff/class.Diff.php";

		/* Build up our data set */
		foreach( $customFields as $id => $field )
		{
			$key = 'field_' . $field->id;

			$conflicts[ $key ] = array( 'revision' => $this->get( $key ), 'record' => $record->$key, 'field' => $field, 'diff' => NULL );

			if ( $record->$key AND $this->get( $key ) )
			{
				if ( md5( $record->$key ) != md5( $this->get( $key ) ) )
				{
					$conflicts[ $key ]['diff'] = \Diff::toTable( \Diff::compare( $this->get( $key ), $record->$key ) );
				}
				else if ( $justChanged !== FALSE )
				{
					unset( $conflicts[ $key ] );
				}
			}
		}

		return $conflicts;
	}

	/**
	 * Set the "data" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_data( $value )
	{
		$this->_data['data'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "data" field
	 *
	 * @return array
	 */
	public function get_data()
	{
		return json_decode( $this->_data['data'], TRUE );
	}
	
	
}
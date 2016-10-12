<?php
/**
 * @brief		Content Router extension: Records
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Board
 * @since		17 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Records
 */
class _Records
{
	/**
	 * @brief	Content Item Classes
	 */
	public $classes = array();
	
	/**
	 * Define classes here
	 *
	 * @return array
	 */
	public function __construct()
	{
		try
		{
			foreach ( \IPS\Db::i()->select( 'database_id', 'cms_databases', 'database_page_id>0' ) as $id )
			{
				$this->classes[] = 'IPS\cms\Records' . $id;
			}
		}
		catch ( \Exception $e ) {} // If you have not upgraded pages but it is installed, this throws an error
	}
}
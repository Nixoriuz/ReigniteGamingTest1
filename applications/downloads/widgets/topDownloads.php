<?php
/**
 * @brief		topDownloads Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	downloads
 * @since		09 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topDownloads Widget
 */
class _topDownloads extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'topDownloads';
	
	/**
	 * @brief	App
	 */
	public $app = 'downloads';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * @brief	Cache Expiration
	 * @note	We allow this cache to be valid for 48 hours
	 */
	public $cacheExpiration = 172800;

	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
 		if ( $form === null )
 		{
	 		$form = new \IPS\Helpers\Form;
 		} 
 		
		$form->add( new \IPS\Helpers\Form\Number( 'number_to_show', isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, TRUE ) );
		return $form;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$categories = array();

		foreach( \IPS\Db::i()->select( 'perm_type_id', 'core_permission_index', array( 'app=? and perm_type=? and (' . \IPS\Db::i()->findInSet( 'perm_' . \IPS\downloads\Category::$permissionMap['read'], \IPS\Member::loggedIn()->groups ) . ' OR ' . 'perm_' . \IPS\downloads\Category::$permissionMap['read'] . '=? )', 'downloads', 'category', '*' ) ) as $category )
		{
			$categories[]	= $category;
		}

		if( !count( $categories ) )
		{
			return '';
		}

		foreach ( array( 'week' => 'P1W', 'month' => 'P1M', 'year' => 'P1Y', 'all' => NULL ) as $time => $interval )
		{			
			$where = array( array( 'file_cat IN(' . implode( ',', $categories ) . ')' ) );
			if ( $interval )
			{
				$where[] = array( 'dtime>?', \IPS\DateTime::create()->sub( new \DateInterval( $interval ) )->getTimestamp() );
			}
			
			
			$$time = new \IPS\Patterns\ActiveRecordIterator(
				\IPS\Db::i()->select(
					'*, COUNT(*) AS downloads',
					'downloads_files',
					$where,
					'downloads DESC',
					isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5,
					'file_id'
				)
				->join( 'downloads_downloads', 'dfid=file_id' ),
				'IPS\downloads\File'
			);
		}
		
		return $this->output( $week, $month, $year, $all );
	}
}
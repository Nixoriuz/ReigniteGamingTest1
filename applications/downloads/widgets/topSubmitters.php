<?php
/**
 * @brief		topSubmitters Widget
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
 * topSubmitters Widget
 */
class _topSubmitters extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'topSubmitters';
	
	/**
	 * @brief	App
	 */
	public $app = 'downloads';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

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
		foreach ( array( 'week' => 'P1W', 'month' => 'P1M', 'year' => 'P1Y', 'all' => NULL ) as $time => $interval )
		{
			$select = \IPS\Db::i()->select( 'core_members.*, downloads_files.file_submitter, COUNT(*) AS files, AVG(file_rating) as rating', 'downloads_files', $interval ? array( 'file_submitted>? and file_submitter != ? and file_rating > ?', \IPS\DateTime::create()->sub( new \DateInterval( $interval ) )->getTimestamp(), 0, 0 ) : array( 'file_submitter != ? and file_rating > ?', 0, 0 ), 'files DESC', isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, 'file_submitter' )->join( 'core_members', 'downloads_files.file_submitter=core_members.member_id' );
			${$time} = array();
			
			foreach( $select as $key => $values )
			{
				${$time}[$key]['member'] = \IPS\Member::constructFromData( $values );
				${$time}[$key]['files']  = $values['files'];
				${$time}[$key]['rating'] = $values['rating'];
			}
		}

		return $this->output( $week, $month, $year, $all );
	}
}
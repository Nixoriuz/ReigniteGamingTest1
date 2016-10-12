<?php
/**
 * @brief		Recent comments widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		25 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recent comments widget
 */
class _recentComments extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'recentComments';
	
	/**
	 * @brief	App
	 */
	public $app = 'gallery';
	
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
		$comments = \IPS\gallery\Image\Comment::getItemsWithPermission( array(), NULL, isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5 );

		return ( count( $comments ) ) ? $this->output( $comments ) : '';
	}
}
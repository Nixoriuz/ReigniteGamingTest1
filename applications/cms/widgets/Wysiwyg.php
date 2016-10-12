<?php
/**
 * @brief		WYSIWYG Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	content
 * @since		22 Aug 2014
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
 * WYSIWYG Widget
 */
class _Wysiwyg extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Wysiwyg';
	
	/**
	 * @brief	App
	 */
	public $app = 'cms';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Specify widget configuration
	 *
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
 		if ( $form === null )
 		{
	 		$form = new \IPS\Helpers\Form;
 		} 
 		
		$form->add( new \IPS\Helpers\Form\Editor( 'content', ( isset( $this->configuration['content'] ) ? $this->configuration['content'] : NULL ), FALSE, array(
			'app'			=> $this->app,
			'key'			=> 'Widgets',
			'autoSaveKey' 	=> 'widget-' . $this->uniqueKey,
			'attachIds'	 	=> isset( $this->configuration['content'] ) ? array( 0, 0, $this->uniqueKey ) : NULL
		) ) );
		
		return $form;
 	}

 	/**
 	 * Pre-save config method
 	 *
 	 * @param	array	$values		Form values
 	 * @return void
 	 */
 	public function preConfig( $values=array() )
 	{
	 	\IPS\File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, $this->uniqueKey );
	 	
	 	return $values;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		return $this->output( isset( $this->configuration['content'] ) ? $this->configuration['content'] : '' );
	}
}
<?php
/**
 * @brief		Database Widget
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
 * Database Widget
 */
class _Database extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Database';
	
	/**
	 * @brief	App
	 */
	public $app = 'cms';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * @brief	HTML if widget is called more than once, we store it.
	 */
	protected static $html = NULL;
	
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

 		$databases = array();
 		foreach( \IPS\cms\Databases::databases() as $db )
 		{
		    if ( ! $db->page_id )
		    {
			    $databases[ $db->id ] = $db->_title;
		    }
 		}

	    if ( ! count( $databases ) )
	    {
		    $form->addMessage('cms_err_no_databases_to_use');
	    }
 		else
	    {
			$form->add( new \IPS\Helpers\Form\Select( 'database', ( isset( $this->configuration['database'] ) ? $this->configuration['database'] : NULL ), FALSE, array( 'options' => $databases ) ) );
	    }

		return $form;
 	}

	/**
	 * Pre save
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		if ( \IPS\Request::i()->pageID and $values['database'] )
		{
			\IPS\cms\Pages\Page::load( \IPS\Request::i()->pageID )->mapToDatabase( $values['database'] );
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( self::$html === NULL )
		{
			if ( isset( $this->configuration['database'] ) )
			{
				try
				{
					$database = \IPS\cms\Databases::load( intval( $this->configuration['database'] ) );
					self::$html = \IPS\cms\Databases\Dispatcher::i()->setDatabase( $database->id )->run();
				}
				catch ( \OutOfRangeException $e )
				{
					self::$html = '';
				}
			}
			else
			{
				return '';
			}
		}
		
		return self::$html;
	}
}
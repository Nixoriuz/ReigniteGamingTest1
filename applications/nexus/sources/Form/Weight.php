<?php
/**
 * @brief		Weight input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		14 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Weight input class for Form Builder
 */
class _Weight extends \IPS\Helpers\Form\FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'unlimitedLang'		=> 'unlimited',	// Language string to use for unlimited checkbox label
	 		'round'				=> 2,			// Decimal points to round to
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'unlimitedLang'			=> NULL,
		'round'					=> 2,
	);

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$unit = \IPS\nexus\Shipping\Weight::bestUnit();				
		return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->weight( $this->name, $this->value ? round( $this->value->float( $unit ), $this->options['round'] ) : 0, $unit, $this->options['unlimitedLang'], $this->value === '*' );
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{
		$val = $this->value;
		if ( $val instanceof \IPS\nexus\Shipping\Weight )
		{
			return $val;
		}
		
		if ( $this->options['unlimitedLang'] and isset( $val['unlimited'] ) )
		{
			return '*';
		}
		if ( isset( $val['amount'] ) )
		{
			return new \IPS\nexus\Shipping\Weight( $val['amount'], $val['unit'] );
		}
		return NULL;
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		if ( $this->required and !$this->value->kilograms )
		{
			throw new \InvalidArgumentException('form_required');
		}
		return TRUE;
	}
}
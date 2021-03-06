<?php
/**
 * @brief		Weight Object
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		13 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Shipping;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Weight Object
 */
class _Weight
{
	/**
	 * @brief	Conversion rates
	 */
	public static $conversionRates = array(
		'kg'	=> 1,
		'lb'	=> 0.45359237,
		'oz'	=> 0.028349523125
	);
		
	/**
	 * @brief	Weight in kilograms (naturally we use kilograms as per le Syst�me international d'unit�s like good boys)
	 */
	public $kilograms = 0;
	
	/**
	 * Best display unit
	 *
	 * @param	\IPS\Member|NULL	$member	The member
	 * @return	string
	 */
	public static function bestUnit( \IPS\Member $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
			
		/* US uses lbs. This is a more reliable way to detect if locale
		   if US, as Windows and Nix use different locale keys, but both
		   will set the currency symbol to USD for US only */
		if ( trim( $member->language()->locale['int_curr_symbol'] ) === 'USD' )
		{
			return 'lb';
		}
		
		return 'kg';
	}
	
	/**
	 * Contructor
	 *
	 * @param	float	$weight	Weight
	 * @param	string	$unit	Unit
	 * @return	void
	 */
	public function __construct( $amount, $unit='kg' )
	{		
		$this->kilograms = ( $amount * static::$conversionRates[ $unit ] );
	}
	
	/**
	 * Get as float
	 *
	 * @param	$unit	Unit
	 * @return	float
	 */
	public function float( $unit='kg' )
	{
		return $this->kilograms / static::$conversionRates[ $unit ];
	}
	
	/**
	 * Get as string
	 *
	 * @param	string|NULL	$unit	Unit (NULL to autodetect)
	 * @param	int|NULL	$round	Decimal places to round to
	 * @return	float
	 */
	public function string( $unit=NULL, $round=2 )
	{
		if ( $unit === NULL )
		{
			$unit = static::bestUnit();
		}
			
		return round( $this->float( $unit ), $round ) . $unit;
	}
}
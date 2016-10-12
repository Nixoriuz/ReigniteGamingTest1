<?php
/**
 * @brief		Money Object
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		11 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Money Object
 */
class _Money
{	
	/**
	 * Get available currencies
	 *
	 * @return	array
	 */
	public static function currencies()
	{
		if ( $currencies = json_decode( \IPS\Settings::i()->nexus_currency, TRUE ) )
		{
			return array_keys( $currencies );
		}
		else
		{
			return array( \IPS\Settings::i()->nexus_currency );
		}
	}
		
	/**
	 * @brief	Amount
	 */
	public $amount;
	
	/**
	 * @brief	Currency
	 */
	public $currency;
	
	/**
	 * @brief	Number of decimal points
	 */
	protected $numberOfDecimalPlaces = 2;
	
	/**
	 * Contructor
	 *
	 * @param	float	$amount		Amount
	 * @param	string	$currency	Currency code
	 * @return	void
	 * @note	If you specify $1.005, this will be deliberately accepted. Call ->round() to round
	 */
	public function __construct( $amount, $currency )
	{
		$this->amount = floatval( $amount );
		$this->currency = $currency;
	}
	
	/**
	 * Round to the correct number of decimal places for the currency
	 *
	 * @param	bool	$alwaysUp	If TRUE, 0.051 would be rounded to 0.06 rather than 0.05
	 * @return	\IPS\nexus\Money
	 */
	public function round( $alwaysUp = TRUE )
	{
		if ( mb_strlen( mb_substr( mb_strrchr( $this->amount, '.' ), 1 ) ) > $this->numberOfDecimalPlaces )
		{
			if ( $alwaysUp )
			{
				$mult = pow( 10, $this->numberOfDecimalPlaces );
				$this->amount = ceil( $this->amount * $mult ) / $mult;
			}
			else
			{
				$this->amount = round( $this->amount, $this->numberOfDecimalPlaces );
			}
		}
		return $this;
	}
	
	/**
	 * Amount as string (not formatted, not locale aware)
	 * Used for gateways where, for example, (float) 10.5 is not acceptable, and (string) "10.50" is required
	 *
	 * @return	\IPS\nexus\Money
	 */
	public function amountAsString()
	{
		return sprintf( "%01." . $this->numberOfDecimalPlaces . "F", $this->amount );
	}
	
	/**
	 * To string
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->toString( \IPS\Member::loggedIn()->language() );
	}
	
	/**
	 * To string for language
	 *
	 * @return	string
	 */
	public function toString( \IPS\Lang $language )
	{		
		/* If the server has intl installed, use that */
		if ( function_exists( 'numfmt_format_currency' ) )
		{						
			$format = numfmt_create( $language->short, \NumberFormatter::CURRENCY );
			if ( $return = numfmt_format_currency( $format, $this->amount, $this->currency ) and $return !== 'NaN' )
			{
				return $return;
			}
		}
		
		/* If this currency matches the locale the user is using, and money_format is supported (Windows doesn't support it), use that */
		if ( function_exists( 'money_format' ) and trim( $language->locale['int_curr_symbol'] ) === $this->currency )
		{
			return money_format( '%n', $this->amount );
		}
				
		/* If it matches any of the installed languages, we can do something with the locale data */
		foreach ( \IPS\Lang::languages() as $lang )
		{
			if ( isset( $lang->locale['int_curr_symbol'] ) AND trim( $lang->locale['int_curr_symbol'] ) === $this->currency )
			{
				$currencySymbol = $lang->locale['currency_symbol'];
				$currencySymbolPreceeds = ( $this->amount >= 0 ) ? $lang->locale['p_cs_precedes'] : $lang->locale['n_cs_precedes'];
				$spaceBetweenCurrencySymbolAndAmount = ( ( $this->amount >= 0 ) ? $lang->locale['p_sep_by_space'] : $lang->locale['n_sep_by_space'] ) ? ' ' : '';
				$amount = number_format( $this->amount, $lang->locale['frac_digits'] );
				
				$positiveNegativeSymbol = ( $this->amount >= 0 ) ? $lang->locale['positive_sign'] : $lang->locale['negative_sign'];
				if ( $positiveNegativeSymbol )
				{
					$positiveNegativeSymbolFormat = ( $this->amount >= 0 ) ? $lang->locale['p_sign_posn'] : $lang->locale['n_sign_posn'];
					for ( $i=0; $i < \strlen( $positiveNegativeSymbolFormat ); $i++ )
					{
						switch ( $positiveNegativeSymbolFormat[ $i ] )
						{
							case 0:
								if ( $currencySymbolPreceeds )
								{
									$currencySymbol = "({$currencySymbol}";
									$amount = "{$amount})";
								}
								else
								{
									$currencySymbol = "{$currencySymbol})";
									$amount = "({$amount}";
								}
								break;
							case 1:
								if ( $currencySymbolPreceeds )
								{
									$currencySymbol = "{$positiveNegativeSymbol}{$currencySymbol}";
								}
								else
								{
									$amount = "{$positiveNegativeSymbol}{$amount}";
								}
								break;
							case 2:
								if ( $currencySymbolPreceeds )
								{
									$amount = "{$amount}{$positiveNegativeSymbol}";
								}
								else
								{
									$currencySymbol = "{$currencySymbol}{$positiveNegativeSymbol}";
								}
								break;
							case 3:
								$currencySymbol = ( $positiveNegativeSymbol . $currencySymbol );
								break;
							case 4:
								$currencySymbol = ( $currencySymbol . $positiveNegativeSymbol );
								break;
						}
					}
				}
				
				return ( $currencySymbolPreceeds ? ( $currencySymbol . $spaceBetweenCurrencySymbolAndAmount . $amount ) : ( $amount . $space . $currencySymbol ) );
			}
		}

		/* And if all else fails, just use the currency code */
		return number_format( $this->amount, 2 ) . " {$this->currency}";
	}
}
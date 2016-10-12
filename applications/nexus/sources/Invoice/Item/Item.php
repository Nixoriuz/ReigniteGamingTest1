<?php
/**
 * @brief		Invoice Abstract Item Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Invoice;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Abstract Item Interface
 */
abstract class _Item
{
	/**
	 * @brief	Can use coupons?
	 */
	public static $canUseCoupons = TRUE;
	
	/**
	 * @brief	Can use account credit?
	 */
	public static $canUseAccountCredit = TRUE;
	
	/**
	 * @brief	string	Name
	 */
	public $name;
	
	/**
	 * @brief	int	Quantity
	 */
	public $quantity = 1;
	
	/**
	 * @brief	\IPS\nexus\Money	Price
	 */
	public $price;
	
	/**
	 * @brief	int|NULL	ID
	 */
	public $id;
	
	/**
	 * @brief	\IPS\nexus\Tax		Tax Class
	 */
	public $tax;
	
	/**
	 * @brief	Payment Methods IDs
	 */
	public $paymentMethodIds;
	
	/**
	 * @brief	Key/Value array of extra details
	 */
	public $details = array();
	
	/**
	 * @brief	Physical?
	 */
	public $physical = FALSE;
	
	/**
	 * @brief	Shipping Methods IDs
	 */
	public $shippingMethodIds;
	
	/**
	 * @brief	Chosen Shipping Methods ID
	 */
	public $chosenShippingMethodId;
	
	/**
	 * @brief	Weight
	 */
	public $weight;
	
	/**
	 * @brief	Length
	 */
	public $length;
	
	/**
	 * @brief	Width
	 */
	public $width;
	
	/**
	 * @brief	Height
	 */
	public $height;
		
	/**
	 * @brief	Pay To member
	 */
	public $payTo;
	
	/**
	 * @brief	Commission percentage
	 */
	public $commission = 0;
	
	/**
	 * @brief	Commission fee
	 */
	public $fee = 0;
	
	/**
	 * @brief	Extra
	 */
	public $extra;
	
	/**
	 * Constructor
	 *
	 * @param	string				$name	Name
	 * @param	\IPS\nexus\Money	$price	Price
	 * @return	void
	 */
	public function __construct( $name, \IPS\nexus\Money $price )
	{
		$this->name = $name;
		$this->price = $price->round();
	}
	
	/**
	 * Get (can be used to override static properties like icon and title in an instance)
	 *
	 * @param	string	$k	Property
	 * @return	mixed
	 */
	public function __get( $k )
	{
		$k = mb_substr( $k, 1 );
		return static::$$k;
	}
	
	/**
	 * Get line price without tax
	 *
	 * @return	\IPS\nexus\Money
	 */
	public function linePrice()
	{
		return new \IPS\nexus\Money( $this->price->amount * $this->quantity, $this->price->currency );
	}
	
	/**
	 * Get tax rate
	 *
	 * @return	float
	 */
	public function taxRate()
	{
		return ( $this->tax ? $this->tax->rate( $this->billaddress ) : 0 );
	}
	
	/**
	 * Get line price with tax
	 *
	 * @return	\IPS\nexus\Money
	 */
	public function grossLinePrice()
	{
		return new \IPS\nexus\Money( $this->linePrice()->amount + ( $this->linePrice()->amount * $this->taxRate() ), $this->price->currency );
	}
	
	/**
	 * Get recipient amounts
	 *
	 * @return	array
	 */
	public function recipientAmounts()
	{
		$return = array();
		
		if ( $this->payTo )
		{
			$linePrice = $this->linePrice();
			$currency = $linePrice->currency;
			
			$commission = ( new \IPS\nexus\Money( ( $this->price->amount / 100 ) * $this->commission, $currency ) );
			$return['site_commission_unit'] = $commission->round();
			$return['site_commission_line'] = new \IPS\nexus\Money( $return['site_commission_unit']->amount * $this->quantity, $currency );
			
			$return['recipient_unit'] = new \IPS\nexus\Money( $this->price->amount - $return['site_commission_unit']->amount, $currency );
			$return['recipient_line'] = new \IPS\nexus\Money( $linePrice->amount - $return['site_commission_line']->amount, $currency );
			
			$return['site_total'] = new \IPS\nexus\Money( $return['site_commission_line']->amount + ( $this->fee ? $this->fee->amount : 0 ), $currency );
			$return['recipient_final'] = new \IPS\nexus\Money( $linePrice->amount - $return['site_total']->amount, $currency );
			if ( $return['recipient_final']->amount < 0 )
			{
				$return['recipient_final']->amount = 0;
			}
		}
		else
		{
			$return['site_total'] = $this->linePrice()->amount;
		}
		
		return $return;
	}
	
	/**
	 * Get amount for recipient (on line price)
	 *
	 * @return	\IPS\nexus\Money
	 * @throws	\BadMethodCallException
	 */
	public function amountForRecipient()
	{
		if ( !$this->payTo )
		{
			throw new \BadMethodCallException;
		}
		
		$linePrice = $this->linePrice();
		$amountForSite = ( $linePrice->amount / 100 ) * $this->commission;
		$amountForRecipient = ( new \IPS\nexus\Money( $linePrice->amount - $amountForSite, $linePrice->currency ) );
		return $amountForRecipient->round();
	}
	
	/**
	 * Image
	 *
	 * @return |IPS\File|NULL
	 */
	public function image()
	{
		return NULL;
	}
	
	/**
	 * On Paid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPaid( \IPS\nexus\Invoice $invoice )
	{
		
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( \IPS\nexus\Invoice $invoice )
	{
		return array();
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return	void
	 */
	public function onUnpaid( \IPS\nexus\Invoice $invoice, $status )
	{
		
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onInvoiceCancel( \IPS\nexus\Invoice $invoice )
	{
		
	}
	
	/**
	 * Client Area URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function url()
	{
		return NULL;
	}
	
	/**
	 * ACP URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function acpUrl()
	{
		return NULL;
	}
}
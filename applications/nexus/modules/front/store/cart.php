<?php
/**
 * @brief		View Cart
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View Cart
 */
class _cart extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store.css', 'nexus' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store_responsive.css', 'nexus', 'front' ) );
		}
		
		parent::execute();
	}
	
	/**
	 * View Cart
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init */
		if ( !isset( $_SESSION['cart'] ) )
		{
			$_SESSION['cart'] = array();
		}
		$currency = isset( $_SESSION['currency'] ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency();

		/* Display */
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('your_cart');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->cart( ( isset( $_SESSION['location'] ) and $_SESSION['location'] ) ? \IPS\GeoLocation::buildFromJson( $_SESSION['location'] ) : NULL, $currency );
	}
	
	/**
	 * Update Quantities
	 *
	 * @return	void
	 */
	protected function quantities()
	{
		\IPS\Session::i()->csrfCheck();
		
		foreach ( \IPS\Request::i()->item as $k => $v )
		{
			/* Get item */			
			$item = $_SESSION['cart'][ $k ];
			$package = \IPS\nexus\Package::load( $item->id );
			
			/* Are any others just a duplicate for discount purposes? If so, condense them into this one */
			foreach ( $_SESSION['cart'] as $_k => $_item )
			{
				if ( $_k != $k and $_item->id == $package->id )
				{
					$cloned = clone $_item;
					$cloned->quantity = $item->quantity;
					$cloned->price = $item->price;
					if ( $cloned == $item )
					{
						$v += $_item->quantity;
						unset( $_SESSION['cart'][ $_k ] );
					}
				}
			}

			/* Subscriptions can only have 1 */			
			if ( $package->subscription and $v > 1 )
			{
				\IPS\Output::i()->error( 'err_subscription_qty', '1X214/4', 403, '' );
			}
			
			/* Set the quantity back to 0 and "re-add" the item */
			$item->quantity = 0;
			if ( $v )
			{				
				$data = $package->optionValuesStockAndPrice( $package->optionValues( $item->details ) );
				if ( $data['stock'] != -1 and ( $data['stock'] - $item->quantity ) < $v )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'not_enough_in_stock', FALSE, array( 'sprintf' => array( $data['stock'] - $item->quantity ) ) ), '1X214/3', 403, '' );
				}
				
				$package->addItemsToCartData( $item->details, $v, $item->renewalTerm, $item->parent );
			}
			else
			{
				foreach ( $_SESSION['cart'] as $k2 => $_item )
				{
					if ( $_item->parent === $k )
					{
						unset( $_SESSION['cart'][ $k2 ] );
					}
				}
			}
			
			/* And if the quantity is 0, remove it */
			if ( $_SESSION['cart'][ $k ]->quantity == 0 )
			{
				unset( $_SESSION['cart'][ $k ] );
			}
		}
		
		if ( empty( $_SESSION['cart'] ) and \IPS\CACHE_PAGE_TIMEOUT and !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Request::i()->setCookie( 'noCache', 0, \IPS\DateTime::ts( time() - 86400 ) );
		}
			
		if ( \IPS\Request::i()->isAjax() )
		{
			$currency = isset( $_SESSION['currency'] ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency();
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate('store')->cartContents( ( isset( $_SESSION['location'] ) and $_SESSION['location'] ) ? \IPS\GeoLocation::buildFromJson( $_SESSION['location'] ) : NULL, $currency ), 200, 'text/html' );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=store&controller=cart', 'front', 'store_cart' ) );
		}
	}
	
	/**
	 * Empty Cart
	 *
	 * @return	void
	 */
	protected function clear()
	{
		\IPS\Session::i()->csrfCheck();
		
		$_SESSION['cart'] = array();
		
		if ( \IPS\CACHE_PAGE_TIMEOUT and !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Request::i()->setCookie( 'noCache', 0, \IPS\DateTime::ts( time() - 86400 ) );
		}
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( $_SESSION['cart'] );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=store&controller=cart', 'front', 'store_cart' ) );
		}
	}
	
	/**
	 * Checkout
	 *
	 * @return	void
	 */
	protected function checkout()
	{
		\IPS\Session::i()->csrfCheck();
		
		$currency = isset( $_SESSION['currency'] ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency();
		
		$canRegister = ( !\IPS\Settings::i()->nexus_reg_force or \IPS\Member::loggedIn()->member_id );
		
		$invoice = new \IPS\nexus\Invoice;
		$invoice->member = \IPS\nexus\Customer::loggedIn();
		$invoice->currency = $currency;
		foreach ( $_SESSION['cart'] as $item )
		{
			if ( !$canRegister and \IPS\nexus\Package::load( $item->id )->reg )
			{
				$canRegister = TRUE;
			}
						
			$invoice->addItem( $item );
		}
		
		if ( !count( $invoice->items ) )
		{
			\IPS\Output::i()->error( 'your_cart_empty', '2X214/2', 403, '' );
		}
		
		if ( !$canRegister )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=store&controller=store&do=register', 'front', 'store' ) );
		}
				
		if ( $minimumOrderAmounts = json_decode( \IPS\Settings::i()->nexus_minimum_order, TRUE ) and $minimumOrderAmounts[ $currency ]['amount'] > $invoice->total->amount )
		{
			\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'err_minimum_order', FALSE, array( 'sprintf' => array( new \IPS\nexus\Money( $minimumOrderAmounts[ $currency ]['amount'], $currency ) ) ) ), '1X214/1', 403, '' );
		}
				
		$_SESSION['cart'] = array();
		if ( \IPS\CACHE_PAGE_TIMEOUT and !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Request::i()->setCookie( 'noCache', 0, \IPS\DateTime::ts( time() - 86400 ) );
		}
		\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
	}
}
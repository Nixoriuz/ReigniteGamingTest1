<?php
/**
 * @brief		Nexus Application Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
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
 * Nexus Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init - catches legacy PayPal IPN messages
	 *
	 * @return	void
	 */
	public function init()
	{
		if ( \IPS\Request::i()->app == 'nexus' and \IPS\Request::i()->module == 'payments' and \IPS\Request::i()->section == 'receive' and \IPS\Request::i()->validate == 'paypal' )
		{
			if ( \IPS\Request::i()->txn_type == 'subscr_payment' and \IPS\Request::i()->payment_status == 'Completed' )
			{
				try
				{
					/* Get the subscription */
					$subscription = \IPS\Db::i()->select( '*', 'nexus_subscriptions', array( 's_gateway_key=? AND s_id=?', 'paypal', \IPS\Request::i()->subscr_id ) )->first();
					$items = \unserialize( $subscription['s_items'] );
					if ( !is_array( $items ) or empty( $items ) )
					{
						exit;
					}
					
					/* If the gateway still exists, fetch it */
					$gateway = NULL;
					try
					{
						$gateway = \IPS\nexus\Gateway::load( $subscription['s_method'] );
					}
					catch ( \OutOfRangeException $e ) { }
										
					/* Check this waas actually a PayPal IPN message */					
					try
					{
						$response = \IPS\Http\Url::external( 'https://www.' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr/' )->request()->setHeaders( array( 'Accept' => 'application/x-www-form-urlencoded' ) )->post( array_merge( array( 'cmd' => '_notify-validate' ), $_POST ) );
						if ( (string) $response !== 'VERIFIED' )
						{
							exit;
						}
					}
					catch ( \Exception $e )
					{
						exit;
					}
					
					/* Has an invoice already been generated? */
					$_items = $items;
					try
					{
						$invoice = \IPS\nexus\Invoice::constructFromData( \IPS\Db::i()->select( '*', 'nexus_invoices', array( 'i_member=? AND i_status=?', $subscription['s_member'], 'pend' ), 'i_date DESC', 1 )->first() );
						foreach ( $invoice->items as $item )
						{
							if ( $item instanceof \IPS\nexus\Invoice\Item\Renewal and in_array( $item->id, $_items ) )
							{
								unset( $_items[ array_search( $item->id, $_items ) ] );
							}
						}
					}
					catch ( \UnderflowException $e ) { }
					
					/* No, create one */
					if ( count( $_items ) )
					{
						$invoice = new \IPS\nexus\Invoice;
						$invoice->member = \IPS\nexus\Customer::load( $subscription['s_member'] );
						foreach ( $items as $purchaseId )
						{
							try
							{
								$purchase = \IPS\nexus\Purchase::load( $purchaseId );
								if ( !$purchase->cancelled )
								{
									$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase ) );
								}
							}
							catch ( \OutOfRangeException $e ) { }
						}
						if ( !count( $invoice->items ) )
						{
							exit;
						}
						$invoice->save();
					}
					
					/* And then log a transaction */
					$transaction = new \IPS\nexus\Transaction;
					$transaction->member = $invoice->member;
					$transaction->invoice = $invoice;
					$transaction->method = $gateway;
					$transaction->amount = new \IPS\nexus\Money( $_POST['mc_gross'], $_POST['mc_currency'] );
					$transaction->gw_id = $_POST['txn_id'];
					$transaction->approve();
				}
				catch ( \UnderflowException $e ) { }
			}
			
			exit;
		}
	}
		
	/**
	 * ACP Menu Numbers
	 *
	 * @param	array	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( $queryString )
	{
		parse_str( $queryString, $queryString );
		switch ( $queryString['controller'] )
		{
			case 'transactions':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_status=?', \IPS\nexus\Transaction::STATUS_HELD ) )->first();
				break;
			
			case 'shipping':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_ship_orders', array( 'o_status=?', \IPS\nexus\Shipping\Order::STATUS_PENDING ) )->first();
				break;
			
			case 'payouts':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', \IPS\nexus\Payout::STATUS_PENDING ) )->first();
				break;
			
			case 'requests':
				$myFilters = \IPS\nexus\Support\Request::myFilters();
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' ), $myFilters['whereClause'] ) )->join( 'nexus_support_departments', 'dpt_id=r_department' )->first();
				break;
				
			case 'errors':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_hosting_errors' )->first();
				break;
		}
	}
	
	/**
	 * Cart count
	 *
	 * @return	int
	 */
	public static function cartCount()
	{
		$count = 0;
		foreach ( $_SESSION['cart'] as $item )
		{
			$count += $item->quantity;
		}
		return $count;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'shopping-cart';
	}
}
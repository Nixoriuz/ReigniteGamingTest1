<?php
/**
 * @brief		PayPal Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Gateway
 */
class _PayPal extends \IPS\nexus\Gateway
{
	/* !Features */
	
	const SUPPORTS_REFUNDS = TRUE;
	const SUPPORTS_PARTIAL_REFUNDS = TRUE;
	
	/**
	 * Check the gateway can process this...
	 *
	 * @param	$amount			\IPS\nexus\Money	The amount
	 * @param	$billingAddress	\IPS\GeoLocation	The billing address
	 * @return	bool
	 * @see		<a href="https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/">PayPal REST API Country & Currency Support</a>
	 */
	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress )
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( $settings['type'] === 'card' )
		{
			if ( !in_array( $amount->currency, array( 'USD', 'GBP', 'CAD', 'EUR', 'JPY' ) ) )
			{
				return FALSE;
			}
		}
		
		switch ( $amount->currency )
		{
			case 'AUD':
				if ( $amount->amount >= 12500 )
				{
					return FALSE;
				}
				break;
			case 'BRL':
				if ( $amount->amount >= 20000 )
				{
					return FALSE;
				}
				break;
			case 'CAD':
				if ( $amount->amount >= 12500 )
				{
					return FALSE;
				}
				break;
			case 'CZK':
				if ( $amount->amount >= 240000 )
				{
					return FALSE;
				}
				break;
			case 'DKK':
				if ( $amount->amount >= 60000 )
				{
					return FALSE;
				}
				break;
			case 'EUR':
				if ( $amount->amount >= 8000 )
				{
					return FALSE;
				}
				break;
			case 'HKD':
				if ( $amount->amount >= 80000 )
				{
					return FALSE;
				}
				break;
			case 'HUF':
				if ( $amount->amount >= 2000000 )
				{
					return FALSE;
				}
				break;
			case 'ILS':
				if ( $amount->amount >= 40000  )
				{
					return FALSE;
				}
				break;
			case 'JPY':
				if ( $amount->amount >= 1000000 )
				{
					return FALSE;
				}
				break;
			case 'MYR':
				if ( $amount->amount >= 40000 )
				{
					return FALSE;
				}
				break;
			case 'MXN':
				if ( $amount->amount >= 110000 )
				{
					return FALSE;
				}
				break;
			case 'TWD':
				if ( $amount->amount >= 330000 )
				{
					return FALSE;
				}
				break;
			case 'NZD':
				if ( $amount->amount >= 15000 )
				{
					return FALSE;
				}
				break;
			case 'NOK':
				if ( $amount->amount >= 70000 )
				{
					return FALSE;
				}
				break;
			case 'PHP':
				if ( $amount->amount >= 500000 )
				{
					return FALSE;
				}
				break;
			case 'PLN':
				if ( $amount->amount >= 32000 )
				{
					return FALSE;
				}
				break;
			case 'GBP':
				if ( $amount->amount >= 5500 )
				{
					return FALSE;
				}
				break;
			case 'SGD':
				if ( $amount->amount >= 16000 )
				{
					return FALSE;
				}
				break;
			case 'SEK':
				if ( $amount->amount >= 80000 )
				{
					return FALSE;
				}
				break;
			case 'CHF':
				if ( $amount->amount >= 13000 )
				{
					return FALSE;
				}
				break;
			case 'THB':
				if ( $amount->amount >= 360000 )
				{
					return FALSE;
				}
				break;
			case 'TRY':
				if ( $amount->amount >= 25000 )
				{
					return FALSE;
				}
				break;
			case 'USD':
				if ( $amount->amount >= 10000 )
				{
					return FALSE;
				}
				break;
			default:
				return FALSE;	
		}
		
		return parent::checkValidity( $amount, $billingAddress );
	}
	
	/**
	 * Can store cards?
	 *
	 * @return	bool
	 */
	public function canStoreCards()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( isset( $settings['type'] ) and $settings['type'] === 'card' and $settings['vault'] );
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @return	bool
	 */
	public function canAdminCharge()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( isset( $settings['type'] ) and $settings['type'] === 'card' );
	}
		
	/* !Payment Gateway */
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	Invoice
	 * @param	\IPS\nexus\Money	$amount		The amount to pay now
	 * @return	array
	 */
	public function paymentScreen( \IPS\nexus\Invoice $invoice, \IPS\nexus\Money $amount )
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( $settings['type'] === 'card' )
		{
			return array( 'card' => new \IPS\nexus\Form\CreditCard( $this->id . '_card', NULL, FALSE, array(
				'types' => array( \IPS\nexus\CreditCard::TYPE_VISA, \IPS\nexus\CreditCard::TYPE_MASTERCARD, \IPS\nexus\CreditCard::TYPE_DISCOVER, \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS ),
				'save'	=> ( $settings['vault'] and \IPS\Member::loggedIn()->member_id ) ? $this : NULL
			) ) );
		}
		return array();
	}
	
	/**
	 * Authorize
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	array|\IPS\nexus\Customer\CreditCard	$values			Values from form OR a stored card object if this gateway supports them
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @return	\IPS\DateTime|NULL		Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException			Message will be displayed to user
	 */
	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL )
	{
		$settings = json_decode( $this->settings, TRUE );
				
		/* Build data */
		if ( $settings['type'] === 'card' )
		{
			$card = is_array( $values ) ? $values[ $this->id . '_card' ] : $values;
			
			if ( $card instanceof \IPS\nexus\Customer\CreditCard )
			{
				$payer = array(
					'payment_method'		=> 'credit_card',
					'funding_instruments'	=> array(
						array(
							'credit_card_token'	=> array(
								'credit_card_id'	=> $card->data
							)
						)
					)
				);
			}
			else
			{
				if ( $maxMind )
				{
					$maxMind->setCard( $card );
				}
				
				switch ( $card->type )
				{
					case \IPS\nexus\CreditCard::TYPE_VISA:
						$cardType = 'visa';
						break;
					case \IPS\nexus\CreditCard::TYPE_MASTERCARD:
						$cardType = 'mastercard';
						break;
					case \IPS\nexus\CreditCard::TYPE_DISCOVER:
						$cardType = 'discover';
						break;
					case \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS:
						$cardType = 'amex';
						break;
				}
				
				/* PayPal requires short codes for states */
				$billingState = $transaction->invoice->billaddress->region;
				if ( isset( \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->billaddress->country ] ) )
				{
					if ( !array_key_exists( $billingState, \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->billaddress->country ] ) )
					{
						$_billingState = array_search( $transaction->invoice->billaddress->region, \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->billaddress->country ] );
						if ( $_billingState !== FALSE )
						{
							$billingState = $_billingState;
						}
					}
				}
				
				$billingAddress = array(
					'line1'				=> $transaction->invoice->billaddress->addressLines[0],
					'line2'				=> isset( $transaction->invoice->billaddress->addressLines[1] ) ? $transaction->invoice->billaddress->addressLines[1] : '',
					'city'				=> $transaction->invoice->billaddress->city,
					'country_code'		=> $transaction->invoice->billaddress->country,
					'postal_code'		=> $transaction->invoice->billaddress->postalCode,
					'state'				=> $billingState,
				);
				if ( $transaction->member->cm_phone )
				{
					$billingAddress['phone'] = preg_replace( '/[^\+0-9\s]/', '', $transaction->member->cm_phone );
				}
				
				$payer = array(
					'payment_method'		=> 'credit_card',
					'funding_instruments'	=> array(
						array(
							'credit_card'		=> array(
								'number'			=> $card->number,
								'type'				=> $cardType,
								'expire_month'		=> intval( $card->expMonth ),
								'expire_year'		=> intval( $card->expYear ),
								'cvv2'				=> intval( $card->ccv ),
								'first_name'		=> $transaction->member->cm_first_name,
								'last_name'			=> $transaction->member->cm_last_name,
								'billing_address'	=> $billingAddress
							)
						),
					)
				);
			}
		}
		else
		{			
			$payer = array( 'payment_method' => 'paypal' );
		}
		
		/* We need a transaction ID */
		$transaction->save();
		
		/* Item list */
		$payPalTransactionData = array(
			'amount'	=> array(
				'currency'	=> $transaction->amount->currency,
				'total'		=> $transaction->amount->amountAsString(),
			),
			'invoice_number'=> $transaction->id,
		);
		if ( $transaction->amount->amount == $transaction->invoice->total->amount )
		{
			$summary = $transaction->invoice->summary();
			$payPalTransactionData['amount']['details'] = array(
				'shipping'	=> $summary['shippingTotal']->amountAsString(),
				'subtotal'	=> $summary['subtotal']->amountAsString(),
				'tax'		=> $summary['taxTotal']->amountAsString(),
			);

			$payPalTransactionData['item_list'] = array( 'items' => array() );
			foreach ( $summary['items'] as $item )
			{
				$payPalTransactionData['item_list']['items'][] = array(
					'quantity'	=> $item->quantity,
					'name'		=> $item->name,
					'price'		=> $item->price->amountAsString(),
					'currency'	=> $transaction->amount->currency,
				);
			}

			if ( $transaction->invoice->shipaddress )
			{
				/* PayPal requires shortcodes for states */
				$shippingState = $transaction->invoice->shipaddress->region;
				if ( isset( \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->shipaddress->country ] ) )
				{
					if ( !array_key_exists( $shippingState, \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->shipaddress->country ] ) )
					{
						$_shippingState = array_search( $transaction->invoice->shipaddress->region, \IPS\nexus\Customer\Address::$stateCodes[ $transaction->invoice->shipaddress->country ] );
						if ( $_shippingState !== FALSE )
						{
							$shippingState = $_shippingState;
						}
					}
				}
				
				$payPalTransactionData['item_list']['shipping_address']	= array(
					'recipient_name'	=> $transaction->invoice->member->cm_name,
					'line1'				=> $transaction->invoice->shipaddress->addressLines[0],
					'line2'				=> isset( $transaction->invoice->shipaddress->addressLines[1] ) ? $transaction->invoice->shipaddress->addressLines[1] : '',
					'city'				=> $transaction->invoice->shipaddress->city,
					'country_code'		=> $transaction->invoice->shipaddress->country,
					'postal_code'		=> $transaction->invoice->shipaddress->postalCode,
					'state'				=> $shippingState,
				);
				if ( $transaction->member->cm_phone )
				{
					$payPalTransactionData['item_list']['shipping_address']['phone'] = preg_replace( '/[^\+0-9\s]/', '', $transaction->member->cm_phone );
				}
			}
		}
		else
		{
			$payPalTransactionData['description'] = sprintf( $transaction->member->language()->get('partial_payment_desc'), $transaction->invoice->id );
		}
				
		/* Send the request */
		$response = $this->api( 'payments/payment', array(
			'intent'		=> 'authorize',
			'payer'			=> $payer,
			'transactions'	=> array( $payPalTransactionData ),
			'redirect_urls'	=> array(
				'return_url'	=> \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/paypal.php?nexusTransactionId=' . $transaction->id,
				'cancel_url'	=> (string) $transaction->invoice->checkoutUrl(),
			)
		) );		
		
		$transaction->gw_id = $response['id'];
		
		/* If this is a PayPal payment (opposed to credit card) we need to get approval from the user */
		if ( $settings['type'] == 'paypal' )
		{
			$transaction->save();
			foreach ( $response['links'] as $link )
			{
				if ( $link['rel'] === 'approval_url' )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::external( $link['href'] ) );
				}
			}
			throw new \RuntimeException;
		}
		/* Otherwise, return how long we've got */
		else
		{
			/* Save the card first if the user wants */
			if ( $card->save )
			{
				try
				{
					$storedCard = new \IPS\nexus\Gateway\PayPal\CreditCard;
					$storedCard->member = $transaction->member;
					$storedCard->method = $this;
					$storedCard->card = $card;
					$storedCard->save();
				}
				catch ( \Exception $e ) { }
			}
			
			/* And return */
			return \IPS\DateTime::ts( strtotime( $response['transactions'][0]['related_resources'][0]['authorization']['valid_until'] ) );
		}
	}
	
	/**
	 * Void
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\Exception
	 */
	public function void( \IPS\nexus\Transaction $transaction )
	{
		$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );
		foreach ( $payment['transactions'][0]['related_resources'] as $rr )
		{
			if ( isset( $rr['authorization'] ) )
			{
				return $this->api( "payments/authorization/{$rr['authorization']['id']}/void" );
			}
		}
		
		throw new \RuntimeException;
	}
		
	/**
	 * Capture
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture( \IPS\nexus\Transaction $transaction )
	{
		$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );
		foreach ( $payment['transactions'][0]['related_resources'] as $rr )
		{
			if ( isset( $rr['authorization'] ) )
			{
				try
				{
					$response = $this->api( "payments/authorization/{$rr['authorization']['id']}/capture", array(
						'amount'			=> array(
							'currency'			=> $transaction->amount->currency,
							'total'				=> $transaction->amount->amountAsString(),
						),
						'is_final_capture'	=> TRUE,
					) );
				}
				catch ( \IPS\nexus\Gateway\PayPal\Exception $e )
				{
					if ( $e->getName() == 'AUTHORIZATION_ALREADY_COMPLETED' )
					{
						return TRUE;
					}
					throw $e;
				}
				
				return TRUE;
			}
		}
				
		throw new \RuntimeException;
	}
		
	/**
	 * Refund
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction to be refunded
	 * @param	float|NULL				$amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @return	mixed									Gateway reference ID for refund, if applicable
	 * @throws	\Exception
 	 */
	public function refund( \IPS\nexus\Transaction $transaction, $amount = NULL )
	{
		$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );		
		foreach ( $payment['transactions'][0]['related_resources'] as $rr )
		{
			if ( isset( $rr['capture'] ) )
			{
				$amount = $amount ? new \IPS\nexus\Money( $amount, $transaction->currency ) : $transaction->amount;
				$response = $this->api( "payments/capture/{$rr['capture']['id']}/refund", array( 'amount' => array(
					'currency'	=> $amount->currency,
					'total'		=> $amount->amountAsString()
				) ) );
				return $response['id'];
			}
		}
		
		throw new \RuntimeException;
	}
	
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function settings( &$form )
	{
		$settings = json_decode( $this->settings, TRUE );
		
		$form->add( new \IPS\Helpers\Form\Radio( 'paypal_type', $settings['type'], TRUE, array( 'options' => array( 'paypal' => 'paypal_type_paypal', 'card' => 'paypal_type_card' ), 'toggles' => array( 'card' => array( 'paypal_vault' ) ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'paypal_vault', $this->id ? $settings['vault'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'paypal_vault' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'paypal_client_id', $settings['client_id'], TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'paypal_secret', $settings['secret'], TRUE ) );
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$settings	Settings
	 * @return	array
	 * @throws	\InvalidArgumentException
	 */
	public function testSettings( $settings )
	{
		try
		{
			$this->getNewToken( $settings );
		}
		catch ( \Exception $e )
		{
			throw new \InvalidArgumentException( $e->getMessage(), $e->getCode() );
		}
		
		return $settings;
	}
	
	/* !Utility Methods */
	
	/**
	 * Send API Request
	 *
	 * @param	string	$uri	The API to request (e.g. "payments/payment")
	 * @param	array	$data	The data to send
	 * @param	string	$method	Method (get/post)
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function api( $uri, $data=NULL, $method='post' )
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( !isset( $settings['token'] ) or $settings['token_expire'] < time() )
		{
			$token = $this->getNewToken();
			$settings['token'] = $token['access_token'];
			$settings['token_expire'] = ( time() + $token['expires_in'] );
			$this->settings = json_encode( $settings );
			$this->save();
		}
		
		$response = \IPS\Http\Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/' . $uri )
			->request( 30 )
			->setHeaders( array(
				'Content-Type'	=> 'application/json',
				'Authorization'	=> "Bearer {$settings['token']}",
			) )
			->$method( $data === NULL ? NULL : json_encode( $data ) );
		
		if ( mb_substr( $response->httpResponseCode, 0, 1 ) !== '2' )
		{
			throw new \IPS\nexus\Gateway\PayPal\Exception( $response );
		}
		
		return $method === 'delete' ? NULL : $response->decodeJson();
	}
	
	/**
	 * Get Token
	 *
	 * @param	array|NULL	$settings	Settings (NULL for saved setting)
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 * @throws	\UnexpectedValueException
	 */
	protected function getNewToken( $settings = NULL )
	{
		$settings = $settings ?: json_decode( $this->settings, TRUE );
				
		$response = \IPS\Http\Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/oauth2/token' )
			->request()
			->setHeaders( array(
				'Accept'			=> 'application/json',
				'Accept-Language'	=> 'en_US',
			) )
			->login( $settings['client_id'], $settings['secret'] )
			->post( array( 'grant_type' => 'client_credentials' ) )
			->decodeJson();
			
		if ( !isset( $response['access_token'] ) )
		{
			throw new \UnexpectedValueException( isset( $response['error_description'] ) ? $response['error_description'] : $response );
		}

		return $response;
	}
}
<?php
/**
 * @brief		Renewal Term Object
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		13 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Purchase;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Renewal Term Object
 */
class _RenewalTerm
{
	/**
	 * @brief	Cost
	 */
	public $cost;
	
	/**
	 * @brief	Interval
	 */
	public $interval;
	
	/**
	 * @brief	Tax
	 */
	public $tax;
	
	/**
	 * @brief	Add to base price?
	 */
	public $addToBase = FALSE;
	
	/**
	 * @brief	Grace period
	 */
	public $gracePeriod;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\nexus\Money|array	$cost			Cost
	 * @param	\DateInterval			$interval		Interval
	 * @param	\IPS\nexus\Tax|NULL		$tax			Tax
	 * @param	bool					$addToBase		Add to base?
	 * @param	\DateInterval|NULL		$gracePeriod	Grace period
	 * @return	void
	 */ 
	public function __construct( $cost, \DateInterval $interval, \IPS\nexus\Tax $tax = NULL, $addToBase = FALSE, \DateInterval $gracePeriod = NULL )
	{
		$this->cost = $cost;
		$this->interval = $interval;
		$this->tax = $tax;
		$this->addToBase = $addToBase;
		$this->gracePeriod = $gracePeriod;
	}
	
	/**
	 * Get term
	 *
	 * @return	array
	 */
	public function getTerm()
	{
		if( $this->interval->y )
		{
			return array( 'term' => $this->interval->y, 'unit' => 'y' );
		}
		elseif( $this->interval->m )
		{
			return array( 'term' => $this->interval->m, 'unit' => 'm' );
		}
		else
		{
			return array( 'term' => $this->interval->d, 'unit' => 'd' );
		}
	}
	
	/**
	 * Get term unit
	 *
	 * @return	string
	 */
	public function getTermUnit()
	{
		$term = $this->getTerm();
		$lang = \IPS\Member::loggedIn()->language();
		switch( $term['unit'] )
		{
			case 'd':
				return $lang->pluralize( $lang->get('renew_days'), array( $term['term'] ) );
			case 'm':
				return $lang->pluralize( $lang->get('renew_months'), array( $term['term'] ) );
			case 'y':
				return $lang->pluralize( $lang->get('renew_years'), array( $term['term'] ) );
		}
	}
	
	/**
	 * Number of days
	 *
	 * @return	int
	 */
	public function days()
	{
		$days = 0;
		if ( $this->interval->y )
		{
			$days += ( 365 * $this->interval->y );
		}
		if ( $this->interval->m )
		{
			$days += ( ( 365 / 12 ) * $this->interval->m );
		}
		if ( $this->interval->d )
		{
			$days += $this->interval->d;
		}
		return $days;
	}
	
	/**
	 * Calculate cost per day
	 *
	 * @return	\IPS\nexus\Money	Cost per day
	 */
	public function costPerDay()
	{
		$days = $this->days();
		if ( !$days )
		{
			return 0;
		}
		else
		{
			return new \IPS\nexus\Money( $this->cost->amount / $days, $this->cost->currency );
		}
	}
	
	/**
	 * To String
	 *
	 * @return	string
	 */
	public function __toString()
	{
		//return \IPS\Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( $this->cost, $this->getTermUnit() ) ) );
		return sprintf( \IPS\Member::loggedIn()->language()->get( 'renew_option'), $this->cost, $this->getTermUnit() )	;
	}
}
<?php
/**
 * @brief		donations
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	
 * @since		17 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * donations
 */
class _donations extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'donations_manage' );
		parent::execute();
	}

	/**
	 * View Donations
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'donationgoals_manage' ) )
		{
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'cog',
				'title'	=> 'donation_goals',
				'link'	=> \IPS\Http\Url::internal( "app=nexus&module=payments&controller=donationgoals" )
			);
		}
		
		$table = new \IPS\Helpers\Table\Db( 'nexus_donate_logs', \IPS\Http\Url::internal('app=nexus&module=payments&controller=donations') );
		
		$table->include = array( 'dl_goal', 'dl_amount', 'dl_member', 'dl_invoice', 'dl_date' );
		$table->parsers = array(
			'dl_goal'	=> function( $val )
			{
				try
				{
					return \IPS\nexus\Donation\Goal::load( $val )->_title;
				}
				catch ( \Exception $e )
				{
					return NULL;
				}
			},
			'dl_member'	=> function ( $val )
			{
				return \IPS\Theme::i()->getTemplate('global')->userLink( \IPS\Member::load( $val ) );
			},
			'dl_amount'	=> function( $val, $row )
			{
				try
				{
					return (string) new \IPS\nexus\Money( $val, \IPS\nexus\Donation\Goal::load( $row['dl_goal'] )->currency );
				}
				catch ( \Exception $e )
				{
					return $val;
				}
			},
			'dl_invoice'	=> function( $val )
			{
				try
				{
					return \IPS\Theme::i()->getTemplate('invoices')->link( \IPS\nexus\Invoice::load( $val ), TRUE );
				}
				catch ( \OutOfRangeException $e )
				{
					return '';
				}
			},
			'dl_date'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val );
			}
		);
		
		foreach ( \IPS\nexus\Donation\Goal::roots() as $goal )
		{
			$table->filters[ "nexus_donategoal_{$goal->_id}" ] = "dl_goal={$goal->_id}";
		}
		$table->advancedSearch = array(
			'dl_goal'	=> array( \IPS\Helpers\Table\SEARCH_NODE, array( 'class' => '\IPS\nexus\Donation\Goal' ) ),
			'dl_member'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			'dl_amount'	=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			'dl_date'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
		);
		
		\IPS\Output::i()->output = (string) $table;
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_payments_donations');
	}
}
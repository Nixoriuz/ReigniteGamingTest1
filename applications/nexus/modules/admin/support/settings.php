<?php
/**
 * @brief		Support Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		15 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Payment Settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Call
	 *
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$tabs = array();
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'departments_manage' ) )
		{
			$tabs['departments'] = 'departments';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'stockactions_manage' ) )
		{
			$tabs['stockactions'] = 'stock_actions';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'scfields_manage' ) )
		{
			$tabs['fields'] = 'custom_support_fields';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'severities_manage' ) )
		{
			$tabs['severities'] = 'severities';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'statuses_manage' ) )
		{
			$tabs['statuses'] = 'statuses';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'autoresolve_manage' ) )
		{
			$tabs['autoresolve'] = 'autoresolve';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'customerfeedback_manage' ) )
		{
			$tabs['customerfeedback'] = 'customer_feedback';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'outgoingemail_manage' ) )
		{
			$tabs['outgoingemail'] = 'outgoing_emails';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'incomingemail_manage' ) )
		{
			$tabs['incomingemail'] = 'incoming_emails';
		}

		if ( isset( \IPS\Request::i()->tab ) and isset( $tabs[ \IPS\Request::i()->tab ] ) )
		{
			$activeTab = \IPS\Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}
		
		$classname = 'IPS\nexus\modules\admin\support\\' . $activeTab;
		$class = new $classname;
		$class->url = \IPS\Http\Url::internal("app=nexus&module=support&controller=settings&tab={$activeTab}");
		$class->execute();
		
		if ( $method === 'manage' and \IPS\Member::loggedIn()->language()->checkKeyExists( $tabs[ $activeTab ] . '_blurb' ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->blurb( $tabs[ $activeTab ] . '_blurb', TRUE, TRUE ) . \IPS\Output::i()->output;
		}

		if ( $method !== 'manage' or \IPS\Request::i()->isAjax() )
		{
			return;
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('support_settings');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, \IPS\Output::i()->output, \IPS\Http\Url::internal( "app=nexus&module=support&controller=settings" ) );
	}
}
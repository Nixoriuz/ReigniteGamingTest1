<?php
/**
 * @brief		Upgrader: License
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		25 Sep 2014
 * @version		SVN_VERSION_NUMBER
 * @modified	13.09.2015 by IAF (Nulling)
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: License
 */
class _license extends \IPS\Dispatcher\Controller
{

	/**
	 * Show Form... Or not.
	 *
	 * @return	void
	 */
	public function manage()
	{
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=applications" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) ); //Simply skip this (License) step
	}
}

/* Nulled by IandroidFan (IAF) */
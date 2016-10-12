<?php
/**
 * @brief		Ajax only methods
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Content
 * @since		01 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Ajax only methods
 */
class _ajax extends \IPS\Content\Controller
{
	/**
	 * Return a FURL
	 *
	 * @return	void
	 */
	protected function makeFurl()
	{
		return \IPS\Output::i()->json( array( 'slug' => \IPS\Http\Url::seoTitle( \IPS\Request::i()->slug ) ) );
	}
	
}
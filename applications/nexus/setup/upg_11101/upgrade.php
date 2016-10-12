<?php
/**
 * @brief		1.2 Alpha 2 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	
 * @since		19 Dec 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_11101;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 1.2 Alpha 2 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Members
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$offset = isset( \IPS\Request::i()->extra ) ? intval( \IPS\Request::i()->extra ) : 0;
		$pergo = 200;
		$select = \IPS\Db::i()->select( '*', 'core_members', NULL, 'member_id', array( $offset, $pergo ) );
		if ( count( $select ) )
		{
			foreach ( $select as $row )
			{
				\IPS\Db::i()->insert( 'nexus_customers', array(
					'member_id'		=> $row['member_id'],
					'cm_first_name'	=> $row['cm_first_name'],
					'cm_last_name'	=> $row['cm_last_name'],
					'cm_address_1'	=> $row['cm_address_1'],
					'cm_address_2'	=> $row['cm_address_2'],
					'cm_city'		=> $row['cm_city'],
					'cm_state'		=> $row['cm_state'],
					'cm_zip'		=> $row['cm_zip'],
					'cm_country'	=> $row['cm_country'],
					'cm_phone'		=> $row['cm_phone'],
				) );
			}
			
			return $offset + $pergo;
		}
		else
		{
			return TRUE;
		}
	}
}
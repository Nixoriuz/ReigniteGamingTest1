<?php
/**
 * @brief		4.0.11 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		14 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_100041;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.11 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Clean out old notifications that are no longer valid
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$prefix = \IPS\Db::i()->prefix;
		$query = "DELETE {$prefix}core_notifications.* FROM {$prefix}core_notifications LEFT JOIN {$prefix}core_member_status_replies ON({$prefix}core_member_status_replies.reply_id={$prefix}core_notifications.item_id AND {$prefix}core_notifications.item_class='IPS\\core\\Statuses\\Reply') WHERE {$prefix}core_notifications.notification_key='profile_reply' and {$prefix}core_member_status_replies.reply_id IS NULL";

		\IPS\Db::i()->query( $query );

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Deleting old invalid notifications";
	}
	
	/**
	 * Ensure any user added CSS is set to the correct set_id
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		$prefix = \IPS\Db::i()->prefix;
		$query = "UPDATE {$prefix}core_theme_css SET css_set_id=css_added_to WHERE css_added_to > 0 AND css_set_id=0";

		\IPS\Db::i()->query( $query );

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step2CustomTitle()
	{
		return "Fixing incorrect CSS mappings";
	}
}
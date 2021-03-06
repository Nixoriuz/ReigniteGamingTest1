<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		06 Jan 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\cms\setup\upg_100023;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 *
 */
class _Upgrade
{
	/**
	 * Step 1
	 * Create background tasks to rebuild editor fields
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		foreach( \IPS\Db::i()->select( '*', 'cms_databases') as $database )
		{
			foreach( \IPS\Db::i()->select( '*', 'cms_database_fields', array( 'field_database_id=? AND field_type=?', $database['database_id'], 'Editor' ) ) as $field )
			{
				if ( $field['field_id'] != $database['database_field_content'] )
				{
					\IPS\Task::queue( 'cms', 'RebuildEditorFields', array( 'class' => 'IPS\cms\Records' . $database['database_id'], 'fieldId' => $field['field_id'] ), 5, array( 'class', 'fieldId' ) );
				}
			}	
		}
			
		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Creating tasks to rebuild editor fields";
	}
}
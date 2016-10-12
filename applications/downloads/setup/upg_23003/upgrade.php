<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Downloads
 * @since		24 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\setup\upg_23003;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Conversion from 3.0
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\core\Setup\Upgrade::runLegacySql( 'downloads', 23003 );
		
		return TRUE;
	}

	/**
	 * Update comment count
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		try
		{
			foreach( \IPS\Db::i()->select( 'COUNT(*) as comments, comment_fid', 'downloads_comments', NULL, NULL, NULL, 'comment_fid' ) as $file )
			{
				$comments[ $file['comment_fid'] ]	= $file['comments'];
			}

			if( count( $comments ) )
			{
				foreach( $comments as $fileId => $commentCount )
				{
					\IPS\Db::i()->update( 'downloads_files', array( 'file_comments' => $commentCount ), array( 'file_id=?', $fileId ) );
				}
			}
		}
		catch( \Exception $e ){}

		
		return TRUE;
	}
}
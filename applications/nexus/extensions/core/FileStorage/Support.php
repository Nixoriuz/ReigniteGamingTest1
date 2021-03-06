<?php
/**
 * @brief		File Storage Extension: Support Custom Fields / Severity Icons
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	
 * @since		17 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Support Custom Fields / Severity Icons
 */
class _Support
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_fields', array( 'sf_type=?', 'upload' ) )->first() + \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_severities', 'sev_icon IS NOT NULL' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		if ( $offset === 0 )
		{
			foreach ( \IPS\Db::i()->select( '*', 'nexus_support_severities' ) as $severity )
			{
				if ( $severity['sev_icon'] )
				{
					\IPS\Db::i()->update( 'nexus_support_severities', array( 'sev_icon' => \IPS\File::get( $oldConfiguration ?: 'nexus_Support', $severity['sev_icon'] )->move( $storageConfiguration ) ), array( 'sev_id=?', $severity['sev_id'] ) );
				}
			}
		}
		
		$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, array( 'sf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			$where = array();
			$departments = array();
			foreach ( $customFields as $field )
			{
				if ( $field->departments and $field->departments !== '*' )
				{
					$departments = array_merge( $departments, explode( ',', $field->departments ) );
				}
				else
				{
					$where = NULL;
					break;
				}
			}
			if ( $where !== NULL )
			{
				$where = \IPS\Db::i()->in( 'r_department', array_unique( $departments ) );
			}
			
			$request = \IPS\Db::i()->select( '*', 'nexus_support_requests', $where, 'r_id', array( $offset, 1 ) )->first();
			
			$fieldValues = json_decode( $request['r_cfields'], TRUE );
			foreach ( $fieldValues as $k => $v )
			{
				if ( array_key_exists( $k, $customFields ) )
				{
					try
					{
						$fieldValues[ $k ] = \IPS\File::get( $oldConfiguration ?: 'nexus_Support', $fieldValues[ $k ] )->move( $storageConfiguration );
					}
					catch( \Exception $e )
					{
						/* Any issues are logged */
					}
				}
			}
			
			\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_cfields' => json_encode( $fieldValues ) ), array( 'r_id=?', $request['r_id'] ) );
		}
		
		throw new \UnderflowException;
	}
	
		/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		if ( $offset === 0 )
		{
			foreach ( \IPS\Db::i()->select( '*', 'nexus_support_severities' ) as $severity )
			{
				if ( $new = \IPS\File::repairUrl( $severity['sev_icon'] ) )
				{
					\IPS\Db::i()->update( 'nexus_support_severities', array( 'sev_icon' => $new ), array( 'sev_id=?', $severity['sev_id'] ) );
				}
			}
		}
		
		$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, array( 'sf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			$where = array();
			$departments = array();
			foreach ( $customFields as $field )
			{
				if ( $field->departments and $field->departments !== '*' )
				{
					$departments = array_merge( $departments, explode( ',', $field->departments ) );
				}
				else
				{
					$where = NULL;
					break;
				}
			}
			if ( $where !== NULL )
			{
				$where = \IPS\Db::i()->in( 'r_department', array_unique( $departments ) );
			}
			
			$request = \IPS\Db::i()->select( '*', 'nexus_support_requests', $where, 'r_id', array( $offset, 1 ) )->first();
			
			$fieldValues = json_decode( $request['r_cfields'], TRUE );
			foreach ( $fieldValues as $k => $v )
			{
				if ( array_key_exists( $k, $customFields ) )
				{
					if ( $new = \IPS\File::repairUrl( $fieldValues[ $k ] ) )
					{
						$fieldValues[ $k ] = $new;
					}
				}
			}
			
			\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_cfields' => json_encode( $fieldValues ) ), array( 'r_id=?', $request['r_id'] ) );
		}
		
		throw new \UnderflowException;
	}
	
	/**
	 * Check if a file is valid
	 *
	 * @param	\IPS\Http\Url	$file		The file to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		try
		{
			\IPS\Db::i()->select( '*', 'nexus_support_severities', array( 'sev_icon=?', (string) $file ) )->first();
			return TRUE;
		}
		catch ( \UnderflowException $e ) { }
		
		$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, array( 'sf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'nexus_support_requests', array( "r_cfields LIKE ?", "%{$file}%" ) ) as $request )
			{
				$fieldValues = json_decode( $request['r_cfields'], TRUE );
				foreach ( $customFields as $field )
				{
					if ( $fieldValues[ $field->id ] == (string) $file )
					{
						return TRUE;
					}
				}
			}
		}
		
		return FALSE;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach ( \IPS\Db::i()->select( '*', 'nexus_support_severities' ) as $severity )
		{
			if ( $severity['sev_icon'] )
			{
				\IPS\File::get( 'nexus_Support', $severity['sev_icon'] )->delete();
			}
		}

		$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, array( 'sf_type=?', 'upload' ) );
		if ( count( $customFields ) )
		{
			$where = array();
			$departments = array();
			foreach ( $customFields as $field )
			{
				if ( $field->departments and $field->departments !== '*' )
				{
					$departments = array_merge( $departments, explode( ',', $departments ) );
				}
				else
				{
					$where = NULL;
					break;
				}
			}
			if ( $where !== NULL )
			{
				$where = \IPS\Db::i()->in( 'r_department', array_unique( $departments ) );
			}

			foreach( \IPS\Db::i()->select( '*', 'nexus_support_requests', $where ) as $request )
			{
				$fieldValues = json_decode( $request['r_cfields'], TRUE );
				foreach ( $fieldValues as $k => $v )
				{
					if ( array_key_exists( $k, $customFields ) )
					{
						try
						{
							\IPS\File::get( 'nexus_Support', $fieldValues[ $k ] )->delete();
						}
						catch( \Exception $e ){}
					}
				}
			}
		}
	}
}
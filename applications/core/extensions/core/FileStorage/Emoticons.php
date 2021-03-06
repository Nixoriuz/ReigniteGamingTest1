<?php
/**
 * @brief		File Storage Extension: Emoticons
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		23 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Emoticons
 */
class _Emoticons
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'core_emoticons' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void|int							An offset integer to use on the next cycle, or nothing
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		$emoticon = \IPS\Db::i()->select( '*', 'core_emoticons', array(), 'id', array( $offset, 1 ) )->first();
		
		try
		{
			$file = \IPS\File::get( $oldConfiguration ?: 'core_Emoticons', $emoticon['image'] )->move( $storageConfiguration );
			
			if ( (string) $file != $emoticon['image'] )
			{
				\IPS\Db::i()->update( 'core_emoticons', array( 'image' => (string) $file ), array( 'id=?', $emoticon['id'] ) );
			}
			
			unset( \IPS\Data\Store::i()->emoticons );
		}
		catch( \Exception $e )
		{
			/* Any issues are logged */
		}
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		$emoticon = \IPS\Db::i()->select( '*', 'core_emoticons', array(), 'id', array( $offset, 1 ) )->first();
		
		if ( $new = \IPS\File::repairUrl( $emoticon['image'] ) )
		{
			\IPS\Db::i()->update( 'core_emoticons', array( 'image' => $new ), array( 'id=?', $emoticon['id'] ) );
			
			unset( \IPS\Data\Store::i()->emoticons );
		}
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
			$emoticon	= \IPS\Db::i()->select( '*', 'core_emoticons', array( 'image=?', (string) $file ) )->first();

			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'core_emoticons', 'image IS NOT NULL' ) as $emoticon )
		{
			try
			{
				\IPS\File::get( 'core_Emoticons', $emoticon['image'] )->delete();
			}
			catch( \Exception $e ){}
		}
	}
}
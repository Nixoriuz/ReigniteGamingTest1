<?php
/**
 * @brief		4.0.4 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Blog
 * @since		5 May 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\blog\setup\upg_100027;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.4 Upgrade Code
 */
class _Upgrade
{
	
	/**
	 * Convert entry images
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$limit = isset( \IPS\Request::i()->extra ) ? intval( \IPS\Request::i()->extra ) : 0;
		
		$select = \IPS\Db::i()->select( '*', 'blog_entries', "entry_image<>''", 'entry_id ASC', array( $limit, 1000 ) );
		
		if ( !$select->count() )
		{
			return TRUE;
		}
		
		foreach ( $select as $entry )
		{
			try
			{
				$filePath = \IPS\ROOT_PATH . '/uploads/' . $entry['entry_image'];
				
				$file = \IPS\File::create( 'core_Attachment', $entry['entry_image'], NULL, NULL, FALSE, $filePath );
				$attachment = $file->makeAttachment('');
				$fileName = htmlentities( $attachment['attach_file'], \IPS\HTMLENTITIES, 'UTF-8', TRUE );
				
				$image = <<<IMAGE
<a href="<fileStore.core_Attachment>/{$file}" class="ipsAttachLink ipsAttachLink_image" style="float:left;"><img data-fileid="{$attachment['attach_id']}" src="<fileStore.core_Attachment>/{$file}" class="ipsImage ipsImage_thumbnailed" style="margin:10px;" alt="{$fileName}"></a>
IMAGE;
				
				\IPS\Db::i()->update( 'blog_entries', array( 'entry_content' => $image . $entry['entry_content'] ), array( 'entry_id=?', $entry['entry_id'] ) );

				$map	= array(
					'attachment_id'		=> $attachment['attach_id'],
					'location_key'		=> 'blog_Entries',
					'id1'				=> $entry['entry_id'],
					'id2'				=> NULL,
					'id3'				=> NULL,
					'temp'				=> NULL,
				);

				\IPS\Db::i()->replace( 'core_attachments_map', $map );
			}
			catch ( \Exception $e ) {}
		}
		
		return $limit + 1000;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Converting entry images";
	}
}
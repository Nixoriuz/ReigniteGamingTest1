<?php
/**
 * @brief		Profile extension: Gallery
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		02 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\extensions\core\Profile;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Profile extension: Gallery
 */
class _gallery
{
	/**
	 * Member
	 */
	protected $member;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member	$member	Member whose profile we are viewing
	 * @return	void
	 */
	public function __construct( \IPS\Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	public function showTab()
	{
		$where = array( array( 'album_owner_id=?', $this->member->member_id ) );
		
		if( count( \IPS\Member::loggedIn()->socialGroups() ) )
		{
			$where[] = array( '( album_type=1 OR ( album_type=2 AND album_owner_id=? ) OR ( album_type=3 AND ( album_owner_id=? OR ( album_allowed_access IS NOT NULL AND album_allowed_access IN(' . implode( ',', \IPS\Member::loggedIn()->socialGroups() ) . ') ) ) ) )', \IPS\Member::loggedIn()->member_id, \IPS\Member::loggedIn()->member_id );
		}
		else
		{
			$where[] = array( '( album_type=1 OR ( album_type IN (2,3) AND album_owner_id=? ) )', \IPS\Member::loggedIn()->member_id );
		}
		
		return (bool) \IPS\Db::i()->select( 'COUNT(*)', 'gallery_albums', $where )->first();
	}
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function render()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'profile.css', 'gallery', 'front' ) );
		
		$table = new \IPS\gallery\Album\Table( $this->member->url()->setQueryString( 'tab', 'node_gallery_gallery') );
		$table->setOwner( $this->member );
		$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'gallery' ), 'profileAlbumTable' );
		$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'global', 'gallery' ), 'profileAlbumRow' );
		
		return (string) $table;
	}
}
<?php
/**
 * @brief		File Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Downloads
 * @since		8 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Model
 */
class _File extends \IPS\Content\Item implements
\IPS\Content\Permissions,
\IPS\Content\Tags,
\IPS\Content\Reputation,
\IPS\Content\Followable,
\IPS\Content\ReportCenter,
\IPS\Content\ReadMarkers,
\IPS\Content\Views,
\IPS\Content\Hideable, \IPS\Content\Featurable, \IPS\Content\Pinnable, \IPS\Content\Lockable,
\IPS\Content\Shareable,
\IPS\Content\Searchable,
\IPS\Content\Embeddable
{
	/**
	 * @brief	Application
	 */
	public static $application = 'downloads';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'downloads';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'downloads_files';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'file_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = 'IPS\downloads\Category';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\downloads\File\Comment';
	
	/**
	 * @brief	Review Class
	 */
	public static $reviewClass = 'IPS\downloads\File\Review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'container'				=> 'cat',
		'author'				=> 'submitter',
		'views'					=> 'views',
		'title'					=> 'name',
		'content'				=> 'desc',
		'num_comments'			=> 'comments',
		'unapproved_comments'	=> 'unapproved_comments',
		'hidden_comments'		=> 'hidden_comments',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'unapproved_reviews',
		'hidden_reviews'		=> 'hidden_reviews',
		'last_comment'			=> 'last_comment',
		'last_review'			=> 'last_review',
		'date'					=> 'submitted',
		'updated'				=> 'updated',
		'rating'				=> 'rating',
		'approved'				=> 'open',
		'approved_by'			=> 'approver',
		'approved_date'			=> 'approvedon',
		'pinned'				=> 'pinned',
		'featured'				=> 'featured',
		'locked'				=> 'locked',
		'ip_address'			=> 'ipaddress'
	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'downloads_file';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'download';
	
	/**
	 * @brief	Form Lang Prefix
	 */
	public static $formLangPrefix = 'file_';
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'file_id';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'downloads-file';
		
	/**
	 * Set name
	 *
	 * @param	string	$name	Name
	 * @return	void
	 */
	public function set_name( $name )
	{
		$this->_data['name'] = $name;
		$this->_data['name_furl'] = \IPS\Http\Url::seoTitle( $name );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_furl()
	{
		if( !$this->_data['name_furl'] )
		{
			$this->name_furl	= \IPS\Http\Url::seoTitle( $this->name );
			$this->save();
		}

		return $this->_data['name_furl'] ?: \IPS\Http\Url::seoTitle( $this->name );
	}

	/**
	 * Get primary screenshot ID
	 *
	 * @return	int|null
	 */
	public function get__primary_screenshot()
	{
		return ( isset( $this->_data['primary_screenshot'] ) ) ? $this->_data['primary_screenshot'] : NULL;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=downloads&module=downloads&controller=view&id={$this->id}", 'front', 'downloads_file', $this->name_furl );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/**
	 * Get URL for last comment page
	 *
	 * @return	\IPS\Http\Url
	 */
	public function lastCommentPageUrl()
	{
		return parent::lastCommentPageUrl()->setQueryString( 'tab', 'comments' );
	}
	
	/**
	 * Get URL for last review page
	 *
	 * @return	\IPS\Http\Url
	 */
	public function lastReviewPageUrl()
	{
		return parent::lastCommentPageUrl()->setQueryString( 'tab', 'reviews' );
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	callable
	 */
	public static function contentTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'browse', 'downloads' ), 'rows' );
	}

	/**
	 * Files
	 */
	protected $_files = array();

	/**
	 * Get files
	 *
	 * @param	int|NULL	$version		If provided, will get the file records for a specific previous version (downloads_filebackup.b_id)
	 * @param	bool		$includeLinks	If true, will include linked files
	 * @return	\IPS\File\Iterator
	 */
	public function files( $version=NULL, $includeLinks=TRUE )
	{
		if( isset( $this->_files[ (int) $version ] ) )
		{
			return $this->_files[ (int) $version ];
		}

		$where = $includeLinks ? array( array( 'record_file_id=? AND ( record_type=? OR record_type=? )', $this->id, 'upload', 'link' ) ) : array( array( 'record_file_id=? AND record_type=?', $this->id, 'upload' ) );
		if ( $version !== NULL )
		{
			$backup = \IPS\Db::i()->select( 'b_records', 'downloads_filebackup', array( 'b_id=?', $version ) )->first();
			$where[] = \IPS\Db::i()->in( 'record_id', explode( ',', $backup ) );
		}
		else
		{
			$where[] = array( 'record_backup=0' );
		}
						
		$iterator = \IPS\Db::i()->select( '*', 'downloads_files_records', $where )->setKeyField( 'record_id' );
		$iterator = new \IPS\File\Iterator( $iterator, 'downloads_Files', 'record_location' );

		$this->_files[ (int) $version ]	= $iterator;
		return $this->_files[ (int) $version ];
	}
	
	/**
	 * Total filesize
	 */
	protected $_filesize = NULL;
		
	/**
	 * Get Total filesize
	 *
	 * @return	int
	 */
	public function filesize()
	{
		if ( $this->_filesize === NULL )
		{
			$this->_filesize = \IPS\Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->id, 'upload' ) )->first();
		}
		
		return $this->_filesize;
	}
	
	/**
	 * Get Price
	 *
	 * @return	\IPS\nexus\Money|NULL
	 */
	public function price()
	{
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on )
		{
			if ( $this->nexus )
			{
				$packages = explode( ',', $this->nexus );
				try
				{
					if ( count( $packages ) === 1 )
					{
						return \IPS\nexus\Package::load( $this->nexus )->priceToDisplay();
					}
					else
					{
						return \IPS\nexus\Package::lowestPriceToDisplay( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_packages', \IPS\Db::i()->in( 'p_id', $packages ) ), 'IPS\nexus\Package' ) );
					}
				}
				catch ( \OutOfRangeException $e ) { }
				
				return NULL;
			}
			
			if ( $this->cost )
			{
				$currency = isset( $_SESSION['currency'] ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency();
				if ( $costs = json_decode( $this->cost, TRUE ) and isset( $costs[ $currency ]['amount'] ) )
				{
					if ( $costs[ $currency ]['amount'] )
					{
						return new \IPS\nexus\Money( $costs[ $currency ]['amount'], $currency );
					}
				}
				else
				{
					return new \IPS\nexus\Money( $this->cost, $currency );
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * @brief	Number of purchases
	 */
	protected static $purchaseCounts;
	
	/**
	 * Get number of purchases
	 *
	 * @return	array
	 */
	public function purchaseCount()
	{
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on )
		{
			if ( static::$purchaseCounts === NULL )
			{
				static::$purchaseCounts = iterator_to_array( \IPS\Db::i()->select( 'COUNT(*) AS count, ps_item_id', 'nexus_purchases', array( array( 'ps_app=? AND ps_type=?', 'downloads', 'file' ), \IPS\Db::i()->in( 'ps_item_id', array_keys( static::$multitons ) ) ), NULL, NULL, 'ps_item_id' )->setKeyField('ps_item_id')->setValueField('count') );
				foreach ( array_keys( static::$multitons ) as $k )
				{
					if ( !isset( static::$purchaseCounts[ $k ] ) )
					{
						static::$purchaseCounts[ $k ] = 0;
					}
				}
			}
			
			if ( !isset( static::$purchaseCounts[ $this->id ] ) )
			{
				static::$purchaseCounts[ $this->id ] = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) )->first();
			}
			
			return static::$purchaseCounts[ $this->id ];
		}
		
		return NULL;
	}
	
	/**
	 * Get Renewal Term
	 *
	 * @return	\IPS\nexus\Purchase\RenewalTerm|NULL
	 */
	public function renewalTerm()
	{
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on and $this->renewal_term )
		{
			$renewalPrice = json_decode( $this->renewal_price, TRUE );
			$renewalPrice = is_array( $renewalPrice ) ? $renewalPrice[ \IPS\nexus\Customer::loggedIn()->defaultCurrency() ] : array( 'currency' => \IPS\nexus\Customer::loggedIn()->defaultCurrency(), 'amount' => $renewalPrice );
			
			$tax = NULL;
			try
			{
				$tax = \IPS\Settings::i()->idm_nexus_tax ? \IPS\nexus\Tax::load( \IPS\Settings::i()->idm_nexus_tax ) : NULL;
			}
			catch ( \OutOfRangeException $e ) { }
			
			return new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $renewalPrice['amount'], $renewalPrice['currency'] ), new \DateInterval( "P{$this->renewal_term}" . mb_strtoupper( $this->renewal_units ) ), $tax );
		}
		
		return NULL;
	}

	/**
	 * Screenshots
	 */
	protected $_screenshotsNormal = NULL;
	protected $_screenshotsThumbs = NULL;
	protected $_screenshotsOriginal = NULL;
	
	/**
	 * Get screenshots
	 *
	 * This is probably my favourite method so far in IPS4. It will prepare a query,
	 * but not execute it until you begin to iterate. It will cache the results so
	 * even if you iterate more than once, the query is only ran once. And as it
	 * iterates, it will automatically create \IPS\File objects out of the values.
	 * It is programatic beauty.
	 * And if you change one character, I will cut off your fingers.
	 *  - Mark
	 *
	 * @param	int		$type			0 = Normal, 1 = Thumbnails, 2 = No watermark
	 * @param	bool	$includeLinks	If true, will include linked files
	 * @return	\IPS\File\Iterator
	 */
	public function screenshots( $type=0, $includeLinks=TRUE )
	{
		switch ( $type )
		{
			case 0:
				if( $this->_screenshotsNormal !== NULL )
				{
					return $this->_screenshotsNormal;
				}
				$valueField = 'record_location';
				$property	= "_screenshotsNormal";
				break;
			case 1:
				if( $this->_screenshotsThumbs !== NULL )
				{
					return $this->_screenshotsThumbs;
				}
				$valueField = function( $row ) { return ( $row['record_type'] == 'sslink' ) ? $row['record_location'] : $row['record_thumb']; };
				$property	= "_screenshotsThumbs";
				break;
			case 2:
				if( $this->_screenshotsOriginal !== NULL )
				{
					return $this->_screenshotsOriginal;
				}
				$valueField = function( $row ) { return $row['record_no_watermark'] ?: $row['record_location']; };
				$property	= "_screenshotsOriginal";
				break;
			default:
				throw new \InvalidArgumentException;
		}
		
		$iterator = \IPS\Db::i()->select( 'record_id, record_location, record_thumb, record_no_watermark, record_default, record_type', 'downloads_files_records', $includeLinks ? array( 'record_file_id=? AND ( record_type=? OR record_type=? ) AND record_backup=0', $this->id, 'ssupload', 'sslink' ) : array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->id, 'ssupload' ), NULL, NULL, NULL, NULL, \IPS\DB::SELECT_SQL_CALC_FOUND_ROWS )->setKeyField( 'record_id' )->setValueField( $valueField );
		$iterator = new \IPS\File\Iterator( $iterator, 'downloads_Screenshots' );
		$iterator = new \CachingIterator( $iterator, \CachingIterator::FULL_CACHE );

		$this->$property	= $iterator;
		return $this->$property;
	}

	/*
	 * @brief Cached primary screenshot
	 */
	protected $_primaryScreenshot	= FALSE;

	/**
	 * Get primary screenshot
	 *
	 * @return	\IPS\File|NULL
	 */
	public function get_primary_screenshot()
	{
		if( $this->_primaryScreenshot !== FALSE )
		{
			return $this->_primaryScreenshot;
		}

		$screenshots = $this->screenshots();
		if ( $this->_data['primary_screenshot'] and isset( $screenshots[ $this->_data['primary_screenshot'] ] ) )
		{
			$this->_primaryScreenshot	= $screenshots[ $this->_data['primary_screenshot'] ];
			return $this->_primaryScreenshot;
		}
		else
		{
			foreach ( $screenshots as $id => $screenshot )
			{
				if ( !$this->_data['primary_screenshot'] or $id === $this->_data['primary_screenshot'] )
				{
					$this->_primaryScreenshot	= $screenshot;
					return $this->_primaryScreenshot;
				}
			}
		}

		$this->_primaryScreenshot	= NULL;
		return $this->_primaryScreenshot;
	}

	/*
	 * @brief Cached primary screenshot thumb
	 */
	protected $_primaryScreenshotThumb	= FALSE;

	/**
	 * Get primary screenshot thumbnail
	 *
	 * @return \IPS\File|NULL
	 */
	public function get_primary_screenshot_thumb()
	{
		if( $this->_primaryScreenshotThumb !== FALSE )
		{
			return $this->_primaryScreenshotThumb;
		}

		$screenshots = $this->screenshots( 1 );
		if ( $this->_data['primary_screenshot'] and isset( $screenshots[ $this->_data['primary_screenshot'] ] ) )
		{
			$this->_primaryScreenshotThumb	= $screenshots[ $this->_data['primary_screenshot'] ];
			return $this->_primaryScreenshotThumb;
		}
		else
		{
			foreach( $screenshots as $id => $screenshot )
			{
				if ( !$this->_data['primary_screenshot'] or $id === $this->_data['primary_screenshot'] )
				{
					$this->_primaryScreenshotThumb	= $screenshot;
					return $this->_primaryScreenshotThumb;
				}
			}
		}

		$this->_primaryScreenshotThumb	= NULL;
		return $this->_primaryScreenshotThumb;
	}
	
	/**
	 * Get custom field values
	 *
	 * @param	bool	$topic	Are we returning the custom fields for the topic? If so we need to apply the display formatting.
	 * @return	array
	 */
	public function customFields( $topic = FALSE )
	{
		$return = array();
		$fields = $this->container()->cfields;

		if( $topic === TRUE )
		{
			$fieldData	= iterator_to_array( \IPS\Db::i()->select( 'cf_id,cf_format', 'downloads_cfields' )->setKeyField( 'cf_id' )->setValueField( 'cf_format' ) );
		}

		try
		{
			$data = \IPS\Db::i()->select( '*', 'downloads_ccontent', array( 'file_id=?', $this->id ) )->first();
			
			foreach ( $data as $k => $v )
			{
				if ( array_key_exists( str_replace( 'field_', '', $k ), $fields ) )
				{
					if( $topic === TRUE )
					{
						if( isset( $fieldData[ str_replace( 'field_', '', $k ) ] ) )
						{
							$v	= str_replace( '{content}', htmlspecialchars( $v, \IPS\HTMLENTITIES, 'UTF-8', FALSE ), $fieldData[ str_replace( 'field_', '', $k ) ] );
							$v	= str_replace( '{member_id}', \IPS\Member::loggedIn()->member_id, $v );
							$v	= str_replace( '{title}', \IPS\Member::loggedIn()->language()->addToStack( 'downloads_field_' . str_replace( 'field_', '', $k ) ), $v );
						}
						else
						{
							$v	= htmlspecialchars( $v, \IPS\HTMLENTITIES, 'UTF-8', FALSE );
						}
					}

					$return[ $k ] = $v;
				}
			}
		}
		catch( \UnderflowException $e ){}
		
		return $return;
	}
	
	/**
	 * Get available comment/review tabs
	 *
	 * @return	array
	 */
	public function commentReviewTabs()
	{
		$tabs = array();
		if ( $this->container()->bitoptions['reviews'] )
		{
			$tabs['reviews'] = \IPS\Member::loggedIn()->language()->pluralize( \IPS\Member::loggedIn()->language()->get( 'file_review_count' ), array( $this->mapped('num_reviews') ) );
		}
		if ( $this->container()->bitoptions['comments'] )
		{
			$tabs['comments'] = \IPS\Member::loggedIn()->language()->pluralize( \IPS\Member::loggedIn()->language()->get( 'file_comment_count' ), array( $this->mapped('num_comments') ) );
		}
				
		return $tabs;
	}
	
	/**
	 * Get comment/review output
	 *
	 * @param	string	$tab	Active tab
	 * @return	string
	 */
	public function commentReviews( $tab )
	{
		if ( $tab === 'reviews' )
		{
			return \IPS\Theme::i()->getTemplate('view')->reviews( $this );
		}
		elseif( $tab === 'comments' )
		{
			return \IPS\Theme::i()->getTemplate('view')->comments( $this );
		}
		
		return '';
	}
	
	/**
	 * Should new items be moderated?
	 *
	 * @param	\IPS\Member		$member		The member posting
	 * @param	\IPS\Node\Model	$container	The container
	 * @return	bool
	 */
	public static function moderateNewItems( \IPS\Member $member, \IPS\Node\Model $container = NULL )
	{
		if ( $container and $container->bitoptions['moderation'] and !$member->group['g_avoid_q'] )
		{
			return TRUE;
		}
		
		return parent::moderateNewItems( $member, $container );
	}
	
	/**
	 * Should new comments be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewComments( \IPS\Member $member )
	{
		$commentClass = static::$commentClass;
		return ( $this->container()->bitoptions['comment_moderation'] and !$member->group['g_avoid_q'] ) or parent::moderateNewComments( $member );
	}
	
	/**
	 * Should new reviews be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewReviews( \IPS\Member $member )
	{
		return $this->container()->bitoptions['reviews_mod'] and !$member->group['g_avoid_q'];
	}
	
	/**
	 * Can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		if ( !$this->container()->open and !$member->isAdmin() )
		{
			return FALSE;
		}
		return parent::canView( $member );
	}
	
	/**
	 * Can edit?
	 * Authors can always edit their own files
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return ( $member->member_id == $this->author()->member_id ) or parent::canEdit( $member );
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string|NULL	$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param	bool|NULL	$includeHiddenItems	Include hidden files? Boolean or NULL to detect if currently logged member has permission
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly			If true will return the count
	 * @param	array|null	$joins				Additional arbitrary joins for the query
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param	bool		$joinTags			If true, will join the tags table
	 * @param	bool		$joinAuthor			If true, will join the members table for the author
	 * @param	bool		$joinLastCommenter	If true, will join the members table for the last commenter
	 * @return	\IPS\Patterns\ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=NULL, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL, $skipPermission=FALSE, $joinTags=TRUE, $joinAuthor=TRUE, $joinLastCommenter=TRUE )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		if ( !$member->isAdmin() )
		{
			$where[] = array( 'downloads_categories.copen=1' );
			$joinContainer = TRUE;
		}
				
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter );
	}
	
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		if ( $member->idm_block_submissions )
		{
			if ( $showError )
			{
				\IPS\Output::i()->error( 'err_submissions_blocked', '1D168/1', 403, '' );
			}
			
			return FALSE;
		}

		return parent::canCreate( $member, $container, $showError );
	}
	
	/**
	 * Can review?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canReview( $member = NULL )
	{
		return parent::canReview( $member ) and !$this->mustDownloadBeforeReview( $member );
	}
	
	/**
	 * Member has to download before they can review?
	 *
	 * @return	bool
	 */
	public function mustDownloadBeforeReview( \IPS\Member $member = NULL )
	{
		if ( $this->container()->bitoptions['reviews_download'] )
		{
			try
			{
				\IPS\Db::i()->select( '*', 'downloads_downloads', array( 'dfid=? AND dmid=?', $this->id, $member ? $member->member_id : \IPS\Member::loggedIn()->member_id ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Can change author?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canChangeAuthor( \IPS\Member $member = NULL )
	{
		return static::modPermission( 'edit', $member, $this->container() );
	}

	/**
	 * Change Author
	 *
	 * @param	\IPS\Member	$newAuthor	The new author
	 * @return	void
	 */
	public function changeAuthor( \IPS\Member $newAuthor )
	{
		if ( \IPS\Application::appIsEnabled( 'nexus' ) )
		{
			\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => $newAuthor->member_id ), array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) );
		}
		parent::changeAuthor( $newAuthor );
	}
	
	/**
	 * @brief	Can download?
	 */
	protected $canDownload = NULL;
	
	/**
	 * Can the member download this file?
	 *
	 * @param	\IPS\Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canDownload( \IPS\Member $member = NULL )
	{
		if ( $this->canDownload === NULL )
		{
			try
			{
				$this->downloadCheck( NULL, $member );
				$this->canDownload = TRUE;
			}
			catch ( \DomainException $e )
			{
				$this->canDownload = FALSE;
			}
		}
		
		return $this->canDownload;
	}
	
	/**
	 * Can the member buy this file?
	 *
	 * @param	\IPS\Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canBuy( \IPS\Member $member = NULL )
	{
		/* Is this a paid file? */
		if ( !\IPS\Application::appIsEnabled( 'nexus' ) or !\IPS\Settings::i()->idm_nexus_on or ( ( !$this->cost or !$this->price() ) and !$this->nexus ) )
		{
			return FALSE;
		}
		
		/* Init */
		$member = $member ?: \IPS\Member::loggedIn();
		$restrictions = json_decode( $member->group['idm_restrictions'], TRUE );

        /* File author */
        if( $member == $this->author() )
        {
            return FALSE;
        }
		
		/* Basic permission check */
		if ( !$this->container()->can( 'download', $member ) )
		{
			/* Hold on - if we're a guest and buying means we'll have to register which will put us in a group with permission, we can continue */
			if ( \IPS\Member::loggedIn()->member_id or !$this->container()->can( 'download', \IPS\Member\Group::load( \IPS\Settings::i()->member_group ) ) )
			{
				return FALSE;
			}
		}
		
		/* Minimum posts */
		if ( $member->member_id and $restrictions['min_posts'] and $restrictions['min_posts'] > $member->member_posts )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Purchases that can be renewed
	 *
	 * @param	\IPS\Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	array
	 */
	public function purchasesToRenew( \IPS\Member $member = NULL )
	{
		/** return an empty array if we don't have commerce */
		if ( !\IPS\Application::appIsEnabled( 'nexus' ) )
		{
			return array();
		}
		$member = $member ?: \IPS\Member::loggedIn();
		
		$return = array();

		foreach ( \IPS\downloads\extensions\nexus\Item\File::getPurchases( \IPS\nexus\Customer::load( $member->member_id ), $this->id ) as $purchase )
		{
			if ( !$purchase->active and $purchase->canRenewUntil() !== FALSE )
			{
				$return[] = $purchase;
			}
		}
		return $return;
	}
	
	/**
	 * Download check
	 *
	 * @parsm	array|NULL			$record		Specific record to download
	 * @param	\IPS\Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	void
	 * @throws	\DomainException
	 */
	public function downloadCheck( array $record = NULL, \IPS\Member $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		$restrictions = json_decode( $member->group['idm_restrictions'], TRUE );
		
		/* Basic prmission check */
		if ( !$this->container()->can( 'download', $member ) )
		{
			throw new \DomainException( $this->container()->message('npd') ?: 'download_no_perm' );
		}
		
		/* Paid? */
		if ( \IPS\Settings::i()->idm_nexus_on and ( ( $this->cost and $this->price() ) or $this->nexus ) and !$member->group['idm_bypass_paid'] and $member->member_id != $this->author()->member_id )
		{
			if ( $this->cost )
			{
				if ( !count( \IPS\downloads\extensions\nexus\Item\File::getPurchases( \IPS\nexus\Customer::load( $member->member_id ), $this->id, FALSE ) ) )
				{
					throw new \DomainException( 'file_not_purchased' );
				}
			}
			elseif ( $this->nexus )
			{
				 if ( !count( \IPS\nexus\extensions\nexus\Item\Package::getPurchases( \IPS\nexus\Customer::load( $member->member_id ), explode( ',', $this->nexus ), FALSE ) ) )
				 {
					 throw new \DomainException( 'file_not_purchased' );
				 }
			}
		}
		
		/* Minimum posts */
		if ( $member->member_id and $restrictions['min_posts'] and $restrictions['min_posts'] > $member->member_posts )
		{
			throw new \DomainException( $member->language()->addToStack( 'download_min_posts', FALSE, array( 'pluralize' => array( $restrictions['min_posts'] ) ) ) );
		}
		
		/* Simultaneous downloads */
		if ( $restrictions['limit_sim'] )
		{
			if ( \IPS\Db::i()->select( 'COUNT(*)', 'downloads_sessions', $member->member_id ? array( 'dsess_mid=?', $member->member_id ) : array( 'dsess_ip=?', \IPS\Request::i()->ipAddress() ) )->first() >= $restrictions['limit_sim'] )
			{
				throw new \DomainException( $member->language()->addToStack( 'max_simultaneous_downloads', FALSE, array( 'pluralize' => array( $restrictions['limit_sim'] ) ) ) );
			}
		}
				
		/* For bandwidth checks, we need a record. If we don't have one - use the one with the smallest filesize */
		if ( !$record )
		{
			$it = $this->files();
			foreach ( $it as $file )
			{
				$data = $it->data();
				if ( !$record or $record['record_size'] > $data['record_size'] )
				{
					$record = $data;
				}
			}
		}
		
		/* Bandwidth & Download limits */
		$logWhere = $member->member_id ? array( 'dmid=?', $member->member_id ) : array( 'dip=?', \IPS\Request::i()->ipAddress() );
		foreach ( array( 'daily' => 'P1D', 'weekly' => 'P1W', 'monthly' => 'P1M' ) as $k => $interval )
		{
			$timePeriodWhere = array( $logWhere, array( 'dtime>?', \IPS\DateTime::create()->sub( new \DateInterval( $interval ) )->getTimestamp() ) );
			
			/* Bandwidth */
			if ( $restrictions[ $k . '_bw' ] )
			{
				$usedThisPeriod = \IPS\Db::i()->select( 'SUM(dsize)', 'downloads_downloads', $timePeriodWhere )->first();
				if ( ( $record['record_size'] + $usedThisPeriod ) > ( $restrictions[ $k . '_bw' ] * 1024 ) )
				{
					if ( $record['record_size'] > ( $restrictions[ $k . '_bw' ] * 1024 ) )
					{
						throw new \DomainException( $member->language()->addToStack( 'bandwidth_limit_' . $k . '_never', FALSE, array( 'sprintf' => array( \IPS\Output\Plugin\Filesize::humanReadableFilesize( $restrictions[ $k . '_bw' ] * 1024 ), \IPS\Output\Plugin\Filesize::humanReadableFilesize( $record['record_size'] ) ) ) ) );
					}
					else
					{
						$date = new \IPS\DateTime;
						foreach ( \IPS\Db::i()->select( '*', 'downloads_downloads', $timePeriodWhere, 'dtime ASC' ) as $log )
						{
							$usedThisPeriod -= $log['dsize'];
							if ( ( $record['record_size'] + $usedThisPeriod ) < ( $restrictions[ $k . '_bw' ] * 1024 ) )
							{
								$date = \IPS\DateTime::ts( $log['dtime'] );
								break;
							}
						}
												
						throw new \DomainException( $member->language()->addToStack( 'bandwidth_limit_' . $k, FALSE, array( 'sprintf' => array( \IPS\Output\Plugin\Filesize::humanReadableFilesize( $restrictions[ $k . '_bw' ] * 1024 ), (string) $date->add( new \DateInterval( $interval ) ) ) ) ) );
					}
				}
			}
			
			/* Download */
			if ( $restrictions[ $k . '_dl' ] )
			{
				$downloadsThisPeriod = \IPS\Db::i()->select( 'COUNT(*)', 'downloads_downloads', $timePeriodWhere, 'dtime ASC' )->first();
				if( $downloadsThisPeriod >= $restrictions[ $k . '_dl' ] )
				{
					throw new \DomainException( $member->language()->addToStack( 'download_limit_' . $k, FALSE, array( 'pluralize' => array( $restrictions[ $k . '_dl' ] ), 'sprintf' => array( (string) \IPS\DateTime::ts( \IPS\Db::i()->select( 'dtime', 'downloads_downloads', $timePeriodWhere, 'dtime ASC', array( $restrictions[ $k . '_dl' ] - $downloadsThisPeriod, 1 ) )->first() )->add( new \DateInterval( $interval ) ) ) ) ) );
				}
			}
		}
		
	}
	
	/**
	 * Can view downloaders?
	 *
	 * @param	\IPS\Member|NULL	$member		The member to check or NULL for currently logged in member
	 * @return	bool
	 */
	public function canViewDownloaders( \IPS\Member $member = NULL )
	{
		if ( $this->container()->log === 0 )
		{
			return FALSE;
		}
		
		$member = $member ?: \IPS\Member::loggedIn();
		if ( $member == $this->author() and $this->container()->bitoptions['submitter_log'] )
		{
			return TRUE;
		}
				
		return $member->group['idm_view_downloads'];
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	int						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{
		/* Init */
		$return = parent::formElements( $item, $container );

		/* Description */
		$return['description'] = new \IPS\Helpers\Form\Editor( 'file_desc', $item ? $item->desc : NULL, TRUE, array( 'app' => 'downloads', 'key' => 'Downloads', 'autoSaveKey' => 'downloads-new-file', 'attachIds' => ( $item === NULL ? NULL : array( $item->id ) ) ) );
		
		/* Primary screenshot */
		if ( $item )
		{
			$screenshotOptions = array();
			foreach ( \IPS\Db::i()->select( '*', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $item->id, 'ssupload' ) ) as $ss )
			{
				$screenshotOptions[ $ss['record_id'] ] = \IPS\File::get( 'downloads_Screenshots', $ss['record_location'] )->url;
			}

			if ( count( $screenshotOptions ) > 1 )
			{
				$return['primary_screenshot'] = new \IPS\Helpers\Form\Radio( 'file_primary_screenshot', $item->_primary_screenshot, FALSE, array( 'options' => $screenshotOptions, 'parse' => 'image' ) );
			}
		}
		
		/* Nexus Integration */
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on and \IPS\Member::loggedIn()->group['idm_add_paid'] )
		{
			$options = array(
				'free'		=> 'file_free',
				'paid'		=> 'file_paid',
			);
			if ( \IPS\Member::loggedIn()->isAdmin() )
			{
				$options['nexus'] = 'file_associate_nexus';
			}
			
			$return['file_cost_type'] = new \IPS\Helpers\Form\Radio( 'file_cost_type', $item ? ( $item->cost ? 'paid' : ( $item->nexus ? 'nexus' : 'free' ) ) : 'free', TRUE, array(
				'options'	=> $options,
				'toggles'	=> array(
					'paid'		=> array( 'file_cost', 'file_renewals' ),
					'nexus'		=> array( 'file_nexus' )
				)
			) );
			
			$commissionBlurb = NULL;
			$fees = NULL;
			if ( $_fees = json_decode( \IPS\Settings::i()->idm_nexus_transfee, TRUE ) )
			{
				$fees = array();
				foreach ( $_fees as $fee )
				{
					$fees[] = (string) ( new \IPS\nexus\Money( $fee['amount'], $fee['currency'] ) );
				}
				$fees = \IPS\Member::loggedIn()->language()->formatList( $fees, \IPS\Member::loggedIn()->language()->get('or_list_format') );
			}
			if ( \IPS\Settings::i()->idm_nexus_percent and $fees )
			{
				$commissionBlurb = \IPS\Member::loggedIn()->language()->addToStack( 'file_cost_desc_both', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->idm_nexus_percent, $fees ) ) );
			}
			elseif ( \IPS\Settings::i()->idm_nexus_percent )
			{
				$commissionBlurb = \IPS\Member::loggedIn()->language()->addToStack('file_cost_desc_percent', FALSE, array( 'sprintf' => \IPS\Settings::i()->idm_nexus_percent ) );
			}
			elseif ( $fees )
			{
				$commissionBlurb = \IPS\Member::loggedIn()->language()->addToStack('file_cost_desc_fee', FALSE, array( 'sprintf' => $fees ) );
			}
			
			\IPS\Member::loggedIn()->language()->words['file_cost_desc'] = $commissionBlurb;			
			$return['file_cost'] = new \IPS\nexus\Form\Money( 'file_cost', $item ? json_decode( $item->cost, TRUE ) : array(), NULL, array(), NULL, NULL, NULL, 'file_cost' );
			$return['file_renewals']  = new \IPS\Helpers\Form\Radio( 'file_renewals', $item ? ( $item->renewal_term ? 1 : 0 ) : 0, TRUE, array(
				'options'	=> array( 0 => 'file_renewals_off', 1 => 'file_renewals_on' ),
				'toggles'	=> array( 1 => array( 'file_renewal_term' ) )
			), NULL, NULL, NULL, 'file_renewals' );
			\IPS\Member::loggedIn()->language()->words['file_renewal_term_desc'] = $commissionBlurb;
			$renewTermForEdit = NULL;
			if ( $item and $item->renewal_term )
			{
				$renewPrices = array();
				foreach ( json_decode( $item->renewal_price, TRUE ) as $currency => $data )
				{
					$renewPrices[ $currency ] = new \IPS\nexus\Money( $data['amount'], $currency );
				}
				$renewTermForEdit = new \IPS\nexus\Purchase\RenewalTerm( $renewPrices, new \DateInterval( 'P' . $item->renewal_term . mb_strtoupper( $item->renewal_units ) ) );
			}
			$return['file_renewal_term'] = new \IPS\nexus\Form\RenewalTerm( 'file_renewal_term', $renewTermForEdit, NULL, array( 'allCurrencies' => TRUE ), NULL, NULL, NULL, 'file_renewal_term' );
			
			if ( \IPS\Member::loggedIn()->isAdmin() )
			{
				$return['file_nexus'] = new \IPS\Helpers\Form\Node( 'file_nexus', $item ? $item->nexus : array(), FALSE, array( 'class' => '\IPS\nexus\Package', 'multiple' => TRUE ), NULL, NULL, NULL, 'file_nexus' );
			}
		}
		
		/* Custom Fields */
		$customFieldValues = $item ? $item->customFields() : array();
		foreach ( $container->cfields as $k => $field )
		{
			$return[] = $field->buildHelper( isset( $customFieldValues[ "field_{$k}" ] ) ? $customFieldValues[ "field_{$k}" ] : NULL );
		}

		if( $item )
		{
			$return['versioning']	= new \IPS\Helpers\Form\Custom( 'file_versioning_info', NULL, FALSE, array( 'getHtml' => function( $element ) use ( $item )
			{
				return \IPS\Theme::i()->getTemplate( 'submit' )->editDetailsInfo( $item );
			} ) );
		}
		
		return $return;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		parent::processForm( $values );

		if ( !$this->_new )
		{
			$oldContent = $this->desc;
		}
		$this->desc	= $values['file_desc'];
		if ( !$this->_new )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}

		if( isset( $values['file_primary_screenshot'] ) )
		{
			$this->primary_screenshot	= (int) $values['file_primary_screenshot'];
		}
		
		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on and \IPS\Member::loggedIn()->group['idm_add_paid'] )
		{
			switch ( $values['file_cost_type'] )
			{
				case 'free':
					$this->cost = NULL;
					$this->renewal_term = 0;
					$this->renewal_units = NULL;
					$this->renewal_price = NULL;
					$this->nexus = NULL;
					break;
				
				case 'paid':
					$this->cost = json_encode( $values['file_cost'] );
					if ( $values['file_renewals'] and $values['file_renewal_term'] )
					{						
						$term = $values['file_renewal_term']->getTerm();
						$this->renewal_term = $term['term'];
						$this->renewal_units = $term['unit'];
						$this->renewal_price = json_encode( $values['file_renewal_term']->cost );
					}
					else
					{
						$this->renewal_term = 0;
						$this->renewal_units = NULL;
						$this->renewal_price = NULL;
					}
					$this->nexus = NULL;
					break;
				
				case 'nexus':
					$this->cost = NULL;
					$this->renewal_term = 0;
					$this->renewal_units = NULL;
					$this->renewal_price = NULL;
					$this->nexus = implode( ',', array_keys( $values['file_nexus'] ) );
					break;
			}
		}
		
		$this->save();
		$cfields = array();
		foreach ( $this->container()->cfields as $field )
		{
			$helper							 = $field->buildHelper();
			$cfields[ "field_{$field->id}" ] = $helper::stringValue( $values[ "downloads_field_{$field->id}" ] );
			
			if ( $helper instanceof \IPS\Helpers\Form\Editor )
			{
				$field->claimAttachments( $this->id );
			}
		}
		
		if ( !empty( $cfields ) )
		{
			\IPS\Db::i()->insert( 'downloads_ccontent', array_merge( array( 'file_id' => $this->id, 'updated' => time() ), $cfields ), TRUE );
		}
		
		/* Update Category */
		$this->container()->setLastFile();
		$this->container()->save();
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( $values )
	{
		/* Set version */
		$this->version = $values['file_version'];
		
		/* Try to set the primary screenshot */
		try
		{
			$this->primary_screenshot = \IPS\Db::i()->select( 'record_id', 'downloads_files_records', array( 'record_post_key=? AND record_type=? AND record_backup=0', $values['postKey'], 'ssupload' ), 'record_default DESC, record_id ASC' )->first();
		}
		catch ( \Exception $e ) { }

		parent::processBeforeCreate( $values );
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( $comment, $values )
	{
		\IPS\File::claimAttachments( 'downloads-new-file', $this->id );
		
		if ( $this->_primary_screenshot )
		{
			\IPS\Db::i()->update( 'downloads_files_records', array( 'record_default' => 1 ), array( 'record_id=?', $this->_primary_screenshot ) );
		}
		\IPS\Db::i()->update( 'downloads_files_records', array( 'record_file_id' => $this->id, 'record_post_key' => NULL ), array( 'record_post_key=?', $values['postKey'] ) );
		$this->size = (int) \IPS\Db::i()->select( 'SUM(record_size)', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_backup=0', $this->id, 'upload' ) )->first();
		$this->save();

		parent::processAfterCreate( $comment, $values );
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( $topic = $this->topic() and $this->container()->bitoptions['topic_delete'] )
		{
			$topic->delete();
		}
		
		if ( \IPS\Application::appIsEnabled( 'nexus' ) )
		{
			\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_cancelled' => TRUE, 'ps_can_reactivate' => FALSE ), array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'downloads', 'file', $this->id ) );
		}
		
		parent::delete();
				
		foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=?', $this->id, 'upload' ) ), 'downloads_Files' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) { }
		}

		foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_location', 'downloads_files_records', array( 'record_file_id=? AND record_type=?', $this->id, 'ssupload' ) ), 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) { }
		}

		foreach ( new \IPS\File\Iterator( \IPS\Db::i()->select( 'record_thumb', 'downloads_files_records', array( 'record_file_id=? AND record_type=? AND record_thumb IS NOT NULL', $this->id, 'ssupload' ) ), 'downloads_Screenshots' ) as $file )
		{
			try
			{
				$file->delete();
			}
			catch ( \Exception $e ) { }
		}
		
		\IPS\Db::i()->delete( 'downloads_ccontent', array( 'file_id=?', $this->id ) );
		\IPS\Db::i()->delete( 'downloads_downloads', array( 'dfid=?', $this->id ) );
		\IPS\Db::i()->delete( 'downloads_filebackup', array( 'b_fileid=?', $this->id ) );
		\IPS\Db::i()->delete( 'downloads_files_records', array( 'record_file_id=?', $this->id ) );
		
		/* Update Category */
		$this->container()->setLastFile();
		$this->container()->save();
	}
	
	/**
	 * URL Blacklist Check
	 *
	 * @param	array	$val	URLs to check
	 * @return	void
	 * @throws	\DomainException
	 */
	public static function blacklistCheck( $val )
	{
		if ( is_array( $val ) )
		{
			foreach ( explode( ',', \IPS\Settings::i()->idm_link_blacklist ) as $blackListedDomain )
			{
				foreach ( array_filter( $val ) as $url )
				{
					if ( mb_substr( $url->data['host'], -mb_strlen( $blackListedDomain ) ) == $blackListedDomain )
					{
						throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'err_url_file_blacklist', FALSE, array( 'sprintf' => $blackListedDomain ) ) );
					}
				}
			}
		}
	}
		
	/* !Tags */
	
	/**
	 * Can tag?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canTag( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::canTag( $member, $container ) and ( $container === NULL or !$container->tags_disabled );
	}
	
	/**
	 * Can use prefixes?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	bool
	 */
	public static function canPrefix( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		return parent::canPrefix( $member, $container ) and ( $container === NULL or !$container->tags_noprefixes );
	}
	
	/**
	 * Defined Tags
	 *
	 * @param	\IPS\Node\Model|NULL	$container	The container to check if tags can be used in, if applicable
	 * @return	array
	 */
	public static function definedTags( \IPS\Node\Model $container = NULL )
	{
		if ( $container and $container->tags_predefined )
		{
			return explode( ',', $container->tags_predefined );
		}
		
		return parent::definedTags( $container );
	}
	
	/* !Followers */
	
	/**
	 * Users to receive immediate notifications (bulk)
	 *
	 * @param	\IPS\downloads\Category	$category	The category the files were posted in.
	 * @param	\IPS\Member|NULL		$member		The member posting the files or NULL for currently logged in member.
	 * @param	int|array				$limit		LIMIT clause
	 * @return	\IPS\Db\Select
	 */
	public static function _notificationRecipients( $category, $member=NULL, $limit=array( 0, 25 ) )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		$unions = array( static::containerFollowers( $category, 3, array( 'immediate' ), NULL, $limit, 'follow_added', TRUE, NULL ) );
		
		if ( $followersQuery = $member->followers( 3, array( 'immediate' ), NULL, NULL, NULL, NULL ) )
		{
			$unions[] = $followersQuery;
		}
		
		return \IPS\Db::i()->union( $unions, NULL, NULL, NULL, FALSE, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
	}
	
	/**
	 * Send Notifications (bulk)
	 *
	 * @param	\IPS\downloads\Category	$category	The category the files were posted in.
	 * @param	\IPS\Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @return	void
	 */
	public static function _sendNotifications( $category, $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		try
		{
			$count = static::_notificationRecipients( $category, $member )->count( TRUE );
		}
		catch( \BadMethodCallException $e )
		{
			return;
		}
		
		$categoryIdColumn	= $category::$databaseColumnId;
		
		if ( $count > static::NOTIFICATIONS_PER_BATCH )
		{
			$queueData = array();
			$queueData['category_id']	= $category->$categoryIdColumn;
			$queueData['member_id']		= $member->member_id;
			
			\IPS\Task::queue( 'downloads', 'Follow', $queueData );
		}
		else
		{
			static::_sendNotificationsBatch( $category, $member );
		}
	}
	
	/**
	 * Send Unapproved Notification (bulk)(
	 *
	 * @param	\IPS\downloads\Category	$category	The category the files were posted too.
	 * @param	\IPS\Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @return	void
	 */
	public static function _sendUnapprovedNotifications( $category, $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		$moderators = array( 'g' => array(), 'm' => array() );
		foreach( \IPS\Db::i()->select( '*', 'core_moderators' ) AS $mod )
		{
			$canView = FALSE;
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );
				
				if ( isset( $perms['can_view_hidden_content'] ) AND $perms['can_view_hidden_content'] )
				{
					$canView = TRUE;
				}
				else if ( isset( $perms['can_view_hidden_' . static::$title ] ) AND $perms['can_view_hidden_' . static::$title ] )
				{
					$canView = TRUE;
				}
			}
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}
		
		$notification = new \IPS\Notification( \IPS\Application::load('core'), 'unapproved_content_bulk', $category, array( $category, $member, $category::$contentItemClass ) );
		foreach ( \IPS\Db::i()->select( '*', 'core_members', ( count( $moderators['m'] ) ? \IPS\Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . \IPS\Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . \IPS\Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) ) as $moderator )
		{
			$notification->recipients->attach( \IPS\Member::constructFromData( $moderator ) );
		}
		$notification->send();
	}
	
	/**
	 * Send Notification Batch (bulk)
	 *
	 * @param	\IPS\downloads\Category	$category	The category the files were posted too.
	 * @param	\IPS\Member|NULL		$member		The member posting the images, or NULL for currently logged in member.
	 * @param	int						$offset		Offset
	 * @return	int|NULL				New Offset or NULL if complete
	 */
	public static function _sendNotificationsBatch( $category, $member=NULL, $offset=0 )
	{
		$member				= $member ?: \IPS\Member::loggedIn();
		
		$followIds = array();
		$followers = static::_notificationRecipients( $category, $member, array( $offset, static::NOTIFICATIONS_PER_BATCH ) );
		
		$notification = new \IPS\Notification( \IPS\Application::load( 'core' ), 'new_content_bulk', $category, array( $category, $member, $category::$contentItemClass ), $member->member_id );
		
		foreach( $followers AS $follower )
		{
			$followMember = \IPS\Member::load( $follower['follow_member_id'] );
			if ( $followMember != $member and $category->can( 'view', $followMember ) )
			{
				$followIds[] = $follower['follow_id'];
				$notification->recipients->attach( $followMember );
			}
		}
		$notification->send();
		
		\IPS\Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), \IPS\Db::i()->in( 'follow_id', $followIds ) );
		
		$newOffset = $offset + static::NOTIFICATIONS_PER_BATCH;
		if ( $newOffset > $followers->count( TRUE ) )
		{
			return NULL;
		}
		return $newOffset;
	}
	
	/**
	 * @brief	Is first time approval
	 */
	protected $firstTimeApproval = FALSE;
	
	/**
	 * Unhide
	 *
	 * @param	\IPS\Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @return	void
	 */
	public function unhide( $member=NULL )
	{
		if ( $this->hidden() === 1 )
		{
			$this->firstTimeApproval = TRUE;
		}
		
		parent::unhide( $member );
	}
	
	/**
	 * Send Approved Notification
	 *
	 * @return	void
	 */
	public function sendApprovedNotification()
	{
		if ( $this->firstTimeApproval )
		{
			$this->sendNotifications();
		}
		else
		{
			$this->sendUpdateNotifications();
		}
	}
	
	/**
	 * Send notifications that the file has been updated
	 *
	 * @return	void
	 */
	public function sendUpdateNotifications()
	{		
		try
		{
			$count = $this->notificationRecipients( array( 0, 25 ), 'update' )->count( TRUE );
		}
		catch ( \BadMethodCallException $e )
		{
			return;
		}	
				
		if ( $count > static::NOTIFICATIONS_PER_BATCH )
		{
			$idColumn = static::$databaseColumnId;
			\IPS\Task::queue( 'core', 'Follow', array( 'class' => get_class( $this ), 'item' => $this->$idColumn, 'extra' => 'update', 'exclude' => array() ) );
		}
		else
		{
			$this->sendNotificationsBatch( 0, array(), 'update' );
		}
	}
	
	/**
	 * Users to receieve immediate notifications
	 *
	 * @param	int|array		$limit	LIMIT clause
	 * @param	string|NULL		$extra		Additional data
	 * @return \IPS\Db\Select
	 */
	public function notificationRecipients( $limit=array( 0, 25 ), $extra=NULL )
	{
		$memberFollowers = $this->author()->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL, NULL, NULL );
		
		if( count( $memberFollowers ) )
		{
			$unions	= array( 
				( $extra === 'update' ? $this->followers( static::FOLLOW_PUBLIC + static::FOLLOW_ANONYMOUS, array( 'immediate' ), NULL, NULL, NULL,0 ) : static::containerFollowers( $this->container(), 3, array( 'immediate' ), $this->mapped('date'), NULL, NULL, 0 ) ),
				$memberFollowers
			);
		
			return \IPS\Db::i()->union( $unions, 'follow_added', $limit, 'follow_member_id', FALSE, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
		}
		else
		{
			return $extra === 'update' ? $this->followers( static::FOLLOW_PUBLIC + static::FOLLOW_ANONYMOUS, array( 'immediate' ), NULL, $limit, 'follow_added', \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ) : static::containerFollowers( $this->container(), 3, array( 'immediate' ), $this->mapped('date'), $limit, 'follow_added', \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
		}
	}
	
	/**
	 * Create Notification
	 *
	 * @param	string|NULL		$extra		Additional data
	 * @return	\IPS\Notification
	 */
	protected function createNotification( $extra=NULL )
	{
		// New content is sent with itself as the item as we deliberately do not group notifications about new content items. Unlike comments where you're going to read them all - you might scan the notifications list for topic titles you're interested in
		if ( $extra === 'update' )
		{
			return new \IPS\Notification( \IPS\Application::load( 'downloads' ), 'new_file_version', $this, array( $this ), array(), \IPS\Member::loggedIn() );
		}
		else
		{
			return new \IPS\Notification( \IPS\Application::load( 'core' ), 'new_content', $this, array( $this ) );
		}
	}
	
	/* !IP.Board Integration */
	
	/**
	 * Create from form
	 *
	 * @param	array					$values		Values from form
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param	bool					$sendNotification	TRUE to automatically send new content notifications (useful for items that may be uploaded in bulk)
	 * @return	\IPS\Content\Item
	 */
	public static function createFromForm( $values, \IPS\Node\Model $container = NULL, $sendNotification = TRUE )
	{
		$file = parent::createFromForm( $values, $container, $sendNotification );
		if ( \IPS\Application::appIsEnabled('forums') and $file->container()->forum_id and !$file->hidden() )
		{
			$file->syncTopic();
		}
		return $file;
	}
	
	/**
	 * Process after the object has been edited on the front-end
	 *
	 * @param	array	$values		Values from form
	 * @return	void
	 */
	public function processAfterEdit( $values )
	{
		if ( \IPS\Application::appIsEnabled('forums') and $this->topic() )
		{
			$this->syncTopic();
		}

		parent::processAfterEdit( $values );
	}
	
	/**
	 * Move
	 *
	 * @param	\IPS\Node\Model	$container	Container to move to
	 * @param	bool			$keepLink	If TRUE, will keep a link in the source
	 * @return	void
	 */
	public function move( \IPS\Node\Model $container, $keepLink=FALSE )
	{
		$oldCategory = $this->container();

		parent::move( $container, $keepLink );
		if ( \IPS\Application::appIsEnabled('forums') and $topic = $this->topic() )
		{
			/* If the old category didn't sync, but the new one does, create the topic */
			if ( !$oldCategory->forum_id and $this->container()->forum_id )
			{
				$this->syncTopic();
			}
			
			/* If both the old and the new categories sync, but to different forums, move the topic, unless it's been moved manually */
			elseif ( $oldCategory->forum_id and $this->container()->forum_id and $oldCategory->forum_id != $this->container()->forum_id and $topic->forum_id == $oldCategory->forum_id )
			{
				try
				{
					$topic->move( \IPS\forums\Forum::load( $this->container()->forum_id ), $keepLink );
				}
				catch ( \Exception $e ) { }
			}
		}
	}
	
	/**
	 * Syncing to run when hiding
	 *
	 * @return	void
	 */
	public function onHide()
	{
		parent::onHide();
		if ( \IPS\Application::appIsEnabled('forums') and $topic = $this->topic() )
		{
			$topic->hide();
		}
	}
	
	/**
	 * Syncing to run when unhiding
	 *
	 * @param	bool	$approving	If true, is being approved for the first time
	 * @return	void
	 */
	public function onUnhide( $approving )
	{
		parent::onUnhide( $approving );
		if ( \IPS\Application::appIsEnabled('forums') )
		{
			if ( $topic = $this->topic() )
			{
				$topic->unhide();
			}
			elseif ( $approving and $this->container()->forum_id )
			{
				$this->syncTopic();
			}
		}
	}
	
	/**
	 * Get Topic (checks member's permissions)
	 *
	 * @param	bool	$checkPerms		Should check if the member can read the topic?
	 * @return	\IPS\forums\Topic|NULL
	 */
	public function topic( $checkPerms=TRUE )
	{
		if ( \IPS\Application::appIsEnabled('forums') and $this->topicid )
		{
			try
			{
				return $checkPerms ? \IPS\forums\Topic::loadAndCheckPerms( $this->topicid ) : \IPS\forums\Topic::load( $this->topicid );
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Create/Update Topic
	 *
	 * @return	void
	 */
	protected function syncTopic()
	{
		/* Existing topic */
		if ( $this->topicid )
		{
			/* Get */
			try
			{
				$topic = \IPS\forums\Topic::load( $this->topicid );
				if ( !$topic )
				{
					return;
				}
				$title = $this->container()->_topic_prefix . $this->name . $this->container()->_topic_suffix;
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $title );
				$topic->title = $title;
				if ( \IPS\Settings::i()->tags_enabled )
				{
					$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
				}
				$topic->save();
				$firstPost = $topic->comments( 1 );
				$content = \IPS\Theme::i()->getTemplate( 'submit', 'downloads', 'front' )->topic( $this );
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );
				$firstPost->post = $content;
				$firstPost->save();
			}
			catch ( \OutOfRangeException $e )
			{
				return;
			}
		}
		/* New topic */
		else
		{
			/* Create topic */
			$topic = \IPS\forums\Topic::createItem( $this->author(), $this->ipaddress, \IPS\DateTime::ts( $this->submitted ), \IPS\forums\Forum::load( $this->container()->forum_id ), $this->hidden() );
			$title = $this->container()->_topic_prefix . $this->name . $this->container()->_topic_suffix;
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $title );
			$topic->title = $title;
			$topic->topic_archive_status = \IPS\forums\Topic::ARCHIVE_EXCLUDE;
			$topic->save();

			if ( \IPS\Settings::i()->tags_enabled )
			{
				$topic->setTags( $this->prefix() ? array_merge( $this->tags(), array( 'prefix' => $this->prefix() ) ) : $this->tags() );
			}
			
			/* Create post */
			$content = \IPS\Theme::i()->getTemplate( 'submit', 'downloads', 'front' )->topic( $this );
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );
			$post = \IPS\forums\Topic\Post::create( $topic, $content, TRUE, NULL, NULL, $this->author() );
			$topic->topic_firstpost = $post->pid;
			$topic->save();
			
			/* Update file */
			$this->topicid = $topic->tid;
			$this->save();
		}
	}
	
	/* !Embeddable */
	
	/**
	 * Get image for embed
	 *
	 * @return	\IPS\File|NULL
	 */
	public function embedImage()
	{
		return $this->primary_screenshot_thumb ? \IPS\File::get( 'downloads_Screenshots', $this->primary_screenshot_thumb ) : NULL;
	}

	/**
	 * If, when making a post, we should merge with an existing comment, this method returns the comment to merge with
	 *
	 * @return	\IPS\Content\Comment|NULL
	 */
	public function mergeConcurrentComment()
	{
		$lastComment = parent::mergeConcurrentComment();

		/* If we sync to the forums, make sure that the "last comment" is not actually the first post */
		if( $this->topicid AND $lastComment !== NULL )
		{
			$firstComment = \IPS\forums\Topic::load( $this->topicid )->comments( 1, 0, 'date', 'asc' );

			if( $firstComment->pid == $lastComment->pid )
			{
				return NULL;
			}
		}

		return $lastComment;
	}
}
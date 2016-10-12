<?php
/**
 * @brief		Google Maps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		19 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\GeoLocation\Maps;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Google Maps
 */
class _Google
{	
	/**
	 * @brief	GeoLocation
	 */
	public $geoLocation;

	/**
	 * Constructor
	 *
	 * @param	\IPS\GeoLocation	$geoLocation	Location
	 * @return	void
	 */
	public function __construct( \IPS\GeoLocation $geoLocation )
	{
		$this->geolocation	= $geoLocation;
	}
	
	/**
	 * Render
	 *
	 * @param	int	$width	Width
	 * @param	int	$heigth	Height
	 * @return	string
	 */
	public function render( $width, $height, $zoom=5 )
	{
		if ( $this->geolocation->lat and $this->geolocation->long )
		{
			$location = sprintf( "%F", $this->geolocation->lat ) . ',' . sprintf( "%F", $this->geolocation->long );
		}
		else
		{
			$zoom = 1;
			$location = array();
			foreach ( array( 'country', 'region', 'city', 'addressLines' ) as $k )
			{
				if ( $this->geolocation->$k )
				{
					$zoom++;
					if ( is_array( $this->geolocation->$k ) )
					{
						foreach ( array_reverse( $this->geolocation->$k ) as $v )
						{
							$location[] = $v;
						}
					}
					else
					{
						$location[] = $this->geolocation->$k;
					}
				}
			}
			$location = implode( ', ', array_reverse( $location ) );
		}
		
		$linkUrl = \IPS\Http\Url::external( '//maps.google.com/' )->setQueryString( 'q', $location );
		
		$imageUrl = \IPS\Http\Url::external( '//maps.googleapis.com/maps/api/staticmap' )->setQueryString( array(
			'center'	=> $location,
			'zoom'		=> $zoom,
			'size'		=> "{$width}x{$height}",
			'sensor'	=> 'false',
			'markers'	=> $location
		) );
		
		return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->googleMap( $linkUrl, $imageUrl, $this->geolocation->lat, $this->geolocation->long );
	}
}
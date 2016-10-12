<?php
/**
 * @brief		File Handler: Amazon S3
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		12 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\File;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Handler: Amazon S3
 */
class _Amazon extends \IPS\File
{
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	array	$configuration		Configuration if editing a setting, or array() if creating a setting.
	 * @return	array
	 */
	public static function settings( $configuration=array() )
	{
		$default = ( isset( $configuration['custom_url'] ) and ! empty( $configuration['custom_url'] ) ) ? TRUE : FALSE;
		
		return array(
			'bucket'		=> 'Text',
			'bucket_path'   => 'Text',
			'access_key'	=> 'Text',
			'secret_key'	=> 'Text',
			'toggle'	 => array( 'type' => 'YesNo', 'default' => $default, 'options' => array(
				'togglesOn' => array( 'Amazon_custom_url' )
			) ),
			'custom_url' => array( 'type' => 'Text', 'default' => '' )
		);
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$values	The submitted values
	 * @return	void
	 * @throws	\LogicException
	 */
	public static function testSettings( &$values )
	{
		$values['bucket_path'] = trim( $values['bucket_path'], '/' );
		$values['bucket'] = trim( $values['bucket'], '/' );
		
		$filename = md5( uniqid() ) . '.ips.txt';
		
		try
		{
			$response = static::makeRequest( "test/{$filename}", 'PUT', $values, NULL, "OK" );
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'file_storage_test_error_amazon_unreachable', FALSE, array( 'sprintf' => array( $values['bucket'] ) ) ) );
		}
		
		if ( $response->httpResponseCode != 200 AND $response->httpResponseCode != 307 )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'file_storage_test_error_amazon', FALSE, array( 'sprintf' => array( $values['bucket'], $response->httpResponseCode ) ) ) );
		}

		$response = static::makeRequest( "test/{$filename}", 'DELETE', $values, NULL );
		
		if ( $response->httpResponseCode == 403 )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'file_storage_test_error_amazon_d403', FALSE, array( 'sprintf' => array( $values['bucket'], $response->httpResponseCode ) ) ) );
		}
		
		if ( ! $values['toggle'] )
		{
			$values['custom_url'] = NULL;
		}
		
		if ( ! empty( $values['custom_url'] ) )
		{
			if ( mb_substr( $values['custom_url'], 0, 2 ) !== '//' AND mb_substr( $values['custom_url'], 0, 4 ) !== 'http' )
			{
				$values['custom_url'] = '//' . $values['custom_url'];
			}
			
			$test = $values['custom_url'];
			
			if ( mb_substr( $test, 0, 2 ) === '//' )
			{
				$test = 'http:' . $test;
			}
			
			if ( filter_var( $test, FILTER_VALIDATE_URL ) === false )
			{
				throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'url_is_not_real', FALSE, array( 'sprintf' => array( $values['custom_url'] ) ) ) );
			}
		}
	}
	
	/**
	 * Determine if the change in configuration warrants a move process
	 *
	 * @param	array		$configuration	    New Storage configuration
	 * @param	array		$oldConfiguration   Existing Storage Configuration
	 * @return	boolean
	 */
	public static function moveCheck( $configuration, $oldConfiguration )
	{
		foreach( array( 'bucket', 'bucket_path' ) as $field )
		
		if ( $configuration[ $field ] !== $oldConfiguration[ $field ] )
		{
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Display name
	 *
	 * @param	array	$settings	Configuration settings
	 * @return	string
	 */
	public static function displayName( $settings )
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'filehandler_display_name', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('filehandler__Amazon'), $settings['bucket'] ) ) );
	}

	/* !File Handling */

	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Storage configuration
	 * @return	void
	 */
	public function __construct( $configuration )
	{
		$this->container = 'monthly_' . date( 'Y' ) . '_' . date( 'm' );
		parent::__construct( $configuration );
	}
	
	/**
	 * Return the base URL
	 *
	 * @return string
	 */
	public function baseUrl()
	{
		return preg_replace( '#^http(s)?://#', '//', ( empty( $this->configuration['custom_url'] ) ) ? rtrim( static::buildBaseUrl( $this->configuration ), '/' ) : $this->configuration['custom_url'] );
	}
	
	/**
	 * Save File
	 *
	 * @return	void
	 */
	public function save()
	{
		/* @link http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingMetadata.html regarding cleaning filenames */
		if ( preg_match( '#[^a-zA-Z0-9!\-_\.\*\(\)]#', $this->filename ) )
		{
			$this->filename = uniqid() . '_' . preg_replace( '#[^a-zA-Z0-9!\-_\.\*\(\)]#', '', $this->filename );
		}

		$this->container = trim( $this->container, '/' );
		$this->url = static::buildBaseUrl( $this->configuration ) . "{$this->container}/{$this->filename}";
		$response  = static::makeRequest( "{$this->container}/{$this->filename}", 'PUT', $this->configuration, $this->configurationId, (string) $this->contents() );

		if ( $response->httpResponseCode != 200 )
		{
			throw new \RuntimeException('COULD_NOT_SAVE_FILE');
		}
	}
	
	/**
	 * Get Contents
	 *
	 * @param	bool	$refresh	If TRUE, will fetch again
	 * @return	string
	 */
	public function contents( $refresh=FALSE )
	{
		if ( $this->contents === NULL or $refresh === TRUE )
		{
			$this->contents = (string) static::makeRequest( "{$this->container}/{$this->filename}", 'GET', $this->configuration, $this->configurationId );
		}
		return $this->contents;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		$this->container = trim( $this->container, '/' );

		$response = static::makeRequest( "{$this->container}/{$this->filename}", 'DELETE', $this->configuration, $this->configurationId );
		
		if ( $response->httpResponseCode == 204 )
		{
			/* Ok */
			return;
		}
		
		if ( $response->httpResponseCode != 200 )
		{
			$this->log( 'COULD_NOT_DELETE_FILE', 'delete', array( $response->httpResponseCode, $response->httpResponseText ), 'error' );
			throw new \RuntimeException('COULD_NOT_DELETE_FILE');
		}
	}
	
	/**
	 * Delete Container
	 *
	 * @param	string	$container	Key
	 * @return	void
	 */
	public function deleteContainer( $container )
	{
		$_strip = array( '_strip_querystring' => TRUE, 'bucket_path' => NULL );
	
		if ( $this->configuration['bucket_path'] )
		{
			$container = $this->configuration['bucket_path'] . '/' . $container;
		}
		
		$response = static::makeRequest( "?prefix=" . urlencode( $container . "/" ), 'GET', array_merge( $this->configuration, $_strip ), $this->configurationId );
		
		/* Parse XML document */
		$document = \IPS\Xml\SimpleXML::loadString( $response );
		
		/* Loop over dom document */
		foreach( $document->Contents as $result )
		{
			if ( $this->configuration['bucket_path'] )
			{
				$result->Key = mb_substr( $result->Key, ( mb_strlen( $this->configuration['bucket_path'] ) + 1 ) );
			}
			
			static::makeRequest( $result->Key, 'DELETE', $this->configuration, $this->configurationId );
		}
	}

	/* !Amazon Utility Methods */
	
	/**
	 * Sign and make request
	 *
	 * @param	string		$uri				The URI (relative to the bucket)
	 * @param	string		$verb				The HTTP verb to use
	 * @param	array 		$configuration		The configuration for this instance
	 * @param	int			$configurationId	The configuration ID
	 * @param	string|null	$content			The content to send
	 * @return	\IPS\Http\Response
	 * @throws	\IPS\Http\Request\Exception
	 */
	protected static function makeRequest( $uri, $verb, $configuration, $configurationId, $content=NULL )
	{
		/* Build a request */
		$request = \IPS\Http\Url::external( static::buildBaseUrl( $configuration ) . $uri )->request();
		$date    = date('r');
		$headers = array( 'Date' => $date, 'Content-Type' => \IPS\File::getMimeType( $uri ), 'Content-MD5' => base64_encode( md5( $content, TRUE ) ), 'x-amz-acl' => 'public-read' );

		if( mb_strtoupper( $verb ) === 'PUT' )
		{
			$headers['Content-Length']	= \strlen( $content );
		}

		/* We need to strip query string parameters for the signature, but not always (e.g. a subresource such as ?acl needs to be included and multi-
			object delete requests must include the query string params).  Let the callee decide to do this or not. */
		if( isset( $configuration['_strip_querystring'] ) AND $configuration['_strip_querystring'] === TRUE )
		{
			$uri	= preg_replace( "/^(.*?)\?.*$/", "$1", $uri );
		}
	
		$string = implode( "\n", array(
			mb_strtoupper( $verb ),
			$headers['Content-MD5'],
			$headers['Content-Type'],
			$date,
			"x-amz-acl:{$headers['x-amz-acl']}",
			"/{$configuration['bucket']}" . ( ( isset( $configuration['bucket_path'] ) AND ! empty( $configuration['bucket_path'] ) ) ? '/' . trim( $configuration['bucket_path'], '/' ) . '/' : '/' ) . $uri
		) );
		
		/* Build a signature */
		$signature = base64_encode( hash_hmac( 'sha1', $string, $configuration['secret_key'], true ) );

		/* Sign the request */
		$headers['Authorization'] = "AWS {$configuration['access_key']}:{$signature}";
		$request->setHeaders( $headers );

		/* Make the request */
		$verb = mb_strtolower( $verb );

		$response = $request->$verb( $content );

		/* Need a different region? */
		if ( $response->httpResponseCode == 307 AND $configurationId )
		{
			$xml = $response->decodeXml();
			$configuration['region'] = mb_substr( $xml->Endpoint, mb_strlen( $configuration['bucket'] ) + 1, -mb_strlen( '.amazonaws.com' ) );
			\IPS\Db::i()->update( 'core_file_storage', array( 'configuration' => json_encode( $configuration ) ), array( 'id=?', $configurationId ) );
			unset( \IPS\Data\Store::i()->storageConfigurations );
			return static::makeRequest( $uri, $verb, $configuration, $configurationId, $content );
		}

		/* Return */
		return $response;
	}

	/**
	 * Build up the base Amazon URL
	 * @param   array   $configuration  Configuration data
	 * @return string
	 */
	public static function buildBaseUrl( $configuration )
	{
		return (
			\IPS\Request::i()->isSecure() ? "https" : "http" )
			. "://{$configuration['bucket']}"
			. ( ( isset( $configuration['region'] ) AND ! empty( $configuration['region'] ) ) ? ".{$configuration['region']}" : '.s3' )
			. ".amazonaws.com"
			. ( ( isset( $configuration['bucket_path'] ) AND ! empty( $configuration['bucket_path'] ) ) ? '/' . trim( $configuration['bucket_path'], '/' ) . '/' : '/' );
	}

	/**
	 * Remove orphaned files
	 *
	 * @param	int			$fileIndex		The file offset to start at in a listing
	 * @param	array	$engines	All file storage engine extension objects
	 * @return	array
	 */
	public function removeOrphanedFiles( $fileIndex, $engines )
	{
		/* Start off our results array */
		$results	= array(
			'_done'				=> FALSE,
			'fileIndex'			=> $fileIndex,
		);
				
		$checked	= 0;
		$skipped	= 0;
		$_strip		= array( '_strip_querystring' => TRUE, 'bucket_path' => NULL );

		if( $fileIndex )
		{
			$response	= static::makeRequest( "?marker={$fileIndex}&max-keys=100", 'GET', array_merge( $this->configuration, $_strip ), $this->configurationId );
		}
		else
		{
			$response	= static::makeRequest( "?max-keys=100", 'GET', array_merge( $this->configuration, $_strip ), $this->configurationId );
		}

		/* Parse XML document */
		$document	= \IPS\Xml\SimpleXML::loadString( $response );

		/* Loop over dom document */
		foreach( $document->Contents as $result )
		{
			$checked++;
			
			if ( $this->configuration['bucket_path'] )
			{
				$result->Key = mb_substr( $result->Key, ( mb_strlen( $this->configuration['bucket_path'] ) + 1 ) );
			}
			
			/* Next we will have to loop through each storage engine type and call it to see if the file is valid */
			foreach( $engines as $engine )
			{
				/* If this file is valid for the engine, skip to the next file */
				if( $engine->isValidFile( $result->Key ) )
				{
					continue 2;
				}
			}
			
			/* If we are still here, the file was not valid.  Delete and increment count. */
			$this->logOrphanedFile( $result->Key );

			$_lastKey = $result->Key;
		}

		if( $document->IsTruncated == 'true' AND $checked == 100 )
		{
			$results['fileIndex'] = $_lastKey;
		}

		/* Are we done? */
		if( !$checked OR $checked < 100 )
		{
			$results['_done'] = TRUE;
		}

		return $results;
	}
}
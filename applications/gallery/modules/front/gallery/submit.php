<?php
/**
 * @brief		submit
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Gallery
 * @since		04 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gallery\modules\front\gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * submit
 */
class _submit extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief Number of files per cycle during submission we can process
	 */
	public $perCycle	= 20;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'submit.css' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_submit.js', 'gallery' ) );	

		\IPS\gallery\Image::canCreate( \IPS\Member::loggedIn(), NULL, TRUE );

		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		parent::execute();
	}

	/**
	 * Manage addition of gallery images
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form( 'select_category', 'continue' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Node( 'image_category', isset( \IPS\Request::i()->category ) ? \IPS\Request::i()->category : NULL, TRUE, array(
			'url'					=> \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ),
			'class'					=> 'IPS\gallery\Category',
			'permissionCheck'		=> 'add',
		) ) );

		if ( $values = $form->values() )
		{
			/* If this is going to be a new wizard session, add it to the URL */
			if( isset( \IPS\Request::i()->_new ) )
			{
				$url = \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit&do=submit', 'front', 'gallery_submit' )->setQueryString( array( 'category' => $values['image_category']->_id, '_new' => 1 ) );
			}
			else
			{
				$url = \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit&do=submit', 'front', 'gallery_submit' )->setQueryString( 'category', $values['image_category']->_id );
			}
			
			\IPS\Output::i()->redirect( $url );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'choose_category' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->categorySelector( $form );	
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'select_category' ) );
		
	}

	/**
	 * Submit process for gallery images
	 *
	 * @return	void
	 */
	protected function submit()
	{
		/* Wizard process... */
		$steps 		= array();
		$perCycle	= $this->perCycle;

		/* Build Wizard */
		$url = \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit&do=submit', 'front', 'gallery_submit' );
		if ( isset( \IPS\Request::i()->category ) )
		{
			$url = $url->setQueryString( 'category', \IPS\Request::i()->category );
		}

		/* Get category data */
		try
		{
			$category = \IPS\gallery\Category::loadAndCheckPerms( \IPS\Request::i()->category, 'add' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
		}
		
		/* Album data */
		if( \IPS\Request::i()->album )
		{
			try
			{
				$album = \IPS\gallery\Album::loadAndCheckPerms( \IPS\Request::i()->album );
				$_SESSION['gallery_album'] = array(
					'_type' => 'existing',
					'name' => $album->name,
					'id' => $album->_id
				);
			}
			catch ( \Exception $e ) {}
		}
		elseif ( isset( \IPS\Request::i()->_new ) )
		{
			unset( $_SESSION['gallery_album'] );
		}
				
		/* ADD STEPS */
		/* Step 1: Album (if permitted) */
		if( $category->allow_albums && \IPS\Request::i()->chooseAlbum )
		{
			$steps['album'] = array( &$this, '_stepAlbum' );
		}

		/* Step 2: Upload images */
		$steps['upload_images'] = array( &$this, '_stepUploadImages' );

		/* Step 3: Add image data */
		$steps['image_information'] = array( &$this, '_stepAddInfo' );

		/* Step 4: Process */
		$steps['process'] = array( &$this, '_stepProcess' );
		
		
		$wizard = new \IPS\Helpers\Wizard( $steps, $url );
		$wizard->template = array( \IPS\Theme::i()->getTemplate( 'submit' ), 'wizardForm' );
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ), array(), 'loc_gallery_adding_image' );
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('add_gallery_image');

		if ( !\IPS\Request::i()->isAjax() )
		{
			if ( \IPS\IN_DEV )
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/moxie.js', 'core', 'interface' ) );
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/plupload.dev.js', 'core', 'interface' ) );
			}
			else
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/plupload.full.min.js', 'core', 'interface' ) );
			}
		}

		if ( \IPS\gallery\Image::moderateNewItems( \IPS\Member::loggedIn() ) )
		{
			$wizard = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->modQueueMessage( \IPS\Member::loggedIn()->warnings( 5, NULL, 'mq' ), \IPS\Member::loggedIn()->mod_posts ) . $wizard;
		}

		\IPS\Output::i()->output = $wizard;
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( ( \IPS\Member::loggedIn()->group['g_movies'] ) ? 'add_gallery_image_movies' : 'add_gallery_image' ) );
	}

	/**
	 * Clears out saved album info from the session
	 *
	 * @return	void
	 */
	public function clearAlbum()
	{
		if( isset( $_SESSION['gallery_album'] ) )
		{
			unset( $_SESSION['gallery_album'] );
		}
	}

	/**
	 * Wizard step: processes the saved data to create an album and save images
	 *
	 * @param $data
	 * @return mixed
	 * @throws \ErrorException
	 */
	public function _stepProcess( $data )
	{
		/* Get category data */
		try
		{
			$category = \IPS\gallery\Category::loadAndCheckPerms( \IPS\Request::i()->category, 'add' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
		}
		
		/* Do we have an album to create? */
		$album = NULL;
		
		if( isset( $_SESSION['gallery_album'] ) )
		{	
			if( $_SESSION['gallery_album']['_type'] == 'new' )
			{
				$albumValues = $_SESSION['gallery_album'];
				$albumValues['album_category'] = $category;

				unset( $albumValues['_type'] );

				$album	= new \IPS\gallery\Album;
				$album->category_id	= $category->_id;
				$album->saveForm( $album->formatFormValues( $albumValues ) );

				unset( $_SESSION['gallery_album'] );
			}
			else
			{
				try
				{
					$album = \IPS\gallery\Album::loadAndCheckPerms( $_SESSION['gallery_album']['id'], 'add' );
				}
				catch ( \OutOfRangeException $e )
				{
					$album = NULL;
				}
			}
		}

		if( $album && !$album->can( 'add' ) )
		{
			$album = NULL;
		}
		
		$albumId = $album ? $album->id : 0;
				
		/* How many? */
		$count = count( $data['images'] ) + count( $data['movies'] );
		
		/* Trim results to fit */
		$limit = NULL;
		if( $album && \IPS\Member::loggedIn()->group['g_img_album_limit'] )
		{
			$limit = \IPS\Member::loggedIn()->group['g_img_album_limit'] - $album->count_imgs;
		}
				
		/* Process */
		$multiRedirect = (string) new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( "app=gallery&module=gallery&controller=submit&do=submit&category={$category->id}&_step=process&album={$albumId}", 'front', 'gallery_submit' ),
			function( $offset ) use ( $data, $category, $album, $count, $limit )
			{
				$existing = \IPS\Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_session=?', $data['postKey'] ), 'upload_location', array( 0, 1 ) )->setKeyField( 'upload_location' );
				foreach( $existing as $location => $file )
				{
					/* If we're over limit, delete it */
					if ( $limit === 0 )
					{
						\IPS\File::get( 'gallery_Images', $file['upload_location'] )->delete();
					}
					/* Otherwise create the image */
					else
					{
						/* Set up the basic values */
						$values = array( 'postKey' => $data['postKey'], 'category' => $category->_id, 'imageLocation' => $location );
						/* Get our form data from the upload_data column */
						$fileData = json_decode( $file['upload_data'], TRUE );
			
						if( count( $fileData ) )
						{
							foreach( $fileData as $k => $v )
							{
								$values[ preg_replace("/^filedata_[0-9]+_/i", '', $k ) ]	= $v;
							}	
						}

						if( isset( $values['image_tags'] ) AND $values['image_tags'] AND !is_array( $values['image_tags'] ) )
						{
							$values['image_tags']	= explode( ',', $values['image_tags'] );
						}
			
						if( isset( $album ) )
						{
							$values['album'] = $album->_id;
						}
			
						/* If no title was saved, use the original file name */
						if( !isset( $values['image_title'] ) )
						{
							$values['image_title'] = $file['upload_file_name'];
						}
			
						/* Create image */
						$image	= \IPS\gallery\Image::createFromForm( $values, ( isset( $album ) ) ? $album->category() : $category, FALSE );
						$image->markRead();
					}
					
					/* Delete that file */
					\IPS\Db::i()->delete( 'gallery_images_uploads', array( 'upload_session=?', $data['postKey'] ), 'upload_location', 1 );

					/* Go to next */
					return array( ++$offset, \IPS\Member::loggedIn()->language()->addToStack('processing'), 100 / $count * $offset );
				}
				
				return NULL;
			},
			function() use ( $category, $album, $data, $count )
			{
				if ( $count === 1 )
				{
					/* If we are only sending one image, send a normal notification */
					$image = \IPS\gallery\Image::constructFromData( \IPS\Db::i()->select( '*', 'gallery_images', NULL, 'image_id DESC', 1 )->first() );
					if ( !$image->hidden() )
					{
						$image->sendNotifications();
					}
					else if( $image->hidden() !== -1 )
					{
						$image->sendUnapprovedNotification();
					}
					
					\IPS\Output::i()->redirect( \IPS\gallery\Image::constructFromData( \IPS\Db::i()->select( '*', 'gallery_images', NULL, 'image_id DESC', 1 )->first() )->url() );
				}
				else
				{
					if ( \IPS\Member::loggedIn()->moderateNewContent() OR \IPS\gallery\Image::moderateNewItems( \IPS\Member::loggedIn(), $category ) )
					{
						\IPS\gallery\Image::_sendUnapprovedNotifications( $category, $album );
					}
					else
					{
						\IPS\gallery\Image::_sendNotifications( $category, $album );
					}
					
					\IPS\Output::i()->redirect( $album ? $album->url() : $category->url() );
				}
			}
		);

		return \IPS\Theme::i()->getTemplate( 'submit' )->processing( $multiRedirect );	
	}

	/**
	 * Wizard step: Choose an album
	 *
	 * @return	void
	 */
	public function _stepAlbum( $data )
	{
		/* Get category data */
		try
		{
			$category = \IPS\gallery\Category::loadAndCheckPerms( \IPS\Request::i()->category, 'add' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
		}

		$totalItems = 0;

		if( isset( $data['images'] ) && isset( $data['movies' ] ) )
		{
			$totalItems = count( $data['images'] ) + count( $data['movies'] );
		}

		$existing	= count( \IPS\gallery\Album::loadByOwner() );
		$canCreate = TRUE;

		/* Check we can create an album */
		if( \IPS\Member::loggedIn()->group['g_album_limit'] > 0 )
		{
			if( $existing >= \IPS\Member::loggedIn()->group['g_album_limit'] )
			{
				$canCreate	= FALSE;
			}
		}

		if( \IPS\Request::i()->albumLocation == 'existing' && $existing > 0  )
		{
			if( \IPS\Request::i()->album )
			{
				try
				{
					$album = \IPS\gallery\Album::loadAndCheckPerms( \IPS\Request::i()->album, 'add' );
				}
				catch ( \OutOfRangeException $e )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
				}

				$_SESSION['gallery_album'] = array(
					'_type' => 'existing',
					'name' => $album->name,
					'id' => $album->_id
				);

				/* Return info to the browser */
				if( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( array( 
						'status' => 'ok',
						'type' => 'existing',
						'name' => htmlentities( $album->name, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
						'maxImages' => \IPS\Member::loggedIn()->group['g_img_album_limit'] - $album->count_imgs
					)	);
				}
				else
				{
					$return = array(
						'postKey'		=> ( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : md5( uniqid() ),
						'category' 		=> $category->_id,
						'album'			=> $album->_id,
						'images' 		=> $data['images'],
						'movies'		=> $data['movies']
					);

					return $return;
				}
			}

			$albumsInCategory = \IPS\gallery\Album::loadByOwner( NULL, array( array( 'category_id=?', $category->id ) ) );

			return \IPS\Theme::i()->getTemplate( 'submit' )->existingAlbums( $category, $albumsInCategory, count( $data['images'] ) + count( $data['movies'] ) );
		}
		elseif( $canCreate )
		{
			$form = new \IPS\Helpers\Form( 'choose_album', 'continue' );

			$album	= new \IPS\gallery\Album;
			$album->form( $form );
			unset( $form->elements['']['album_category'] );
			
			/* Processing a new album? */
			if ( $values = $form->values() )
			{
				$values['_type'] = 'new';

				/* Need to create a new album if that's what was submitted */
				$return	= array(
					'postKey'	=> ( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : md5( uniqid() )
				);

				if( !isset( $values['album_name'] ) OR !$values['album_name'] )
				{
					$form->elements['']['album_name']->error	= \IPS\Member::loggedIn()->language()->addToStack('form_required');

					/* Show album create form */
					return \IPS\Theme::i()->getTemplate( 'submit' )->createAlbum( $form );
				}

				if( !\IPS\gallery\Image::modPermission( 'edit' ) )
				{
					$values['set_album_owner']	= 'me';
				}

				/* Add the album info to the session */
				$_SESSION['gallery_album'] = $values;

				/* Return info to the browser */
				if( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( array( 
						'status' => 'ok',
						'type' => 'new',
						'name' => htmlentities( $values['album_name'], \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
						'maxImages' => \IPS\Member::loggedIn()->group['g_img_album_limit']
					)	);
				}
				else
				{
					$return = array(
						'postKey'		=> ( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : md5( uniqid() ),
						'category' 		=> $category->_id,
						'images' 		=> $data['images'],
						'movies'		=> $data['movies']
					);

					return $return;
				}
			}

			/* Show album create form */
			return \IPS\Theme::i()->getTemplate( 'submit' )->createAlbum( $form, $totalItems );
		}
		else
		{
			return array(
				'postKey'		=> ( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : md5( uniqid() ),
				'category' 		=> $category->_id,
				'images' 		=> $data['images'],
				'movies'		=> $data['movies']
			);
		}
			
	}

	/**
	 * Wizard step: Add image information
	 *
	 * @return	void
	 */
	public function _stepAddInfo( $data )
	{				
		/* Get category data */
		try
		{
			$category = \IPS\gallery\Category::loadAndCheckPerms( \IPS\Request::i()->category, 'add' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
		}

		/* Are we finished on this step? */
		if( isset( \IPS\Request::i()->finish ) )
		{
			return $data;
		}

		/* Get any records we had before so we can mark them done */
		$existing = iterator_to_array( \IPS\Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_session=?', isset( $data['postKey'] ) ? $data['postKey'] : \IPS\Request::i()->postKey ) )->setKeyField( 'upload_location' ) );

		$images = array();
		$movies = array();

		if( isset( $data['images'] ) AND count( $data['images'] ) )
		{
			foreach ( $data['images'] as $key => $file )
			{
				/* Header */
				$file = \IPS\File::get( 'gallery_Images', $file );
				$images[ $key ] = $file;
				$images[ $key ]->done = FALSE;

				/* If data exists for this image, we can mark it as done */
				if( isset( $existing[ (string) $images[ $key ] ]['upload_data'] ) && $existing[ (string) $images[ $key ] ]['upload_data'] )
				{
					$images[ $key ]->done = TRUE;
				}
			}
		}

		if( isset( $data['movies'] ) AND count( $data['movies'] ) )
		{
			foreach ( $data['movies'] as $key => $file )
			{
				/* Header */
				$file = \IPS\File::get( 'gallery_Images', $file );
				$movies[ $key ] = $file;
				$movies[ $key ]->done = FALSE;

				/* If data exists for this movie, we can mark it as done */
				if( isset( $existing[ (string) $movies[ $key ] ]['upload_data'] ) && $existing[ (string) $movies[ $key ] ]['upload_data'] )
				{
					$movies[ $key ]->done = TRUE;
					
					if ( $movieData = json_decode( $existing[ (string) $movies[ $key ] ]['upload_data'], TRUE ) and $movieData["filedata_{$key}_image_thumbnail"] )
					{
						$movies[ $key ]->attachmentThumbnailUrl = $movieData["filedata_{$key}_image_thumbnail"];
					}
				}

			}
		}

		$totalItems = count( $images ) + count( $movies );
		$canCreateAlbum = $this->_canCreateAlbum( $category );
		$album = NULL;

		/* Do we have an album in session or coming from the URL? */
		if( isset( $_SESSION['gallery_album'] ) )
		{
			$imageCount = $totalItems;

			if( \IPS\Member::loggedIn()->group['g_img_album_limit'] && $totalItems > \IPS\Member::loggedIn()->group['g_img_album_limit'] )
			{
				$imageCount = \IPS\Member::loggedIn()->group['g_img_album_limit'];
			}

			if( isset( $_SESSION['gallery_album'] ) )
			{
				$album = array(
					'images' => $imageCount,
					'name' => ( $_SESSION['gallery_album']['_type'] == 'new' ) ? $_SESSION['gallery_album']['album_name'] : $_SESSION['gallery_album']['name']
				);
			}
		}

		/* Are we deleting? */
		if( isset( \IPS\Request::i()->delete ) )
		{
			$deleteType = ( \IPS\Request::i()->deleteType == 'movies' ) ? 'movies' : 'images';
			$deleteItem = ${ $deleteType }[ \IPS\Request::i()->delete ];
			
			$next = $this->_getNext( $images, $movies, $deleteType, \IPS\Request::i()->delete );

			\IPS\Db::i()->delete( 'gallery_images_uploads', array( 
				'`upload_location` = ? AND `upload_session` = ?',
				(string) $deleteItem->url,
				$data['postKey']
			) );

			try
			{
				\IPS\File::get( 'gallery_Images', $deleteItem->location )->delete();
			}
			catch ( \Exception $e ) { }

			unset( ${ $deleteType }[ \IPS\Request::i()->delete ] );

			/* Get the form for the next item */
			if( !$next['done'] )
			{
				$nextForm = \IPS\Theme::i()->getTemplate( 'submit' )->imageInformationForm( 
					$this->_getFormForImage( $category, $next['type'], $next['index'], $next['item'], $data['postKey'] ),
					$next['item'],
					$totalItems,
					$next['index'],
					$category->_id,
					$next['type']
				);
			}

			/* Send back the appropriate info - the next form, or some json data if via ajax */
			if( \IPS\Request::i()->isAjax() )
			{
				if( !$next['done'] )
				{
					$output = array( 'nextType' => $next['type'], 'nextID' => $next['index'], 'nextForm' => $nextForm );
				}
				else
				{
					$output = array( 'done' => TRUE );
				}

				\IPS\Output::i()->json( $output );
			}
			else
			{
				if( !$next['done'] )
				{
					return \IPS\Theme::i()->getTemplate( 'submit' )->imageInformation( $images, $movies, $nextForm, $category, $next['index'], $next['type'], $next['item'], $album, $canCreateAlbum );	
				}
				else
				{
					\IPS\Output::i()->redirect( $category->url() );
				}
			}
		}

		$editType = isset( \IPS\Request::i()->type ) && \IPS\Request::i()->type == 'movies' ? 'movies' : 'images';
		$editIndex = isset( \IPS\Request::i()->edit ) ? intval( \IPS\Request::i()->edit ) : 0;

		/* Try and locate an item to edit */
		if ( isset( ${$editType}[ $editIndex ] ) )
		{
			$editItem = ${$editType}[ $editIndex ];
		}
		else
		{
			if( $editType == 'movies' && isset( $images[ $editIndex ] ) )
			{
				$editType = 'images';
				$editItem = $images[ $editIndex ];
			}
			elseif( $editType == 'images' && isset( $movies[ $editIndex ] ) )
			{
				$editType = 'movies';
				$editItem = $movies[ $editIndex ];
			}
			else
			{
				$editType = count( $images ) ? 'images' : 'movies';
				$editIndex = 0;
				$editItem = count( $images ) ? $images[ 0 ] : $movies [ 0 ];
			}
		}

		// Get form
		$form = $this->_getFormForImage( $category, $editType, $editIndex, $editItem, $data['postKey'] );
		if( $values = $form->values( TRUE ) )
		{
			/* Store this data in the temporary upload table */
			\IPS\Db::i()->update( 'gallery_images_uploads', array( 
				'upload_data' 		=> json_encode( $values )
			), array( 
				'`upload_location` = ? AND `upload_session` = ?',
				(string) $editItem,
				\IPS\Request::i()->postKey
			) );

			/* Get next thing to edit */
			$next = $this->_getNext( $images, $movies, $editType, $editIndex );

			/* Get the form for the next item */
			if( !$next['done'] )
			{
				$nextForm = \IPS\Theme::i()->getTemplate( 'submit' )->imageInformationForm( 
					$this->_getFormForImage( $category, $next['type'], $next['index'], $next['item'], $data['postKey'] ),
					$next['item'],
					$totalItems,
					$next['index'],
					$category->_id,
					$next['type']
				);
			}
			else
			{
				$nextForm = \IPS\Theme::i()->getTemplate( 'submit' )->imageInformationDone( $images, $movies, $category, $album );
			}

			if( \IPS\Request::i()->isAjax() )
			{
				if( !$next['done'] )
				{
					$output = array( 'nextType' => $next['type'], 'nextID' => $next['index'], 'nextForm' => $nextForm );
				}
				else
				{
					$output = array( 'done' => TRUE, 'nextForm' => $nextForm, 'nextID' => -1 );
				}

				\IPS\Output::i()->json( $output );
			}
			else
			{
				if( !$next['done'] )
				{
					return \IPS\Theme::i()->getTemplate( 'submit' )->imageInformation( $images, $movies, $nextForm, $category, $next['index'], $next['type'], $next['item'], $album );	
				}
				else
				{
					return \IPS\Theme::i()->getTemplate( 'submit' )->imageInformation( $images, $movies, $nextForm, $category, -1, NULL, NULL, $album );	
				}
			}
		}

		if( \IPS\Request::i()->isAjax() )
		{
			return \IPS\Theme::i()->getTemplate( 'submit' )->imageInformationForm( $form, $editItem, $totalItems, $editIndex, $category->_id, $editType );
		}
		else
		{
			return \IPS\Theme::i()->getTemplate( 'submit' )->imageInformation( $images, $movies, \IPS\Theme::i()->getTemplate( 'submit' )->imageInformationForm( $form, $editItem, $totalItems, $editIndex, $category->_id, $editType ), $category, $editIndex, $editType, $editItem, $album, $canCreateAlbum );
		}	
	}

	/**
	 * Wizard step: upload images
	 *
	 * @return	void
	 */
	public function _stepUploadImages( $data )
	{
		/* Get category data */
		try
		{
			$category = \IPS\gallery\Category::loadAndCheckPerms( \IPS\Request::i()->category, 'add' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=gallery&module=gallery&controller=submit', 'front', 'gallery_submit' ) );
		}

		$form = new \IPS\Helpers\Form( 'upload_images', 'continue' );
		$form->class = 'ipsForm_vertical';
		$form->hiddenValues['postKey']	= ( !empty( $data['postKey'] ) ) ? $data['postKey'] : md5( uniqid() );

		/* Populate any existing records */
		$images	= array();
		$movies	= array();

		if ( isset( $data['images'] ) )
		{
			foreach ( $data['images'] as $url )
			{
				$file	= \IPS\File::get( 'gallery_Images', $url );

				if ( $file->isImage() )
				{
					$images[] = $file;
				}
				else
				{
					$movies[] = $file;
				}
			}
		}

		/* Show a multi-file uploader field, or multiple individual image upload fields */
		$imageLimit = NULL;

		if ( isset( $_SESSION['gallery_album'] ) AND isset( $_SESSION['gallery_album']['id'] ) )
		{
			try
			{
				$album = \IPS\gallery\Album::load( $_SESSION['gallery_album']['id'] );
				$imageLimit		= ( \IPS\Member::loggedIn()->group['g_img_album_limit'] ) ? ( \IPS\Member::loggedIn()->group['g_img_album_limit'] - ( $album->count_imgs + $album->count_imgs_hidden ) ) : NULL;
			}
			catch ( \Exception $e ) { }
		}
		
		$moviesAllowed	= ( \IPS\Member::loggedIn()->group['g_movies'] ) ? TRUE : FALSE;

		/* Add the upload field */
		$form->add( new \IPS\Helpers\Form\Upload( 'images', $images, FALSE , array(
			'storageExtension'	=> 'gallery_Images', 
			'image'				=> TRUE, 
			'multiple'			=> TRUE, 
			'minimize'			=> FALSE,
			/* 'template' => "...",		// This is the javascript template for the submission form */ 
			/* This has to be converted from kB to mB */
			'maxFileSize'		=> \IPS\Member::loggedIn()->group['g_max_upload'] ? ( \IPS\Member::loggedIn()->group['g_max_upload'] / 1024 ) : NULL,
		), function( $value ) use ( $imageLimit ) {
			if( $imageLimit === NULL )
			{
				return;
			}

			if( count( $value ) > $imageLimit )
			{
				throw new \InvalidArgumentException('gallery_images_too_many');
			}
		} ) );

		/* If movies are allowed, add the movie field... */
		if( $moviesAllowed )
		{
			$form->add( new \IPS\Helpers\Form\Upload( 'movies', $movies, FALSE, array( 
				'storageExtension'	=> 'gallery_Images', 
				'allowedFileTypes'	=> array( 'flv', 'f4v', 'wmv', 'mpg', 'mpeg', 'mp4', 'mkv', 'm4a', 'm4v', '3gp', 'mov', 'avi', 'webm', 'ogg', 'ogv' ), 
				'multiple'			=> TRUE, 
				'minimize'			=> FALSE,
				/* 'template' => "...",		// This is the javascript template for the submission form */ 
				/* This has to be converted from kB to mB */
				'maxFileSize'		=> \IPS\Member::loggedIn()->group['g_movie_size'] ? ( \IPS\Member::loggedIn()->group['g_movie_size'] / 1024 ) : NULL,
			), function( $value ) use ( $imageLimit ) {
				if( $imageLimit === NULL )
				{
					return;
				}

				if( count( $value ) > $imageLimit )
				{
					throw new \InvalidArgumentException('gallery_movies_too_many');
				}
			} ) );
		}

		$canCreateAlbum = $this->_canCreateAlbum( $category );

		/* Process a submission */
		if ( $values = $form->values() )
		{
			/* Make sure *something* was submitted */
			if( ( empty( $values['images'] ) ) AND ( empty( $values['movies'] ) ) )
			{
				$form->error	= \IPS\Member::loggedIn()->language()->addToStack('gallery_image_or_movie');

				return \IPS\Theme::i()->getTemplate( 'submit' )->uploadImages( $form, $category );
			}

			/* Get any records we had before in case we need to delete them */
			$existing = iterator_to_array( \IPS\Db::i()->select( '*', 'gallery_images_uploads', array( 'upload_session=?', \IPS\Request::i()->postKey ) )->setKeyField( 'upload_location' ) );
			
			/* Loop through the values we have */
			$k		= 0;
			$images	= array();
			$movies	= array();

			$inserts = array();
			if( isset( $values['images'] ) AND count( $values['images'] ) )
			{
				foreach ( $values['images'] as $image )
				{
					$images[ $k ] = (string) $image;

					if ( !isset( $existing[ (string) $image ] ) )
					{
						$inserts[] = array(
							'upload_session'	=> \IPS\Request::i()->postKey,
							'upload_member_id'	=> (int) \IPS\Member::loggedIn()->member_id,
							'upload_location'	=> (string) $image,
							'upload_file_name'	=> $image->originalFilename,
							'upload_date'		=> time(),
						);
					}

					$k++;
					unset( $existing[ (string) $image ] );
				}
			}

			if( isset( $values['movies'] ) AND count( $values['movies'] ) )
			{
				foreach ( $values['movies'] as $movie )
				{
					$movies[ $k ] = (string) $movie;

					if ( !isset( $existing[ (string) $movie ] ) )
					{
						$inserts[] = array(
							'upload_session'	=> \IPS\Request::i()->postKey,
							'upload_member_id'	=> (int) \IPS\Member::loggedIn()->member_id,
							'upload_location'	=> (string) $movie,
							'upload_file_name'	=> $movie->originalFilename,
							'upload_date'		=> time(),
						);
					}

					$k++;
					unset( $existing[ (string) $movie ] );
				}
			}
			
			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'gallery_images_uploads', $inserts );
			}

			/* Delete any that we don't have any more */
			foreach ( $existing as $location => $file )
			{
				try
				{
					\IPS\File::get( 'gallery_Images', $location )->delete();
				}
				catch ( \Exception $e ) { }
				
				\IPS\Db::i()->delete( 'gallery_images_uploads', array( 'upload_session=? and upload_location=?', $file['upload_session'], $file['upload_location'] ) );
			}

			$return	= array(
				'postKey'	=> ( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : md5( uniqid() ),
				'images'	=> $images,
				'movies'	=> $movies,
				'category'	=> $category->_id
			);

			return $return;
		}

		return \IPS\Theme::i()->getTemplate( 'submit' )->uploadImages( $form, $category, $canCreateAlbum, $imageLimit );
	}

	/**
	 * Returns whether the user can create an album
	 *
	 * @return	boolean
	 */
	protected function _canCreateAlbum( $category )
	{
		$canCreateAlbum = FALSE;

		/* Can this user add albums (permissions, but also have they used their allowance?) */
		if( $category->allow_albums && \IPS\Member::loggedIn()->group['g_create_albums'] )
		{
			if( \IPS\Member::loggedIn()->group['g_album_limit'] > 0 )
			{
				$existing	= count( \IPS\gallery\Album::loadByOwner() );

				if( $existing < \IPS\Member::loggedIn()->group['g_album_limit'] )
				{
					$canCreateAlbum	= TRUE;
				}
			}
			else
			{
				$canCreateAlbum = TRUE;
			}
		} 

		return $canCreateAlbum;
	}

	/**
	 * Returns data about the next editable item
	 *
	 * @return	array
	 */
	protected function _getNext( $images, $movies, $currentType='images', $currentIndex=0 )
	{
		$next = array(
			'type' 	=> 'images',
			'index' => 0,
			'item' 	=> NULL,
			'done'	=> FALSE
		);

		if( $currentType == 'movies' )
		{
			if( isset( $movies[ $currentIndex + 1 ] ) )
			{
				$next['type'] = 'movies';
				$next['index'] = $currentIndex + 1;
				$next['item'] = $movies[ $next['index'] ];
			}
			else
			{
				$next['done'] = TRUE;
			}
		}
		else
		{
			if( isset( $images[ $currentIndex + 1 ] ) )
			{
				$next['index'] = $currentIndex + 1;
				$next['item'] = $images[ $next['index'] ];
			}
			elseif( isset( $movies[0] ) )
			{
				$next['type'] = 'movies';
				$next['item'] = $movies[ $nextIndex ];
			}
			else
			{
				$next['done'] = TRUE;
			}
		}

		return $next;
	}

	/**
	 * Returns the \IPS\Helpers\Form object for the provided image
	 *
	 * @return	\IPS\Helpers\Form
	 */
	protected function _getFormForImage( $category, $type, $index, $item, $postKey )
	{
		/* Get existing values */
		try
		{
			$data = json_decode( \IPS\Db::i()->select( 'upload_data', 'gallery_images_uploads', array( 'upload_session=? AND upload_file_name=?', $postKey, $item->originalFilename ) )->first(), TRUE );
		}
		catch ( \UnderflowException $e )
		{
			$data = array();
		}
				
		/* Build the form for the image we're editing */
		$form	= new \IPS\Helpers\Form( 'image_information', 'continue_next_image' );
		$form->hiddenValues['postKey']	= $postKey;
		$form->class = 'ipsForm_vertical';

		if ( $type == 'images' )
		{
			/* Form Elements for images */
			foreach ( \IPS\gallery\Image::formElements( NULL, $category ) as $input )
			{
				\IPS\Member::loggedIn()->language()->words[ "filedata_{$index}_{$input->name}" ] = \IPS\Member::loggedIn()->language()->addToStack( $input->name, FALSE );
								
				if ( !$input->value )
				{
					if ( isset( $data["filedata_{$index}_{$input->name}"] ) )
					{
						$input->value = $data["filedata_{$index}_{$input->name}"];
					} 
					elseif ( $input->name === 'image_title' )
					{
						$input->value = str_replace( "_", "-", $item->originalFilename );
					}
				}
										
				$input->name = "filedata_{$index}_{$input->name}";
				if ( $input instanceof \IPS\Helpers\Form\Editor )
				{
					$input->options['autoSaveKey'] .= $index;
				}
								
				$form->add( $input );
			}

			/* Is there location data stored with this image? If so let the user show the map or not */
			if( \IPS\Image::exifSupported() )
			{
				$exif	= \IPS\Image::create( $item->contents() )->parseExif();

				if( count( $exif ) )
				{
					if( isset( $exif['GPS.GPSLatitudeRef'] ) && isset( $exif['GPS.GPSLatitude'] ) && isset( $exif['GPS.GPSLongitudeRef'] ) && isset( $exif['GPS.GPSLongitude'] ) )
					{
						\IPS\Member::loggedIn()->language()->words[ "filedata_{$index}_image_gps_show" ]		= \IPS\Member::loggedIn()->language()->addToStack( 'image_gps_show', FALSE );
						\IPS\Member::loggedIn()->language()->words[ "filedata_{$index}_image_gps_show_desc" ]	= \IPS\Member::loggedIn()->language()->addToStack( 'image_gps_show_desc', FALSE );
						$form->add( new \IPS\Helpers\Form\YesNo( "filedata_{$index}_image_gps_show", TRUE, FALSE ) );
					}
				}
			}

			$form->hiddenValues['type']		= 'images';
			$form->hiddenValues['edit']		= $index;
		}
		else
		{
			/* Form Elements for movies */
			foreach ( \IPS\gallery\Image::formElements( NULL, $category ) as $input )
			{
				\IPS\Member::loggedIn()->language()->words[ "filedata_{$index}_{$input->name}" ] = \IPS\Member::loggedIn()->language()->addToStack( $input->name, FALSE );

				if ( !$input->value and in_array( $input->name, array( 'image_title' ) ) )
				{
					$input->value = $item->originalFilename;
				}

				$input->name = "filedata_{$index}_{$input->name}";
				if ( $input instanceof \IPS\Helpers\Form\Editor )
				{
					$input->options['autoSaveKey'] .= $index;
				}
								
				$form->add( $input );
			}

			/* Add the field to upload a thumbnail */
			\IPS\Member::loggedIn()->language()->words[ "filedata_{$index}_image_thumbnail" ]		= \IPS\Member::loggedIn()->language()->addToStack( 'image_thumbnail', FALSE );
			$form->add( new \IPS\Helpers\Form\Upload( "filedata_{$index}_image_thumbnail", isset( $data["filedata_{$index}_image_thumbnail"] ) ? \IPS\File::get( 'gallery_Images', $data["filedata_{$index}_image_thumbnail"] ) : NULL, FALSE, array( 
				'storageExtension'	=> 'gallery_Images', 
				'image'				=> TRUE,
				/* 'template' => "...",		// This is the javascript template for the submission form */ 
				/* This has to be converted from kB to mB */
				'maxFileSize'		=> \IPS\Member::loggedIn()->group['g_max_upload'] ? ( \IPS\Member::loggedIn()->group['g_max_upload'] / 1024 ) : NULL,
			) ) );
			
			$form->action = $form->action->setQueryString( array( 'type' => 'movies', 'edit' => $index ) );
			$form->hiddenValues['type']		= 'movies';
			$form->hiddenValues['edit']		= $index;
		}

		return $form;
	}
}
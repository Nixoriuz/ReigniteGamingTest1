<?php
/**
 * @brief		Search settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		14 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search settings
 */
class _search extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'search_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 * @note	There is a hardcoded check for Sphinx here
	 * @todo	The lang string replacements need to be checked <- checked for what?
	 */
	protected function manage()
	{		
		/* Rebuild button */
		\IPS\Output::i()->sidebar['actions'] = array(
			'rebuildIndex'	=> array(
				'title'		=> 'search_rebuild_index',
				'icon'		=> 'undo',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=search&do=queueIndexRebuild' ),
				'data'		=> array( 'confirm' => '', 'confirmSubMessage' => \IPS\Member::loggedIn()->language()->get('search_rebuild_index_confirm') ),
			),
		);
		
		\IPS\Member::loggedIn()->language()->words['search_engine__sphinx_desc']	= sprintf( \IPS\Member::loggedIn()->language()->get('search_engine__sphinx_desc'), \IPS\Settings::i()->sphinx_base_path . '/sphinx.conf' );

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'search_engine', isset( \IPS\Request::i()->search_sphinx_server ) ? 'sphinx' : \IPS\Settings::i()->search_engine, TRUE, array( 'options' => $this->_getEngines(), 'toggles' => array( 'mysql' => array( 'search_index_timeframe' ), 'sphinx' => array( 'form_header_sphinx_header', 'form_header_sphinx_instructions', 'search_sphinx_server', 'search_sphinx_port', 'sphinx_base_path', 'sphinx_prefix' ) ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'search_index_timeframe', \IPS\Settings::i()->search_index_timeframe, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'all' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('search_index_timeframe_prefix'), \IPS\Member::loggedIn()->language()->addToStack('search_index_timeframe_suffix'), 'search_index_timeframe' ) );

		$form->addHeader( 'sphinx_header' );
		if ( \IPS\Settings::i()->search_engine == 'sphinx' )
		{
			$form->addMessage( 'sphinx_instructions', '', TRUE, 'form_header_sphinx_instructions' );
		}
		$form->add( new \IPS\Helpers\Form\Text( 'search_sphinx_server', \IPS\Settings::i()->search_sphinx_server, FALSE, array(), function( $val )
		{
			if ( $val === 'localhost' )
			{
				throw new \DomainException('search_sphinx_server_err');
			}
		}, NULL, NULL, 'search_sphinx_server' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'search_sphinx_port', \IPS\Settings::i()->search_sphinx_port, FALSE, array( 'placeholder' => "3312" ), NULL, NULL, NULL, 'search_sphinx_port' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'sphinx_base_path', \IPS\Settings::i()->sphinx_base_path, FALSE, array( 'placeholder' => "/var/sphinx" ), NULL, NULL, NULL, 'sphinx_base_path' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'sphinx_prefix', \IPS\Settings::i()->sphinx_prefix, FALSE, array(), NULL, NULL, NULL, 'sphinx_prefix' ) );
		
		if ( $values = $form->values() )
		{
			$existing	= \IPS\Settings::i()->search_engine;
			$indexPrune = \IPS\Settings::i()->search_index_timeframe;
			
			/* Did we just enable Sphinx? If so, we need some more instructions... */
			if( $existing != $values['search_engine'] and $values['search_engine'] === 'sphinx' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=search&do=sphinxInstructions' )->setQueryString( $values ) );
			}
			
			/* Otherwise go ahead and save... */
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplogs__search_settings' );
			
			/* And re-index if setting updated */
			if( $existing != $values['search_engine'] or $indexPrune != $values['search_index_timeframe'] )
			{				
				\IPS\Content\Search\Index::i()->rebuild();
			}
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_search');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_search', $form );
	}
	
	/**
	 * Show Sphinx instructions
	 *
	 * @return	void
	 */
	public function sphinxInstructions()
	{
		$configuration = array();
		foreach ( array( 'search_sphinx_server', 'search_sphinx_port', 'sphinx_prefix', 'sphinx_base_path' ) as $k )
		{
			$configuration[ $k ] = isset( \IPS\Request::i()->$k ) ? \IPS\Request::i()->$k : '';
		}
		
		$error = NULL;
		if ( isset( \IPS\Request::i()->check ) )
		{
			try
			{
				$database = \IPS\Db::i( 'SPHINXQL', array(
					'sql_host'		=> \IPS\Request::i()->search_sphinx_server,
					'sql_user'		=> NULL,
					'sql_pass'		=> NULL,
					'sql_database'	=> NULL,
					'sql_port'		=> (int) \IPS\Request::i()->search_sphinx_port,
					'sql_socket'	=> NULL,
					'sql_utf8mb4'	=> NULL,
				)	);

				$result = $database->query("SHOW STATUS");
				
				\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 'sphinx' ), array( 'conf_key=?', 'search_engine' ) );
				foreach ( $configuration as $k => $v )
				{
					\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $v ), array( 'conf_key=?', $k ) );
					\IPS\Settings::i()->$k	= $v;
				}
				unset( \IPS\Data\Store::i()->settings );
				\IPS\Session::i()->log( 'acplogs__search_settings' );
				
				\IPS\Content\Search\Index::i()->rebuild();
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=search' ), 'sphinx_setup_successful' );
			}
			catch( \Exception $exception )
			{
				\IPS\Log::i( \LOG_INFO )->write( get_class( $exception ) . "\n" . $exception->getCode() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString() );
				$error = 'sphinx_appear_invalid';
			}
		}
		
		$configurationUrl = \IPS\Http\Url::internal( 'app=core&module=settings&controller=search&do=sphinxConfiguration' )->setQueryString( $configuration );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('sphinx_header');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'settings' )->sphinxInstructions( $configurationUrl, $error );
	}
	
	/**
	 * Get Sphinx conifguration file
	 *
	 * @return	void
	 */
	public function sphinxConfiguration()
	{
		$server = isset( \IPS\Request::i()->search_sphinx_server ) ? \IPS\Request::i()->search_sphinx_server : \IPS\Settings::i()->search_sphinx_server;
		$port = isset( \IPS\Request::i()->search_sphinx_port ) ? \IPS\Request::i()->search_sphinx_port : \IPS\Settings::i()->search_sphinx_port;
		$prefix = isset( \IPS\Request::i()->sphinx_prefix ) ? \IPS\Request::i()->sphinx_prefix : \IPS\Settings::i()->sphinx_prefix;
		$path = isset( \IPS\Request::i()->sphinx_base_path ) ? \IPS\Request::i()->sphinx_base_path : \IPS\Settings::i()->sphinx_base_path;
		$collation = 'utf8_general_ci';
		
		$sphinxConfiguration = <<<SPHINX
searchd
{
	listen                  = {$server}:{$port}:mysql41
	max_children            = 30
	pid_file                = {$path}/buildsearchd.pid
	preopen_indexes         = 0
	collation_server        = {$collation}
	workers                 = threads
}

index {$prefix}ips
{
	type                    = rt
	path                    = {$path}/index
	
	rt_field                = index_title
	rt_field                = index_content
	rt_field                = index_tags
	rt_attr_bigint          = index_object_id
	rt_attr_bigint          = index_item_id
	rt_attr_bigint          = index_container_id
	rt_attr_bigint          = index_author
	rt_attr_bigint          = index_views
	rt_attr_bigint          = index_num_comments
	rt_attr_bigint          = index_num_reviews
	rt_attr_json            = index_permissions
	rt_attr_timestamp       = index_date_created
	rt_attr_timestamp       = index_date_updated
	rt_attr_string          = index_class
	rt_attr_uint            = index_class_id
	rt_attr_uint            = index_hidden
}
SPHINX;
	
		\IPS\Output::i()->sendOutput( $sphinxConfiguration, 200, 'application/octet-stream', array( 'Content-Disposition' => 'attachment; filename=sphinx.conf' ) );
	}

	/**
	 * Get the array of engine options
	 *
	 * @return	array
	 */
	protected function _getEngines()
	{
		$engines = array();
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . "/system/Content/Search/" ) as $file )
		{
			if ( $file->isDir() and !$file->isDot() and $file != 'index.html' )
			{				
				$name = mb_strtolower( $file );
				$engines[ $name ]	= 'search_engine__' . $name;
			}
		}
		return $engines;
	}
	
	/**
	 * Queue an index rebuild
	 *
	 * @return	void
	 */
	protected function queueIndexRebuild()
	{
		\IPS\Content\Search\Index::i()->rebuild();
	
		\IPS\Session::i()->log( 'acplogs__queued_search_index' );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=search' ), 'search_index_rebuilding' );
	}
}
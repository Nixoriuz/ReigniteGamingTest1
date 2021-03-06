<?php
/**
 * @brief		Top Downloaders
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Downloads
 * @since		17 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\downloads\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Top Downloaders
 */
class _downloaders extends \IPS\Dispatcher\Controller
{
	const PER_PAGE = 25;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'downloaders_manage' );
		parent::execute();
	}

	/**
	 * Top Downloaders
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$values = NULL;
		$where = array( array( 'dmid>0' ) );
		
		if ( isset( \IPS\Request::i()->form ) )
		{
			$form = new \IPS\Helpers\Form( 'form', 'go' );
			$form->add( new \IPS\Helpers\Form\DateRange( 'stats_date_range' ) );
			
			if ( $values = $form->values() )
			{
				if ( $values['stats_date_range']['start'] )
				{
					$where[] = array( 'dtime>?', $values['stats_date_range']['start']->getTimestamp() );
				}
				if ( $values['stats_date_range']['end'] )
				{
					$where[] = array( 'dtime<?', $values['stats_date_range']['end']->getTimestamp() );
				}
			}
			else
			{
				\IPS\Output::i()->output = $form;
				return;
			}
		}
		
		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$select = \IPS\Db::i()->select( 'dmid, COUNT(*) as downloads, core_members.*', 'downloads_downloads', $where, 'downloads DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), 'dmid', NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )->join( 'core_members', 'core_members.member_id=downloads_downloads.dmid' );
		
		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=downloads&module=stats&controller=downloaders' )->setQueryString( $values ),
			ceil( $select->count( TRUE ) / static::PER_PAGE ),
			$page,
			static::PER_PAGE,
			FALSE
		);
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'stats_date_range',
				'icon'		=> 'calendar',
				'link'		=> \IPS\Http\Url::internal( 'app=downloads&module=stats&controller=downloaders&form=1' )->setQueryString( $values ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('stats_date_range') )
			)
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('stats')->downloadsTable( $select, $pagination );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__downloads_stats_downloaders');
	}
}
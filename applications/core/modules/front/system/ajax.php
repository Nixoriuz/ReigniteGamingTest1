<?php
/**
 * @brief		AJAX actions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		04 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AJAX actions
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Find Member
	 *
	 * @retun	void
	 */
	public function findMember()
	{
		$results = array();
		
		$input = mb_strtolower( \IPS\Request::i()->input );
		
		$where = array( "name LIKE CONCAT('%', ?, '%')" );
		$binds = array( $input );
		if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' )
		{
			$where[] = "email LIKE CONCAT('%', ?, '%')";
			$binds[] = $input;
			
			if ( is_numeric( \IPS\Request::i()->input ) )
			{
				$where[] = "member_id=?";
				$binds[] = intval( \IPS\Request::i()->input );
			}
		}
				
		/* Build the array item for this member after constructing a record */
		/* The value should be just the name so that it's inserted into the input properly, but for display, we wrap it in the group *fix */
		foreach ( \IPS\Db::i()->select( '*', 'core_members', array_merge( array( implode( ' OR ', $where ) ), $binds ), 'LENGTH(name) ASC', array( 0, 20 ) ) as $row )
		{
			$member = \IPS\Member::constructFromData( $row );
			
			$results[] = array(
				'id'	=> 	$member->member_id,
				'value' => 	$member->name,
				'name'	=> 	\IPS\Dispatcher::i()->controllerLocation == 'admin' ? $member->group['prefix'] . htmlentities( $member->name, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . $member->group['suffix'] : htmlentities( $member->name, \IPS\HTMLENTITIES, 'UTF-8', FALSE ),
				'extra'	=> 	\IPS\Dispatcher::i()->controllerLocation == 'admin' ? $member->email : $member->groupName,
				'photo'	=> 	(string) $member->photo,
			);
		}
				
		\IPS\Output::i()->json( $results );
	}
	
	/**
	 * Returns boolean in json indicating whether the supplied username already exists
	 *
	 * @return	void
	 */
	public function usernameExists()
	{
		$result = array( 'result' => 'ok' );
		
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$name = urldecode( \IPS\Request::i()->input );
		
		/* Check is valid */
		if ( !$name )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_required') );
		}
		elseif ( mb_strlen( $name ) > \IPS\Settings::i()->max_user_name_length )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->max_user_name_length ) ) ) );
		}
		elseif ( \IPS\Settings::i()->username_characters and !preg_match( '/^[' . str_replace( '\-', '-', preg_quote( \IPS\Settings::i()->username_characters, '/' ) ) . ']*$/i', $name ) )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_bad_value') );
		}

		/* Check if it exists */
		else
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->usernameIsInUse( $name ) === TRUE )
				{
					if ( \IPS\Member::loggedIn()->isAdmin() )
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_name_exists_admin', FALSE, array( 'sprintf' => array( $k ) ) ) );
					}
					else
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_name_exists') );
					}
				}
			}
		}
		
		/* Check it's not banned */
		if ( $result == array( 'result' => 'ok' ) )
		{
			foreach( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
			{
				if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', $name ) )
				{
					$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_name_banned') );
				}
			}
		}

		\IPS\Output::i()->json( $result );	
	}

	/**
	 * Returns boolean in json indicating whether the supplied email already exists
	 *
	 * @return	void
	 */
	public function emailExists()
	{
		$result = array( 'result' => 'ok' );
		
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$email = urldecode( \IPS\Request::i()->input );
		
		/* Check is valid */
		if ( !$email )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_required') );
		}
		elseif ( filter_var( $email, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_bad_value') );
		}

		/* Check if it exists */
		else
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->emailIsInUse( $email ) === TRUE )
				{
					if ( \IPS\Member::loggedIn()->isAdmin() )
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_email_exists_admin', FALSE, array( 'sprintf' => array( $k ) ) ) );
					}
					else
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_email_exists') );
					}
				}
			}
		}

		\IPS\Output::i()->json( $result );	
	}

	/**
	 * Get state/region list for country
	 *
	 * @return	void
	 */
	public function states()
	{
		$states = array();
		if ( array_key_exists( \IPS\Request::i()->country, \IPS\GeoLocation::$states ) )
		{
			$states = \IPS\GeoLocation::$states[ \IPS\Request::i()->country ];
		}
		
		\IPS\Output::i()->json( $states );
	}
	
	/**
	 * Top Contributors
	 *
	 * @retun	void
	 */
	public function topContributors()
	{
		/* How many? */
		$limit = intval( ( isset( \IPS\Request::i()->limit ) and \IPS\Request::i()->limit < 20 ) ? \IPS\Request::i()->limit : 5 );
		
		/* What timeframe? */
		$where = array( array( 'member_received > 0' ) );
		$timeframe = 'all';
		if ( isset( \IPS\Request::i()->time ) and \IPS\Request::i()->time != 'all' )
		{
			switch ( \IPS\Request::i()->time )
			{
				case 'week':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1W' ) )->getTimestamp() );
					$timeframe = 'week';
					break;
				case 'month':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1M' ) )->getTimestamp() );
					$timeframe = 'month';
					break;
				case 'year':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1Y' ) )->getTimestamp() );
					$timeframe = 'year';
					break;
			}
            $topContributors = iterator_to_array( \IPS\Db::i()->select( 'core_reputation_index.member_received as member, SUM(rep_rating) as rep', 'core_reputation_index', $where, 'rep DESC', $limit, 'member' )->setKeyField('member')->setValueField('rep') );
        }
        else
        {
            $topContributors = iterator_to_array( \IPS\Db::i()->select( 'member_id as member, pp_reputation_points as rep', 'core_members', array( 'pp_reputation_points > 0' ), 'rep DESC', $limit )->setKeyField('member')->setValueField('rep') );
        }

		/* Load their data */	
		foreach ( \IPS\Db::i()->select( '*', 'core_members', \IPS\Db::i()->in( 'member_id', array_keys( $topContributors ) ) ) as $member )
		{
			\IPS\Member::constructFromData( $member );
		}
		
		/* Render */
		$output = \IPS\Theme::i()->getTemplate( 'widgets' )->topContributorRows( $topContributors, $timeframe, \IPS\Request::i()->orientation );
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output );
		}
		else
		{
			\IPS\Output::i()->output = $output;
		}
	}
}
<?php
/**
 * @brief		Nexus Incoming Email Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @subpackage	Nexus
 * @since		07 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\IncomingEmail;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class _Support
{
	/**
	 * Handle email
	 *
	 * @param	\IPS\Email\Incoming	$email	The email
	 * @return	bool
	 */
	public function process( \IPS\Email\Incoming $email )
	{
		/* Is it from an actual member? */
		$member = \IPS\Member::load( $email->data['from'], 'email' );
		
		/* Replying? */
		if ( preg_match( '/\[SR(\d+?)\.(.+?)(\.(\d+))?\]/', $email->data['raw'], $matches ) or preg_match( '/\[SR(\d+?)\.(.+?)(\.(\d+))?\]/', $email->data['message'], $matches ) )
		{
			try
			{
				$request = \IPS\nexus\Support\Request::load( $matches[1] );
				if ( $request->email_key === $matches[2] )
				{
					$staff = array_key_exists( $member->member_id, \IPS\nexus\Support\Request::staff() );
					
					$pending = FALSE;
					$newMessage = NULL;
					if ( $staff and isset( $matches[4] ) )
					{
						$newMessage = $request->comments( 1, 0, 'date', 'desc' );
						if ( $matches[4] != $newMessage->id )
						{
							$pending = TRUE;
						}
					}
					
					$request->status = \IPS\nexus\Support\Status::load( TRUE, $staff ? 'status_default_staff' : 'status_default_member' );
					$request->last_reply = time();
					$request->last_reply_by = (int) $member->member_id;
					$request->replies++;
					$request->save();
					
					$reply = new \IPS\nexus\Support\Reply;
					$reply->request = $request->id;
					$reply->member = (int) $member->member_id;
					$reply->type = $staff ? ( $pending ? $reply::REPLY_PENDING : $reply::REPLY_STAFF ) : $reply::REPLY_EMAIL;
					$reply->post = $email->data['message'];
					$reply->date = time();
					$reply->email = $email->data['from'];
					$reply->cc = $email->data['cc'];
					$reply->raw = $email->data['raw'];
					$reply->textformat = $email->data['alternative'];
					$reply->save();
					static::makeAndClaimAttachments( $email->data['attachments'], $reply );
					
					if ( $pending )
					{
						$notifyEmail = \IPS\Email::buildFromTemplate( 'nexus', 'staffReplyPending', array( $reply, $newMessage ) );
						$notifyEmail->send( $member );
					}
					else
					{
						if ( $staff )
						{
							$defaultRecipients = $request->getDefaultRecipients();
							$reply->sendCustomerNotifications( $defaultRecipients['to'], $defaultRecipients['cc'], $defaultRecipients['bcc'] );
						}
						$reply->sendNotifications();
					}
					
					return TRUE;
				}
			}
			catch ( \OutOfRangeException $e ) {}
		}
								
		/* Nope, creating a new one */
		try
		{	
			/* Load department */
			$department = \IPS\nexus\Support\Department::load( $email->data['to'], 'dpt_email' );
			
			/* Has it been forwarded? */
			if ( preg_match( '/^FWD?: (.*)$/i', $email->data['subject'], $matches ) )
			{
				$originallyFromEmail = NULL;
				if ( isset( $email->mail->headers['original-recipient'] ) )
				{
					$originallyFromEmail = preg_replace( '/^.*;(.*)$/', '$1', $email->mail->headers['original-recipient'] );
				}
				else
				{
					if ( preg_match( '/From:.+?(\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b)/i', $email->data['quoted'], $_matches ) )
					{
						$originallyFromEmail = $_matches[1];
					}
				}
				if ( $originallyFromEmail )
				{
					$originallyFrom = \IPS\Member::load( $originallyFromEmail, 'email' );
					if ( $member->member_id and array_key_exists( $member->member_id, \IPS\nexus\Support\Request::staff() ) )
					{						
						/* Strip any headers from the forwarded message */
						$quoted = $email->data['quoted'];
						$haveInitialHeader = FALSE;
						$exploded = explode( '<br>', $quoted );
						foreach ( $exploded as $k => $line )
						{
							if ( $line )
							{
								if ( !$haveInitialHeader and preg_match( '/^.*:/', $line ) )
								{
									$haveInitialHeader = TRUE;
								}
								elseif ( !preg_match( '/^.*:.*$/', $line ) )
								{
									$quoted = implode( '<br>', array_splice( $exploded, $k ) );
									break;
								}
							}
						}
						$quoted = \IPS\Text\Parser::parseStatic( '<div>' . $quoted . '</div>' );
						
						/* Create request */
						$request = new \IPS\nexus\Support\Request;
						$request->title = $matches[1];
						$request->department = $department;
						$request->member = (int) $originallyFrom->member_id;
						$request->status = \IPS\nexus\Support\Status::load( TRUE, 'status_default_member' );
						$request->severity = \IPS\nexus\Support\Severity::load( TRUE, 'sev_default' );
						$request->started = time();
						$request->last_reply = time();
						$request->last_reply_by = (int) $member->member_id;
						$request->last_new_reply = time();
						$request->replies = 1;
						$request->email = $originallyFromEmail;
						$request->save();
						
						/* Create the reply */
						$reply = new \IPS\nexus\Support\Reply;
						$reply->request = $request->id;
						$reply->member = (int) $member->member_id;
						$reply->type = $reply::REPLY_EMAIL;
						$reply->post = $quoted;
						$reply->date = time();
						$reply->email = $originallyFromEmail;
						$reply->cc = $email->data['cc'];
						$reply->raw = $email->data['raw'];
						$reply->textformat = $email->data['alternative'];
						$reply->save();
						
						/* Create a hidden note */
						if ( trim( $email->data['message'] ) or count( $email->data['attachments'] ) )
						{
							$note = new \IPS\nexus\Support\Reply;
							$note->request = $request->id;
							$note->member = (int) $member->member_id;
							$note->type = $reply::REPLY_HIDDEN;
							$note->post = $email->data['message'];
							$note->date = time();
							$note->email = $email->data['from'];
							$note->save();
							
							static::makeAndClaimAttachments( $email->data['attachments'], $note );
						}

						$request->sendNotifications();
						
						/* Return */
						return TRUE;
					}
				}
			}
			
			/* Nope - normal email */
			else
			{
				/* Create request */
				$request = new \IPS\nexus\Support\Request;
				$request->title = $email->data['subject'];
				$request->department = $department;
				$request->member = (int) $member->member_id;
				$request->status = \IPS\nexus\Support\Status::load( TRUE, 'status_default_member' );
				$request->severity = \IPS\nexus\Support\Severity::load( TRUE, 'sev_default' );
				$request->started = time();
				$request->last_reply = time();
				$request->last_reply_by = (int) $member->member_id;
				$request->last_new_reply = time();
				$request->replies = 1;
				$request->email = $email->data['from'];
				$request->save();
				
				/* Create the reply */
				$reply = new \IPS\nexus\Support\Reply;
				$reply->request = $request->id;
				$reply->member = (int) $member->member_id;
				$reply->type = $reply::REPLY_EMAIL;
				$reply->post = $email->data['message'];
				$reply->date = time();
				$reply->email = $email->data['from'];
				$reply->cc = $email->data['cc'];
				$reply->raw = $email->data['raw'];
				$reply->textformat = $email->data['alternative'];
				$reply->save();
				static::makeAndClaimAttachments( $email->data['attachments'], $reply );
				
				/* Send confirmation email */
				if ( \IPS\Settings::i()->nexus_sout_autoreply )
				{
					$confirmationEmail = \IPS\Email::buildFromTemplate( 'nexus', 'emailConfirmation', array( $request ) );
					$confirmationEmail->from = $request->department->email;
					switch ( \IPS\Settings::i()->nexus_sout_from )
					{
						case 'staff':
						case 'dpt':
							$confirmationEmail->fromName = $confirmationEmail->language->get( 'nexus_department_' . $request->department->_id );
							break;
						default:
							$confirmationEmail->fromName = \IPS\Settings::i()->nexus_sout_from;
							break;
					}
					$confirmationEmail->send( $member->member_id ? $member : $email->data['from'] );
				}

				$request->sendNotifications();
			}
			
			/* Return */
			return TRUE;
		}
		catch ( \OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Make and claim attachments
	 *
	 * @param	array						$files	\IPS\File objects
	 * @param	\IPS\nexus\Support\Reply	$reply	The support request reply
	 * @return	void
	 */
	public static function makeAndClaimAttachments( array $files, \IPS\nexus\Support\Reply $reply )
	{
		foreach ( $files as $file )
		{
			$attachment = $file->makeAttachment('');
			
			\IPS\Db::i()->insert( 'core_attachments_map', array(
				'attachment_id'	=> $attachment['attach_id'],
				'location_key'	=> 'nexus_Support',
				'id1'			=> $reply->request,
				'id2'			=> $reply->id,
			) );
		}
	}
}
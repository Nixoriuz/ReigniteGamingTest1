<?php

// Add comment_approved for new comments functionality:
$SQL[] = "ALTER TABLE blog_comments ADD comment_approved INT(1) DEFAULT '0'";
$SQL[] = "UPDATE blog_comments SET comment_approved = 1 WHERE comment_queued = 0";
$SQL[] = "UPDATE blog_comments SET comment_approved = 0 WHERE comment_queued = 1";

// Get DB prefix:
$PRE = \IPS\Db::i()->prefix;

// Convert tracker to like:
if ( \IPS\Db::i()->checkForTable('blog_tracker') )
{
	if( \IPS\Db::i()->checkForTable('core_like') )
	{
		$SQL[] = "INSERT IGNORE INTO `{$PRE}core_like` (like_id, like_lookup_id, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq) SELECT MD5(CONCAT('blog;blog;', blog_id, ';', member_id)), MD5(CONCAT('blog;blog;', blog_id)), 'blog', 'blog', blog_id, member_id, 0, UNIX_TIMESTAMP(), 1, 'immediate' FROM `{$PRE}blog_tracker`";

		$SQL[] = "INSERT IGNORE INTO `{$PRE}core_like` (like_id, like_lookup_id, like_app, like_area, like_rel_id, like_member_id, like_is_anon, like_added, like_notify_do, like_notify_freq) SELECT MD5(CONCAT('blog;entries;', entry_id, ';', member_id)), MD5(CONCAT('blog;entries;', entry_id)), 'blog', 'entries', entry_id, member_id, 0, UNIX_TIMESTAMP(), 1,'immediate' FROM `{$PRE}blog_tracker` WHERE entry_id <> 0 AND entry_id IS NOT NULL";
	}
	else
	{
		$SQL[] = "INSERT IGNORE INTO `{$PRE}core_follow` (follow_id, follow_app, follow_area, follow_rel_id, follow_member_id, follow_is_anon, follow_added, follow_notify_do, follow_notify_freq) SELECT MD5(CONCAT('blog;blog;', blog_id, ';', member_id)), 'blog', 'blog', blog_id, member_id, 0, UNIX_TIMESTAMP(), 1, 'immediate' FROM `{$PRE}blog_tracker`";

		$SQL[] = "INSERT IGNORE INTO `{$PRE}core_follow` (follow_id, follow_app, follow_area, follow_rel_id, follow_member_id, follow_is_anon, follow_added, follow_notify_do, follow_notify_freq) SELECT MD5(CONCAT('blog;entries;', entry_id, ';', member_id)), 'blog', 'entries', entry_id, member_id, 0, UNIX_TIMESTAMP(), 1,'immediate' FROM `{$PRE}blog_tracker` WHERE entry_id <> 0 AND entry_id IS NOT NULL";
	}

	$SQL[] = "DROP TABLE blog_tracker";
	$SQL[] = "DROP TABLE blog_tracker_queue";
}

<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACL_CAT_MUSICSHARE'			=> 'Music Share',

	'ACL_U_MUSICSHARE_UPLOAD'		=> 'Can upload songs',
	'ACL_U_MUSICSHARE_PLAYLIST'		=> 'Can create personal playlists',
	'ACL_M_MUSICSHARE_MANAGE'		=> 'Can manage (edit/delete) all songs',

	'ACL_U_MUSICSHARE_VIEW'			=> 'Can access the Music section',
	'ACL_U_MUSICSHARE_FEED'			=> 'Can see the recent songs box',

	'ACL_U_MUSICSHARE_NOTIFY'		=> 'Receives notifications of new songs',

	'ACL_U_MUSICSHARE_WALL_POST'	=> 'Can post on author walls',
	'ACL_U_MUSICSHARE_WALL_EDIT'	=> 'Can edit and delete own wall comments',
	'ACL_M_MUSICSHARE_WALL'			=> 'Can moderate everyone\'s wall comments',
]);

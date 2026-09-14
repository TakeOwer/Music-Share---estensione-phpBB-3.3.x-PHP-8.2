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
	'UCP_MUSICSHARE_TITLE'		=> 'Music Share',
	'UCP_MUSICSHARE_SONGS'		=> 'My songs',
	'UCP_MUSICSHARE_UPLOAD'		=> 'Upload song',
	'UCP_MUSICSHARE_PLAYLISTS'	=> 'My playlists',
]);

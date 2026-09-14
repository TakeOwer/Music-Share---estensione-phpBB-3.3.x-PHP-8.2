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

	'ACL_U_MUSICSHARE_UPLOAD'		=> 'Può caricare brani',
	'ACL_U_MUSICSHARE_PLAYLIST'		=> 'Può creare playlist personali',
	'ACL_M_MUSICSHARE_MANAGE'		=> 'Può gestire (modificare/eliminare) tutti i brani',

	'ACL_U_MUSICSHARE_VIEW'			=> 'Può accedere alla sezione Musica',
	'ACL_U_MUSICSHARE_FEED'			=> 'Può vedere il riquadro dei brani recenti',

	'ACL_U_MUSICSHARE_NOTIFY'		=> 'Riceve la notifica dei nuovi brani',

	'ACL_U_MUSICSHARE_WALL_POST'	=> 'Può scrivere sulle bacheche degli autori',
	'ACL_U_MUSICSHARE_WALL_EDIT'	=> 'Può modificare ed eliminare i propri commenti in bacheca',
	'ACL_M_MUSICSHARE_WALL'			=> 'Può moderare i commenti in bacheca di tutti',
]);

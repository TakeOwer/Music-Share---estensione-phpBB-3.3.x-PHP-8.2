<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\ucp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\salvocortesiano\musicshare\ucp\main_module',
			'title'		=> 'UCP_MUSICSHARE_TITLE',
			'modes'		=> array(
				'songs'		=> array(
					'title'	=> 'UCP_MUSICSHARE_SONGS',
					'auth'	=> 'acl_u_musicshare_upload',
					'cat'	=> array('UCP_MUSICSHARE_TITLE'),
				),
				'upload'	=> array(
					'title'	=> 'UCP_MUSICSHARE_UPLOAD',
					'auth'	=> 'acl_u_musicshare_upload',
					'cat'	=> array('UCP_MUSICSHARE_TITLE'),
				),
				'playlists'	=> array(
					'title'	=> 'UCP_MUSICSHARE_PLAYLISTS',
					'auth'	=> 'acl_u_musicshare_playlist',
					'cat'	=> array('UCP_MUSICSHARE_TITLE'),
				),
			),
		);
	}
}

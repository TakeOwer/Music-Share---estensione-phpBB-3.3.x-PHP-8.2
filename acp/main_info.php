<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\salvocortesiano\musicshare\acp\main_module',
			'title'		=> 'ACP_MUSICSHARE_TITLE',
			'modes'		=> array(
				'genres'	=> array(
					'title'	=> 'ACP_MUSICSHARE_GENRES',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'groups'	=> array(
					'title'	=> 'ACP_MUSICSHARE_GROUPS',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_authgroups',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'moderate'	=> array(
					'title'	=> 'ACP_MUSICSHARE_MODERATE',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'tools'	=> array(
					'title'	=> 'ACP_MUSICSHARE_TOOLS',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'maintenance'	=> array(
					'title'	=> 'ACP_MUSICSHARE_MAINTENANCE',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'recognition'	=> array(
					'title'	=> 'ACP_MUSICSHARE_RECOGNITION',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
				'settings'	=> array(
					'title'	=> 'ACP_MUSICSHARE_SETTINGS',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_a_board',
					'cat'	=> array('ACP_MUSICSHARE_TITLE'),
				),
			),
		);
	}
}

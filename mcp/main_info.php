<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\mcp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\salvocortesiano\musicshare\mcp\main_module',
			'title'		=> 'MCP_MUSICSHARE_TITLE',
			'modes'		=> array(
				'pending'	=> array(
					'title'	=> 'MCP_MUSICSHARE_PENDING',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_m_musicshare_manage',
					'cat'	=> array('MCP_MUSICSHARE_TITLE'),
				),
				'songs'		=> array(
					'title'	=> 'MCP_MUSICSHARE_SONGS',
					'auth'	=> 'ext_salvocortesiano/musicshare && acl_m_musicshare_manage',
					'cat'	=> array('MCP_MUSICSHARE_TITLE'),
				),
			),
		);
	}
}

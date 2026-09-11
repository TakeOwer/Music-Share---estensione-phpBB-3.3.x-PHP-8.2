<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\migrations;

class add_scroll_after extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_view_permissions'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_feed_scroll_after']);
	}

	public function update_data()
	{
		return [
			// Da quanti brani in poi il riquadro diventa scorrevole.
			// 0 = mai, il riquadro si allunga quanto serve.
			['config.add', ['musicshare_feed_scroll_after', 20]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_feed_scroll_after']],
		];
	}
}

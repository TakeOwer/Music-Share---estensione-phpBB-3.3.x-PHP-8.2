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

class add_toast_self extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_feed_title'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_toast_self']);
	}

	public function update_data()
	{
		return [
			// Mostrare l'avviso anche a chi ha caricato il brano.
			// Predefinito: no, come si comportava fino ad ora.
			['config.add', ['musicshare_toast_self', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_toast_self']],
		];
	}
}

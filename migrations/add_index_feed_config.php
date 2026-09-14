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

class add_index_feed_config extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_feed_config'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_index_feed']);
	}

	public function update_data()
	{
		return [
			// 0 = disattivato, 1 = solo indice, 2 = tutte le pagine
			['config.add', ['musicshare_index_feed', 1]],
			// quanti brani mostrare in tutto
			['config.add', ['musicshare_index_feed_count', 6]],
			// quanti al massimo per ciascun utente (0 = nessun limite)
			['config.add', ['musicshare_index_feed_per_user', 2]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_index_feed']],
			['config.remove', ['musicshare_index_feed_count']],
			['config.remove', ['musicshare_index_feed_per_user']],
		];
	}
}

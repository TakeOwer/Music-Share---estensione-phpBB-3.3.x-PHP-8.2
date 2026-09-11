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

class add_player_scope extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_index_feed_config'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_player_scope']);
	}

	public function update_data()
	{
		return [
			// 'all'   = lettore in basso su tutte le pagine
			// 'music' = solo nelle pagine della sezione Musica; altrove si
			//           comanda la riproduzione dai comandi sulla riga
			['config.add', ['musicshare_player_scope', 'music']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_player_scope']],
		];
	}
}

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

class add_feed_config extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_stats_config'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_toast_enabled']);
	}

	public function update_data()
	{
		return [
			// Avviso a comparsa sui nuovi brani caricati
			['config.add', ['musicshare_toast_enabled', 1]],
			// Ogni quanti secondi controllare i nuovi caricamenti
			['config.add', ['musicshare_toast_interval', 90]],
			// Quanti brani mostrare nel feed dei caricamenti recenti
			['config.add', ['musicshare_recent_count', 8]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_toast_enabled']],
			['config.remove', ['musicshare_toast_interval']],
			['config.remove', ['musicshare_recent_count']],
		];
	}
}

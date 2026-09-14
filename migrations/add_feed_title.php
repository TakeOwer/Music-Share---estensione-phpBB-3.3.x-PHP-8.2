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

class add_feed_title extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_song_download'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_feed_title_set']);
	}

	public function update_data()
	{
		return [
			// Titolo personalizzato del riquadro, una voce per lingua.
			// Vuoto = si usa la traduzione predefinita dell'estensione.
			['config_text.add', ['musicshare_feed_title', '']],
			// Serve solo a sapere che questa migrazione e' gia' passata:
			// config_text non compare fra le chiavi di $config.
			['config.add', ['musicshare_feed_title_set', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config_text.remove', ['musicshare_feed_title']],
			['config.remove', ['musicshare_feed_title_set']],
		];
	}
}

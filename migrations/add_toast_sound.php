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

class add_toast_sound extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_song_description'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_toast_sound']);
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_toast_sound', 1]],
			['config.add', ['musicshare_toast_volume', 30]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_toast_sound']],
			['config.remove', ['musicshare_toast_volume']],
		];
	}
}

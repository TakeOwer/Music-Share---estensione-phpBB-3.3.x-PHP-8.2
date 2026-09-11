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

class add_bbcode extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_toast_sound'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_bbcode']);
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_bbcode', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_bbcode']],
		];
	}
}

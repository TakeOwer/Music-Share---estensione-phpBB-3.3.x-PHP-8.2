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

class add_folder_naming extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_hash_and_download'];
	}

	public function effectively_installed()
	{
		return isset($this->config['musicshare_folder_naming']);
	}

	public function update_data()
	{
		return [
			// 'username' = cartelle con il nome dell'utente, 'id' = solo ID numerico
			['config.add', ['musicshare_folder_naming', 'username']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_folder_naming']],
		];
	}
}

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

class add_song_description extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_recognition_module'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// descrizione scritta dall'utente al caricamento
					'song_description'	=> ['VCHAR_UNI:300', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['song_description'],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_descriptions', 1]],
			['config.add', ['musicshare_description_max', 300]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_descriptions']],
			['config.remove', ['musicshare_description_max']],
		];
	}
}

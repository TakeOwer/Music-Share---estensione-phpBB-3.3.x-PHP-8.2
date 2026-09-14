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

class add_music_metadata extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_follows'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// Dati che servono a USARE un brano, non solo ad
					// ascoltarlo: sono quelli che cerca chi vuole un loop
					// da inserire in un proprio pezzo.
					'song_license'	=> ['VCHAR:32', ''],
					'song_bpm'		=> ['USINT', 0],
					'song_key'		=> ['VCHAR:16', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['song_license', 'song_bpm', 'song_key'],
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_show_license', 1]],
			['config.add', ['musicshare_show_bpm', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_show_license']],
			['config.remove', ['musicshare_show_bpm']],
		];
	}
}

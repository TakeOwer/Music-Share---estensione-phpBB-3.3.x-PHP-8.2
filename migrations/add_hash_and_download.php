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

class add_hash_and_download extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_modules_extra'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// impronta del file, per riconoscere i caricamenti doppi
					'file_hash'	=> ['VCHAR:32', ''],
				],
			],
			'add_index'		=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					'ms_file_hash'	=> ['file_hash'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_keys'		=> [
				$this->table_prefix . 'musicshare_songs'	=> ['ms_file_hash'],
			],
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['file_hash'],
			],
		];
	}

	public function update_data()
	{
		return [
			// Consenti agli utenti di scaricare il file originale
			['config.add', ['musicshare_allow_download', 0]],
			// Blocca il caricamento di un brano già presente per lo stesso utente
			['config.add', ['musicshare_block_duplicates', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_allow_download']],
			['config.remove', ['musicshare_block_duplicates']],
		];
	}
}

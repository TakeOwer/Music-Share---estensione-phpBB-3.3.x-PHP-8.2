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

class add_song_download extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_votes'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// scelta dell'autore: consentire o meno il download.
					// Ha effetto solo se il download è abilitato in ACP.
					'allow_download'	=> ['BOOL', 1],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['allow_download'],
			],
		];
	}
}

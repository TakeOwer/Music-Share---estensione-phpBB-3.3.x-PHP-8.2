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

class add_play_log extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_song_topic'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_plays'	=> [
					'COLUMNS'	=> [
						'play_id'	=> ['UINT', null, 'auto_increment'],
						'song_id'	=> ['UINT', 0],
						'user_id'	=> ['UINT', 0],
						'play_time'	=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'play_id',
					'KEYS'	=> [
						// l'indice serve alle classifiche per periodo:
						// senza, la query dovrebbe leggere tutta la tabella
						'ms_play_time'		=> ['INDEX', 'play_time'],
						'ms_play_song'		=> ['INDEX', ['song_id', 'play_time']],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [$this->table_prefix . 'musicshare_plays'],
		];
	}

	public function update_data()
	{
		return [
			// per quanti giorni conservare il dettaglio degli ascolti:
			// il contatore complessivo resta comunque sul brano
			['config.add', ['musicshare_plays_keep_days', 90]],
			['config.add', ['musicshare_plays_cleanup_last', 0]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_plays_keep_days']],
			['config.remove', ['musicshare_plays_cleanup_last']],
		];
	}
}

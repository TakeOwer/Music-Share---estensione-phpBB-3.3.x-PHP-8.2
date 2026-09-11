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

class add_votes extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_player_scope'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_votes'	=> [
					'COLUMNS'		=> [
						'song_id'	=> ['UINT', 0],
						'user_id'	=> ['UINT', 0],
						// 1 = mi piace, -1 = non mi piace
						'vote'		=> ['TINT:2', 0],
						'vote_time'	=> ['TIMESTAMP', 0],
					],
					// un solo voto per utente e per brano
					'PRIMARY_KEY'	=> ['song_id', 'user_id'],
					'KEYS'			=> [
						'user_id'	=> ['INDEX', 'user_id'],
					],
				],
			],
			// contatori sul brano, per non ricontare a ogni pagina
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					'song_likes'	=> ['UINT:4', 0],
					'song_dislikes'	=> ['UINT:4', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> ['song_likes', 'song_dislikes'],
			],
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_votes',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['musicshare_votes_enabled', 1]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_votes_enabled']],
		];
	}
}

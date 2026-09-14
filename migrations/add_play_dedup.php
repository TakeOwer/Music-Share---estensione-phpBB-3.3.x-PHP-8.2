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

class add_play_dedup extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_music_metadata'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_plays'	=> [
					// Per gli ospiti l'identificativo utente e' lo stesso
					// per tutti: senza la sessione, "un ascolto per
					// utente" li ridurrebbe a una persona sola.
					'session_id'	=> ['VCHAR:32', ''],
				],
			],
			'add_index'	=> [
				$this->table_prefix . 'musicshare_plays'	=> [
					// serve alla verifica "ha gia' ascoltato di recente?"
					'ms_play_user'	=> ['user_id', 'song_id', 'play_time'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_keys'	=> [
				$this->table_prefix . 'musicshare_plays'	=> ['ms_play_user'],
			],
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_plays'	=> ['session_id'],
			],
		];
	}

	public function update_data()
	{
		return [
			// ore minime fra due ascolti conteggiati della stessa persona
			// sullo stesso brano. 0 = conta ogni riproduzione.
			['config.add', ['musicshare_play_interval', 12]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_play_interval']],
		];
	}
}

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

/**
 * Bacheca dell'autore.
 *
 * La tabella tiene solo il legame fra un autore e il suo argomento: i
 * commenti sono messaggi veri del forum e stanno dove stanno tutti gli
 * altri. Cosi' li moderano i moderatori, si possono segnalare, entrano
 * nella ricerca e nelle notifiche di phpBB senza costruire un secondo
 * sistema di discussione.
 */
class add_walls extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_tools_module'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_walls'	=> [
					'COLUMNS'	=> [
						'user_id'		=> ['UINT', 0],
						'topic_id'		=> ['UINT', 0],
						'created_time'	=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'user_id',
					'KEYS'			=> [
						'topic_id'	=> ['INDEX', 'topic_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_walls',
			],
		];
	}

	public function update_data()
	{
		return [
			// spenta di partenza: apre argomenti a nome degli utenti in
			// una sezione del forum, va scelta dall'amministratore
			['config.add', ['musicshare_wall_enabled', 0]],
			['config.add', ['musicshare_wall_forum', 0]],
			// quanti messaggi mostrare nel riquadro della pagina autore
			['config.add', ['musicshare_wall_preview', 5]],
			// solo chi segue l'autore vede il modulo per scrivere
			['config.add', ['musicshare_wall_follow_only', 1]],
			['config_text.add', ['musicshare_wall_title', 'Bacheca di %1$s']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['musicshare_wall_enabled']],
			['config.remove', ['musicshare_wall_forum']],
			['config.remove', ['musicshare_wall_preview']],
			['config.remove', ['musicshare_wall_follow_only']],
			['config_text.remove', ['musicshare_wall_title']],
		];
	}
}

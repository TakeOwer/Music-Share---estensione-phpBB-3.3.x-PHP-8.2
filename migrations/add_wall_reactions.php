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
 * Reazioni ai commenti in bacheca: mi piace, non mi piace, cuore.
 *
 * Niente contatori sulla riga del commento, a differenza dei brani: i
 * commenti mostrati in una pagina sono pochi e si contano con una sola
 * interrogazione, mentre due colonne in piu' andrebbero tenute
 * allineate a mano a ogni cancellazione.
 */
class add_wall_reactions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\rework_walls'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_wall_reactions'	=> [
					'COLUMNS'	=> [
						'comment_id'	=> ['UINT', 0],
						'user_id'		=> ['UINT', 0],
						// 1 = mi piace, 2 = non mi piace, 3 = cuore
						'reaction'		=> ['TINT:2', 0],
						'reaction_time'	=> ['TIMESTAMP', 0],
					],
					// una reazione di ciascun tipo per persona e commento
					'PRIMARY_KEY'	=> ['comment_id', 'user_id', 'reaction'],
					'KEYS'			=> [
						'comment_id'	=> ['INDEX', 'comment_id'],
						'user_id'		=> ['INDEX', 'user_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_wall_reactions',
			],
		];
	}
}

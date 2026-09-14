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

class fix_stale_topics extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_download_count'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'clear_stale_topics']]],
		];
	}

	/**
	 * Slega i brani dagli argomenti che non esistono piu'.
	 *
	 * Finche' l'estensione non ascoltava la cancellazione, un argomento
	 * rimosso lasciava il brano collegato a una discussione inesistente:
	 * compariva un collegamento che portava a "argomento non esiste", e
	 * il pulsante per aprirne uno nuovo restava nascosto perche' il
	 * brano risultava gia' collegato. Qui si ripuliscono quei casi.
	 *
	 * @return void
	 */
	public function clear_stale_topics()
	{
		$tabella = $this->table_prefix . 'musicshare_songs';

		$sql = 'UPDATE ' . $tabella . '
			SET topic_id = 0, post_id = 0
			WHERE topic_id > 0
				AND NOT EXISTS (
					SELECT 1 FROM ' . TOPICS_TABLE . ' t
					WHERE t.topic_id = ' . $tabella . '.topic_id
						AND t.topic_visibility = ' . ITEM_APPROVED . '
				)';
		$this->db->sql_query($sql);
	}
}

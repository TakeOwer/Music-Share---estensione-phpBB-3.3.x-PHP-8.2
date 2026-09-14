<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\service;

/**
 * Rimozione delle notifiche dell'estensione già lette.
 *
 * Una notifica letta non serve più a nessuno: resta solo a occupare
 * spazio in una tabella che phpBB interroga a ogni pagina. Con molti
 * destinatari il volume cresce in fretta, quindi conviene ripulire.
 */
class notification_cleaner
{
	/** Righe cancellate per ciclo: si procede a blocchi per non tenere
	 *  la tabella occupata a lungo. */
	const BLOCCO = 500;

	protected $db;
	protected $config;
	protected $table_prefix;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		$table_prefix
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Tipi di notifica introdotti dall'estensione.
	 *
	 * @return array identificativi numerici
	 */
	protected function get_type_ids()
	{
		$nomi = array(
			'salvocortesiano.musicshare.notification.type.song_new',
			'salvocortesiano.musicshare.notification.type.song_approved',
			'salvocortesiano.musicshare.notification.type.song_rejected',
		);

		$sql = 'SELECT notification_type_id
			FROM ' . $this->table_prefix . 'notification_types
			WHERE ' . $this->db->sql_in_set('notification_type_name', $nomi);
		$result = $this->db->sql_query($sql);

		$ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['notification_type_id'];
		}
		$this->db->sql_freeresult($result);

		return $ids;
	}

	/**
	 * Quante notifiche dell'estensione sono già lette e più vecchie del
	 * periodo di conservazione.
	 *
	 * @param int $giorni 0 = tutte le lette, senza limite di data
	 * @return int
	 */
	public function count_removable($giorni = null)
	{
		$where = $this->build_where($giorni);

		if ($where === '')
		{
			return 0;
		}

		$sql = 'SELECT COUNT(*) AS quante FROM ' . $this->table_prefix . 'notifications WHERE ' . $where;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quante'];
	}

	/**
	 * Cancella le notifiche lette, a blocchi.
	 *
	 * @param int $giorni periodo di conservazione
	 * @param int $max_blocchi limite di blocchi per chiamata
	 * @return int righe rimosse
	 */
	public function clean($giorni = null, $max_blocchi = 40)
	{
		$where = $this->build_where($giorni);

		if ($where === '')
		{
			return 0;
		}

		$rimosse = 0;

		for ($i = 0; $i < $max_blocchi; $i++)
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . 'notifications WHERE ' . $where;
			$this->db->sql_query_limit($sql, self::BLOCCO);
			$quante = (int) $this->db->sql_affectedrows();
			$rimosse += $quante;

			if ($quante < self::BLOCCO)
			{
				// finite: non serve un altro giro
				break;
			}
		}

		return $rimosse;
	}

	/**
	 * Quante notifiche dell'estensione esistono in tutto, lette o no.
	 *
	 * @return int
	 */
	public function count_all()
	{
		$ids = $this->get_type_ids();

		if (empty($ids))
		{
			return 0;
		}

		$sql = 'SELECT COUNT(*) AS quante FROM ' . $this->table_prefix . 'notifications
			WHERE ' . $this->db->sql_in_set('notification_type_id', $ids);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quante'];
	}

	/**
	 * Rimuove TUTTE le notifiche dell'estensione, comprese quelle non
	 * ancora lette.
	 *
	 * Operazione distruttiva: gli avvisi che gli utenti non hanno ancora
	 * visto spariscono senza essere mostrati. Va offerta solo dietro
	 * conferma esplicita.
	 *
	 * @param int $max_blocchi
	 * @return int righe rimosse
	 */
	public function purge_all($max_blocchi = 200)
	{
		$ids = $this->get_type_ids();

		if (empty($ids))
		{
			return 0;
		}

		$where = $this->db->sql_in_set('notification_type_id', $ids);
		$rimosse = 0;

		for ($i = 0; $i < $max_blocchi; $i++)
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . 'notifications WHERE ' . $where;
			$this->db->sql_query_limit($sql, self::BLOCCO);
			$quante = (int) $this->db->sql_affectedrows();
			$rimosse += $quante;

			if ($quante < self::BLOCCO)
			{
				break;
			}
		}

		return $rimosse;
	}

	/**
	 * Condizione comune a conteggio e cancellazione.
	 *
	 * @param int|null $giorni
	 * @return string stringa vuota se non c'è nulla da fare
	 */
	protected function build_where($giorni = null)
	{
		$ids = $this->get_type_ids();

		if (empty($ids))
		{
			return '';
		}

		$where = $this->db->sql_in_set('notification_type_id', $ids) . ' AND notification_read = 1';

		if ($giorni === null)
		{
			$giorni = isset($this->config['musicshare_cleanup_days'])
				? (int) $this->config['musicshare_cleanup_days']
				: 30;
		}

		$giorni = (int) $giorni;

		if ($giorni > 0)
		{
			$where .= ' AND notification_time < ' . (time() - $giorni * 86400);
		}

		return $where;
	}
}

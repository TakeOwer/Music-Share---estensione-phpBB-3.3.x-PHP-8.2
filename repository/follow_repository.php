<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\repository;

/**
 * Chi segue chi.
 *
 * Serve al gesto intermedio che mancava: le notifiche erano tutto o
 * niente, o le ricevevi per ogni brano del forum o per nessuno. Seguire
 * un singolo autore permette di riaprirle senza tornare a decine di
 * migliaia di righe per ogni caricamento.
 */
class follow_repository
{
	protected $db;
	protected $table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table)
	{
		$this->db = $db;
		$this->table = $table;
	}

	/**
	 * @param int $user_id chi segue
	 * @param int $author_id chi viene seguito
	 * @return bool
	 */
	public function is_following($user_id, $author_id)
	{
		$sql = 'SELECT 1 AS trovato FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id . '
				AND author_id = ' . (int) $author_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	/**
	 * Inizia a seguire. Non fa nulla se già lo si seguiva.
	 *
	 * @return bool true se qualcosa è cambiato
	 */
	public function follow($user_id, $author_id)
	{
		if ((int) $user_id === (int) $author_id || $this->is_following($user_id, $author_id))
		{
			return false;
		}

		$this->db->sql_query('INSERT INTO ' . $this->table . ' ' . $this->db->sql_build_array('INSERT', array(
			'user_id'		=> (int) $user_id,
			'author_id'		=> (int) $author_id,
			'follow_time'	=> time(),
		)));

		return true;
	}

	/**
	 * Smette di seguire.
	 *
	 * @return bool
	 */
	public function unfollow($user_id, $author_id)
	{
		$this->db->sql_query('DELETE FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id . '
				AND author_id = ' . (int) $author_id);

		return (bool) $this->db->sql_affectedrows();
	}

	/**
	 * Chi segue un dato autore.
	 *
	 * @param int $author_id
	 * @return array identificativi
	 */
	public function get_followers($author_id)
	{
		$sql = 'SELECT user_id FROM ' . $this->table . '
			WHERE author_id = ' . (int) $author_id;
		$result = $this->db->sql_query($sql);

		$out = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Chi un utente sta seguendo, con nome e numero di brani.
	 *
	 * Senza questo elenco si poteva smettere di seguire qualcuno solo
	 * andando sulla sua pagina: chi non ricordava di averlo seguito non
	 * aveva modo di scoprirlo.
	 *
	 * @param int $user_id
	 * @param string $songs_table
	 * @return array
	 */
	public function get_following($user_id, $songs_table)
	{
		$sql = 'SELECT f.author_id, f.follow_time, u.username, u.user_colour, u.user_allow_pm,
				(
					SELECT COUNT(*) FROM ' . $songs_table . ' s
					WHERE s.user_id = f.author_id AND s.song_approved = 1
				) AS songs
			FROM ' . $this->table . ' f, ' . USERS_TABLE . ' u
			WHERE f.user_id = ' . (int) $user_id . '
				AND u.user_id = f.author_id
			ORDER BY u.username_clean ASC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Quanti utenti segue una persona.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public function count_following($user_id)
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quanti'];
	}

	/**
	 * Quanti seguono un autore.
	 *
	 * @param int $author_id
	 * @return int
	 */
	public function count_followers($author_id)
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table . '
			WHERE author_id = ' . (int) $author_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quanti'];
	}

	/**
	 * Un utente accetta messaggi privati?
	 *
	 * Sta qui perche' e' l'unico punto dell'estensione che deve
	 * interrogare la tabella degli utenti per questo dato, e il
	 * controller non ha una connessione propria.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function accepts_pm($user_id)
	{
		$sql = 'SELECT user_allow_pm FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (bool) $row['user_allow_pm'] : false;
	}

	/**
	 * Rimuove i legami di un utente cancellato, in entrambe le direzioni.
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function purge_user($user_id)
	{
		$this->db->sql_query('DELETE FROM ' . $this->table . '
			WHERE user_id = ' . (int) $user_id . '
				OR author_id = ' . (int) $user_id);
	}
}

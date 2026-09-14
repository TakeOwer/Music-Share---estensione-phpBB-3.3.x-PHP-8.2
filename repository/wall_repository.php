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
 * Commenti in bacheca.
 *
 * Vivono in una tabella propria e restano sulla pagina dell'autore.
 * Un solo livello di rientro: un commento, e sotto le sue risposte.
 */
class wall_repository
{
	/** Reazioni possibili a un commento */
	const REACT_LIKE = 1;
	const REACT_DISLIKE = 2;
	const REACT_HEART = 3;

	protected $db;
	protected $table;
	protected $reactions_table;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table, $reactions_table)
	{
		$this->db = $db;
		$this->table = $table;
		$this->reactions_table = $reactions_table;
	}

	/**
	 * I tre tipi ammessi, in ordine di comparsa.
	 *
	 * @return array
	 */
	public static function reaction_types()
	{
		return array(self::REACT_LIKE, self::REACT_DISLIKE, self::REACT_HEART);
	}

	/**
	 * Colonne del commento piu' quelle di chi l'ha scritto.
	 *
	 * @return string
	 */
	protected function select_fields()
	{
		return 'c.comment_id, c.wall_user_id, c.parent_id, c.user_id, c.comment_text,
			c.bbcode_uid, c.bbcode_bitfield, c.bbcode_options, c.comment_time,
			c.edit_time, c.edit_user,
			u.username, u.user_colour, u.user_avatar, u.user_avatar_type,
			u.user_avatar_width, u.user_avatar_height';
	}

	/**
	 * Commenti di primo livello di una bacheca, dal piu' recente.
	 *
	 * @param int $wall_user_id
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	public function get_comments($wall_user_id, $start = 0, $limit = 5)
	{
		$wall_user_id = (int) $wall_user_id;

		if ($wall_user_id <= 0)
		{
			return array();
		}

		$sql = 'SELECT ' . $this->select_fields() . '
			FROM ' . $this->table . ' c, ' . USERS_TABLE . ' u
			WHERE c.wall_user_id = ' . $wall_user_id . '
				AND c.parent_id = 0
				AND u.user_id = c.user_id
			ORDER BY c.comment_time DESC, c.comment_id DESC';
		$result = $this->db->sql_query_limit($sql, max(1, (int) $limit), max(0, (int) $start));
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Risposte dei commenti indicati, dalla piu' vecchia: sotto un
	 * commento si legge nell'ordine in cui si e' discusso.
	 *
	 * @param array $parent_ids
	 * @return array raggruppate per commento padre
	 */
	public function get_replies(array $parent_ids)
	{
		$parent_ids = array_filter(array_map('intval', $parent_ids));

		if (empty($parent_ids))
		{
			return array();
		}

		$sql = 'SELECT ' . $this->select_fields() . '
			FROM ' . $this->table . ' c, ' . USERS_TABLE . ' u
			WHERE ' . $this->db->sql_in_set('c.parent_id', $parent_ids) . '
				AND u.user_id = c.user_id
			ORDER BY c.comment_time ASC, c.comment_id ASC';
		$result = $this->db->sql_query($sql);

		$out = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[(int) $row['parent_id']][] = $row;
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Un singolo commento, con i dati di chi l'ha scritto.
	 *
	 * @param int $comment_id
	 * @return array|false
	 */
	public function get_comment($comment_id)
	{
		$comment_id = (int) $comment_id;

		if ($comment_id <= 0)
		{
			return false;
		}

		$sql = 'SELECT ' . $this->select_fields() . '
			FROM ' . $this->table . ' c, ' . USERS_TABLE . ' u
			WHERE c.comment_id = ' . $comment_id . '
				AND u.user_id = c.user_id';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Quanti commenti di primo livello ha una bacheca. Serve per la
	 * paginazione, che scorre solo quelli.
	 *
	 * @param int $wall_user_id
	 * @return int
	 */
	public function count_comments($wall_user_id)
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table . '
			WHERE wall_user_id = ' . (int) $wall_user_id . '
				AND parent_id = 0';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (int) $row['quanti'] : 0;
	}

	/**
	 * Totale di commenti e risposte: e' il numero sul pulsante.
	 *
	 * @param int $wall_user_id
	 * @return int
	 */
	public function count_all($wall_user_id)
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table . '
			WHERE wall_user_id = ' . (int) $wall_user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (int) $row['quanti'] : 0;
	}

	/**
	 * Conteggi per piu' bacheche in una volta sola, per gli elenchi.
	 *
	 * @param array $user_ids
	 * @return array user_id => quanti
	 */
	public function count_for_users(array $user_ids)
	{
		$user_ids = array_filter(array_map('intval', $user_ids));

		if (empty($user_ids))
		{
			return array();
		}

		$sql = 'SELECT wall_user_id, COUNT(*) AS quanti
			FROM ' . $this->table . '
			WHERE ' . $this->db->sql_in_set('wall_user_id', $user_ids) . '
			GROUP BY wall_user_id';
		$result = $this->db->sql_query($sql);

		$out = array();

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[(int) $row['wall_user_id']] = (int) $row['quanti'];
		}
		$this->db->sql_freeresult($result);

		return $out;
	}

	/**
	 * Salva un commento nuovo.
	 *
	 * @param array $dati
	 * @return int identificativo assegnato
	 */
	public function add(array $dati)
	{
		$this->db->sql_query('INSERT INTO ' . $this->table . ' ' .
			$this->db->sql_build_array('INSERT', $dati));

		return (int) $this->db->sql_nextid();
	}

	/**
	 * Aggiorna il testo di un commento.
	 *
	 * @param int $comment_id
	 * @param array $dati
	 * @return void
	 */
	public function update($comment_id, array $dati)
	{
		$this->db->sql_query('UPDATE ' . $this->table . '
			SET ' . $this->db->sql_build_array('UPDATE', $dati) . '
			WHERE comment_id = ' . (int) $comment_id);
	}

	/**
	 * Cancella un commento e, se era di primo livello, le sue risposte.
	 *
	 * @param int $comment_id
	 * @return int quante righe sono sparite
	 */
	public function delete($comment_id)
	{
		$comment_id = (int) $comment_id;

		if ($comment_id <= 0)
		{
			return 0;
		}

		// prima si raccolgono gli identificativi, poi si cancellano le
		// reazioni: dopo la cancellazione dei commenti non ci sarebbe
		// piu' modo di sapere quali risposte esistevano, e le loro
		// reazioni resterebbero nel database per sempre
		$ids = array($comment_id);

		$result = $this->db->sql_query('SELECT comment_id FROM ' . $this->table . '
			WHERE parent_id = ' . $comment_id);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['comment_id'];
		}
		$this->db->sql_freeresult($result);

		$this->delete_reactions($ids);

		$this->db->sql_query('DELETE FROM ' . $this->table . '
			WHERE comment_id = ' . $comment_id . '
				OR parent_id = ' . $comment_id);

		return (int) $this->db->sql_affectedrows();
	}

	/**
	 * Conteggi delle reazioni per i commenti indicati, piu' quelle di
	 * chi sta guardando.
	 *
	 * Due interrogazioni per l'intera pagina, non due per commento.
	 *
	 * @param array $comment_ids
	 * @param int $user_id 0 per gli ospiti
	 * @return array comment_id => array('counts' => tipo => quanti,
	 *                                   'mine' => array di tipi)
	 */
	public function get_reactions(array $comment_ids, $user_id = 0)
	{
		$comment_ids = array_filter(array_map('intval', $comment_ids));

		if (empty($comment_ids))
		{
			return array();
		}

		$out = array();

		foreach ($comment_ids as $id)
		{
			$out[$id] = array('counts' => array(), 'mine' => array());
		}

		$sql = 'SELECT comment_id, reaction, COUNT(*) AS quanti
			FROM ' . $this->reactions_table . '
			WHERE ' . $this->db->sql_in_set('comment_id', $comment_ids) . '
			GROUP BY comment_id, reaction';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$out[(int) $row['comment_id']]['counts'][(int) $row['reaction']] = (int) $row['quanti'];
		}
		$this->db->sql_freeresult($result);

		$user_id = (int) $user_id;

		if ($user_id > 0 && $user_id !== ANONYMOUS)
		{
			$sql = 'SELECT comment_id, reaction
				FROM ' . $this->reactions_table . '
				WHERE user_id = ' . $user_id . '
					AND ' . $this->db->sql_in_set('comment_id', $comment_ids);
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$out[(int) $row['comment_id']]['mine'][] = (int) $row['reaction'];
			}
			$this->db->sql_freeresult($result);
		}

		return $out;
	}

	/**
	 * Aggiunge o toglie una reazione.
	 *
	 * Premendo di nuovo la stessa si toglie. Mi piace e non mi piace si
	 * escludono a vicenda, come nel voto dei brani; il cuore e' a parte
	 * e convive con gli altri due.
	 *
	 * @param int $comment_id
	 * @param int $user_id
	 * @param int $reaction
	 * @return array stato aggiornato di quel commento
	 */
	public function set_reaction($comment_id, $user_id, $reaction)
	{
		$comment_id = (int) $comment_id;
		$user_id = (int) $user_id;
		$reaction = (int) $reaction;

		$sql = 'SELECT reaction FROM ' . $this->reactions_table . '
			WHERE comment_id = ' . $comment_id . '
				AND user_id = ' . $user_id . '
				AND reaction = ' . $reaction;
		$result = $this->db->sql_query($sql);
		$gia_messa = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($gia_messa)
		{
			$this->db->sql_query('DELETE FROM ' . $this->reactions_table . '
				WHERE comment_id = ' . $comment_id . '
					AND user_id = ' . $user_id . '
					AND reaction = ' . $reaction);
		}
		else
		{
			if ($reaction === self::REACT_LIKE || $reaction === self::REACT_DISLIKE)
			{
				$this->db->sql_query('DELETE FROM ' . $this->reactions_table . '
					WHERE comment_id = ' . $comment_id . '
						AND user_id = ' . $user_id . '
						AND ' . $this->db->sql_in_set('reaction', array(self::REACT_LIKE, self::REACT_DISLIKE)));
			}

			$this->db->sql_query('INSERT INTO ' . $this->reactions_table . ' ' .
				$this->db->sql_build_array('INSERT', array(
					'comment_id'	=> $comment_id,
					'user_id'		=> $user_id,
					'reaction'		=> $reaction,
					'reaction_time'	=> time(),
				)));
		}

		$stato = $this->get_reactions(array($comment_id), $user_id);

		return $stato[$comment_id];
	}

	/**
	 * Le reazioni dei commenti cancellati.
	 *
	 * @param array $comment_ids
	 * @return void
	 */
	public function delete_reactions(array $comment_ids)
	{
		$comment_ids = array_filter(array_map('intval', $comment_ids));

		if (empty($comment_ids))
		{
			return;
		}

		$this->db->sql_query('DELETE FROM ' . $this->reactions_table . '
			WHERE ' . $this->db->sql_in_set('comment_id', $comment_ids));
	}

	/**
	 * Ripulisce quando un utente viene cancellato dal forum: sia la sua
	 * bacheca sia i commenti che ha lasciato altrove.
	 *
	 * Senza questa pulizia resterebbero righe che puntano a un utente
	 * inesistente: le query uniscono la tabella degli utenti, quindi
	 * quei commenti sparirebbero dalla vista restando pero' nel
	 * database, e i conteggi direbbero un numero diverso da quello che
	 * si vede.
	 *
	 * @param array $user_ids
	 * @return void
	 */
	public function purge_users(array $user_ids)
	{
		$user_ids = array_filter(array_map('intval', $user_ids));

		if (empty($user_ids))
		{
			return;
		}

		// reazioni dei commenti che stanno per sparire, piu' quelle che
		// l'utente aveva lasciato sui commenti altrui
		$ids = array();
		$result = $this->db->sql_query('SELECT comment_id FROM ' . $this->table . '
			WHERE ' . $this->db->sql_in_set('wall_user_id', $user_ids) . '
				OR ' . $this->db->sql_in_set('user_id', $user_ids));

		while ($row = $this->db->sql_fetchrow($result))
		{
			$ids[] = (int) $row['comment_id'];
		}
		$this->db->sql_freeresult($result);

		$this->delete_reactions($ids);

		$this->db->sql_query('DELETE FROM ' . $this->reactions_table . '
			WHERE ' . $this->db->sql_in_set('user_id', $user_ids));

		$this->db->sql_query('DELETE FROM ' . $this->table . '
			WHERE ' . $this->db->sql_in_set('wall_user_id', $user_ids) . '
				OR ' . $this->db->sql_in_set('user_id', $user_ids));
	}
}

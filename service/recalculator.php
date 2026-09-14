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
 * Ricalcolo dei contatori a partire dai dati di origine.
 *
 * I numeri salvati sul brano - mi piace, non mi piace, download - sono
 * copie comode di dati che vivono altrove, nelle tabelle dei voti e dei
 * download. Copie del genere si scollano dalla realta': una
 * cancellazione andata storta, una migrazione interrotta, un
 * aggiornamento sfortunato. Qui si rifanno i conti dalle tabelle
 * originali.
 *
 * Attenzione: gli ascolti NON sono ricalcolabili allo stesso modo. Il
 * registro datato viene sfoltito periodicamente, quindi ricalcolare da
 * li' azzererebbe la storia piu' vecchia invece di correggerla.
 */
class recalculator
{
	protected $db;
	protected $table_prefix;

	public function __construct(\phpbb\db\driver\driver_interface $db, $table_prefix)
	{
		$this->db = $db;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Quadro d'insieme dei dati dell'estensione.
	 *
	 * La scheda mostrava solo le cose da correggere: con tutto a posto
	 * restavano quattro zeri, che non dicono nulla su cosa c'e' dentro.
	 *
	 * @return array
	 */
	/** @var \salvocortesiano\musicshare\service\metadata_extractor */
	protected $metadata_extractor = null;

	/** @var \salvocortesiano\musicshare\service\storage_helper */
	protected $storage_helper = null;

	/**
	 * Iniettati dal contenitore dopo la costruzione, non nel
	 * costruttore: aggiungere argomenti a una firma gia' in uso vuol
	 * dire poter sbagliare l'ordine di quelli esistenti senza che nulla
	 * lo segnali.
	 *
	 * @param \salvocortesiano\musicshare\service\metadata_extractor $extractor
	 * @param \salvocortesiano\musicshare\service\storage_helper $storage
	 * @return void
	 */
	public function set_quality_tools(
		\salvocortesiano\musicshare\service\metadata_extractor $extractor,
		\salvocortesiano\musicshare\service\storage_helper $storage
	)
	{
		$this->metadata_extractor = $extractor;
		$this->storage_helper = $storage;
	}

	/**
	 * Brani senza i dati tecnici dell'audio.
	 *
	 * Sono quelli caricati prima che l'estensione li leggesse: il file
	 * c'e' ancora, basta rianalizzarlo.
	 *
	 * @return int
	 */
	public function count_missing_quality()
	{
		// esclude quelli gia' provati senza successo: sono marcati, e
		// contarli terrebbe il numero fermo sopra lo zero per sempre
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . "musicshare_songs
			WHERE song_bitrate = 0
				AND song_bitrate_mode = ''";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (int) $row['quanti'] : 0;
	}

	/**
	 * Rilegge i dati tecnici dai file, a blocchi.
	 *
	 * L'analisi di getID3 apre e legge ogni file: su una libreria
	 * grande farlo tutto in una volta supererebbe il tempo massimo di
	 * esecuzione a meta' strada. Si procede a blocchi e si dice quanti
	 * ne restano.
	 *
	 * @param int $limite quanti brani per volta
	 * @return array quanti aggiornati, quanti saltati, quanti restano
	 */
	public function fill_quality($limite = 25)
	{
		$esito = array('updated' => 0, 'skipped' => 0, 'left' => 0);

		if ($this->metadata_extractor === null || $this->storage_helper === null)
		{
			return $esito;
		}

		$limite = max(1, min(200, (int) $limite));

		$sql = 'SELECT song_id, file_path FROM ' . $this->table_prefix . "musicshare_songs
			WHERE song_bitrate = 0
				AND song_bitrate_mode = ''
			ORDER BY song_id ASC";
		$result = $this->db->sql_query_limit($sql, $limite);
		$brani = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		foreach ($brani as $brano)
		{
			$percorso = $this->storage_helper->get_song_file($brano);

			// file mancante: si salta, non e' questo lo strumento che
			// ripulisce i riferimenti rotti
			if (empty($brano['file_path']) || !is_file($percorso))
			{
				$this->mark_unreadable((int) $brano['song_id']);
				$esito['skipped']++;

				continue;
			}

			$meta = $this->metadata_extractor->extract($percorso);

			// un file senza bitrate leggibile resterebbe a zero e
			// tornerebbe nel blocco successivo all'infinito: si salta
			if (empty($meta['bitrate']))
			{
				$this->mark_unreadable((int) $brano['song_id']);
				$esito['skipped']++;

				continue;
			}

			$this->db->sql_query('UPDATE ' . $this->table_prefix . 'musicshare_songs
				SET ' . $this->db->sql_build_array('UPDATE', array(
					'song_bitrate'		=> (int) $meta['bitrate'],
					'song_bitrate_mode'	=> (string) $meta['bitrate_mode'],
					'song_samplerate'	=> (int) $meta['samplerate'],
					'song_channels'		=> (int) $meta['channels'],
				)) . '
				WHERE song_id = ' . (int) $brano['song_id']);

			$esito['updated']++;
		}

		$esito['left'] = $this->count_missing_quality();

		return $esito;
	}

	/**
	 * Marca un brano da cui i dati tecnici non si ricavano.
	 *
	 * Senza questo segno resterebbe con bitrate a zero e verrebbe
	 * ripescato a ogni blocco: venticinque file illeggibili in testa
	 * all'elenco bloccherebbero tutti gli altri per sempre.
	 *
	 * Il valore non compare da nessuna parte, perche' la riga dei dati
	 * tecnici si mostra solo quando c'e' un bitrate.
	 *
	 * @param int $song_id
	 * @return void
	 */
	protected function mark_unreadable($song_id)
	{
		$this->db->sql_query('UPDATE ' . $this->table_prefix . "musicshare_songs
			SET song_bitrate_mode = 'n/a'
			WHERE song_id = " . (int) $song_id);
	}

	public function get_totals()
	{
		$p = $this->table_prefix;
		$out = array();

		// brani, stato e contatori complessivi
		$sql = 'SELECT COUNT(*) AS brani,
				SUM(CASE WHEN song_approved = 1 THEN 1 ELSE 0 END) AS approvati,
				SUM(CASE WHEN topic_id > 0 THEN 1 ELSE 0 END) AS con_argomento,
				SUM(play_count) AS ascolti,
				SUM(download_count) AS download,
				SUM(song_likes) AS mi_piace,
				SUM(song_dislikes) AS non_mi_piace,
				SUM(file_size) AS spazio
			FROM ' . $p . 'musicshare_songs';
		$result = $this->db->sql_query($sql);
		$riga = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$out['songs'] = (int) $riga['brani'];
		$out['approved'] = (int) $riga['approvati'];
		$out['pending'] = $out['songs'] - $out['approved'];
		$out['with_topic'] = (int) $riga['con_argomento'];
		$out['plays'] = (int) $riga['ascolti'];
		$out['downloads'] = (int) $riga['download'];
		$out['likes'] = (int) $riga['mi_piace'];
		$out['dislikes'] = (int) $riga['non_mi_piace'];
		$out['size'] = (float) $riga['spazio'];

		// righe delle tabelle di supporto
		foreach (array(
			'votes'			=> 'musicshare_votes',
			'plays_log'		=> 'musicshare_plays',
			'downloads_log'	=> 'musicshare_downloads',
			'playlists'		=> 'musicshare_playlists',
			'genres'		=> 'musicshare_genres',
			'follows'		=> 'musicshare_follows',
		) as $chiave => $tabella)
		{
			$out[$chiave] = $this->count_rows($p . $tabella);
		}

		// quanti utenti diversi hanno caricato almeno un brano
		$sql = 'SELECT COUNT(DISTINCT user_id) AS quanti FROM ' . $p . 'musicshare_songs';
		$result = $this->db->sql_query($sql);
		$riga = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$out['uploaders'] = (int) $riga['quanti'];

		return $out;
	}

	/**
	 * Righe di una tabella, zero se la tabella non esiste.
	 *
	 * @param string $tabella
	 * @return int
	 */
	protected function count_rows($tabella)
	{
		$tools = new \phpbb\db\tools\tools($this->db);

		if (!$tools->sql_table_exists($tabella))
		{
			return 0;
		}

		$result = $this->db->sql_query('SELECT COUNT(*) AS quanti FROM ' . $tabella);
		$riga = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $riga['quanti'];
	}

	/**
	 * Quanti brani hanno un contatore dei voti diverso dai voti reali.
	 *
	 * @return int
	 */
	public function count_wrong_votes()
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . 'musicshare_songs s
			WHERE s.song_likes <> (
					SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_votes v
					WHERE v.song_id = s.song_id AND v.vote = 1
				)
				OR s.song_dislikes <> (
					SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_votes v
					WHERE v.song_id = s.song_id AND v.vote = -1
				)';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quanti'];
	}

	/**
	 * Rifa' i conti di mi piace e non mi piace dalla tabella dei voti.
	 *
	 * @return int brani corretti
	 */
	public function fix_votes()
	{
		$da_correggere = $this->count_wrong_votes();

		$sql = 'UPDATE ' . $this->table_prefix . 'musicshare_songs s
			SET s.song_likes = (
					SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_votes v
					WHERE v.song_id = s.song_id AND v.vote = 1
				),
				s.song_dislikes = (
					SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_votes v
					WHERE v.song_id = s.song_id AND v.vote = -1
				)';
		$this->db->sql_query($sql);

		return $da_correggere;
	}

	/**
	 * Quanti brani hanno il contatore dei download diverso dal registro.
	 *
	 * @return int
	 */
	public function count_wrong_downloads()
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . 'musicshare_songs s
			WHERE s.download_count <> (
				SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_downloads d
				WHERE d.song_id = s.song_id
			)';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quanti'];
	}

	/**
	 * Rifa' i conti dei download dal registro.
	 *
	 * @return int
	 */
	public function fix_downloads()
	{
		$da_correggere = $this->count_wrong_downloads();

		$sql = 'UPDATE ' . $this->table_prefix . 'musicshare_songs s
			SET s.download_count = (
				SELECT COUNT(*) FROM ' . $this->table_prefix . 'musicshare_downloads d
				WHERE d.song_id = s.song_id
			)';
		$this->db->sql_query($sql);

		return $da_correggere;
	}

	/**
	 * Brani collegati a un argomento inesistente o nascosto.
	 *
	 * @return int
	 */
	public function count_stale_topics()
	{
		$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . 'musicshare_songs s
			WHERE s.topic_id > 0
				AND NOT EXISTS (
					SELECT 1 FROM ' . TOPICS_TABLE . ' t
					WHERE t.topic_id = s.topic_id
						AND t.topic_visibility = ' . ITEM_APPROVED . '
				)';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (int) $row['quanti'];
	}

	/**
	 * Slega i brani dagli argomenti che non ci sono piu'.
	 *
	 * @return int
	 */
	public function fix_stale_topics()
	{
		$da_correggere = $this->count_stale_topics();

		$sql = 'UPDATE ' . $this->table_prefix . 'musicshare_songs s
			SET s.topic_id = 0, s.post_id = 0
			WHERE s.topic_id > 0
				AND NOT EXISTS (
					SELECT 1 FROM ' . TOPICS_TABLE . ' t
					WHERE t.topic_id = s.topic_id
						AND t.topic_visibility = ' . ITEM_APPROVED . '
				)';
		$this->db->sql_query($sql);

		return $da_correggere;
	}

	/**
	 * Righe che puntano a brani non piu' esistenti.
	 *
	 * @return array tabella => quante
	 */
	public function count_orphan_rows()
	{
		$out = array();

		foreach (array('musicshare_votes', 'musicshare_playlist_songs', 'musicshare_song_genre') as $t)
		{
			$sql = 'SELECT COUNT(*) AS quanti FROM ' . $this->table_prefix . $t . ' x
				WHERE NOT EXISTS (
					SELECT 1 FROM ' . $this->table_prefix . 'musicshare_songs s
					WHERE s.song_id = x.song_id
				)';
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$out[$t] = (int) $row['quanti'];
		}

		return $out;
	}

	/**
	 * Rimuove le righe riferite a brani non piu' esistenti.
	 *
	 * @return int righe rimosse
	 */
	public function fix_orphan_rows()
	{
		$rimosse = 0;

		foreach (array('musicshare_votes', 'musicshare_playlist_songs', 'musicshare_song_genre',
			'musicshare_plays', 'musicshare_downloads') as $t)
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . $t . '
				WHERE NOT EXISTS (
					SELECT 1 FROM ' . $this->table_prefix . 'musicshare_songs s
					WHERE s.song_id = ' . $this->table_prefix . $t . '.song_id
				)';
			$this->db->sql_query($sql);
			$rimosse += (int) $this->db->sql_affectedrows();
		}

		return $rimosse;
	}
}

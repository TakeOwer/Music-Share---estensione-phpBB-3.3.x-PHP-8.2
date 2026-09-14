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

use salvocortesiano\musicshare\repository\wall_repository;

/**
 * Bacheca dell'autore.
 *
 * I commenti restano sulla pagina dell'autore e stanno in una tabella
 * propria. Non sono messaggi del forum: per discutere di un brano c'e'
 * gia' il suo argomento, e portare fuori dalla pagina chi vuole
 * scrivere due righe all'autore era una strada in piu' verso la stessa
 * cosa.
 *
 * Il testo passa comunque dal formattatore di phpBB, cosi' BBCode,
 * faccine, collegamenti e censura delle parole funzionano come
 * ovunque sul forum.
 */
class wall_manager
{
	/** Lunghezza massima di un commento */
	const MAX_LENGTH = 3000;

	protected $config;
	protected $user;
	protected $auth;
	protected $wall_repository;
	protected $notification_manager;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		wall_repository $wall_repository,
		\phpbb\notification\manager $notification_manager
	)
	{
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->wall_repository = $wall_repository;
		$this->notification_manager = $notification_manager;
	}

	/**
	 * @return bool
	 */
	public function is_enabled()
	{
		return !empty($this->config['musicshare_wall_enabled']);
	}

	/**
	 * Quanti commenti mostrare per pagina.
	 *
	 * @return int
	 */
	public function get_preview_count()
	{
		$quanti = isset($this->config['musicshare_wall_preview'])
			? (int) $this->config['musicshare_wall_preview'] : 5;

		return max(1, min(20, $quanti));
	}

	/**
	 * Cosa puo' fare chi guarda, su questa bacheca.
	 *
	 * Un unico metodo perche' la pagina e il salvataggio devono
	 * rispondere allo stesso modo: se la pagina mostra il modulo e poi
	 * il salvataggio rifiuta, l'utente perde quello che ha scritto.
	 *
	 * @param int $wall_user_id
	 * @param bool $segue chi guarda segue gia' l'autore
	 * @return array
	 */
	public function get_context($wall_user_id, $segue = false)
	{
		$wall_user_id = (int) $wall_user_id;
		$utente = (int) $this->user->data['user_id'];

		$stato = array(
			'enabled'		=> $this->is_enabled(),
			'wall_user_id'	=> $wall_user_id,
			'is_owner'		=> ($utente === $wall_user_id && $utente !== ANONYMOUS),
			'can_comment'	=> false,
			'can_moderate'	=> false,
			'motivo'		=> '',
		);

		if (!$stato['enabled'])
		{
			return $stato;
		}

		// lo staff modera sempre; l'autore a casa propria solo se
		// l'amministratore ha acceso l'opzione, perche' chi cancella i
		// commenti che riceve mostra solo quelli che gli fanno comodo
		$stato['can_moderate'] = (bool) $this->auth->acl_get('m_musicshare_wall')
			|| ($stato['is_owner'] && !empty($this->config['musicshare_wall_author_moderates']));

		if ($utente === ANONYMOUS)
		{
			$stato['motivo'] = 'MUSICSHARE_WALL_LOGIN';

			return $stato;
		}

		if (!$this->auth->acl_get('u_musicshare_wall_post'))
		{
			$stato['motivo'] = 'MUSICSHARE_WALL_NO_PERMISSION';

			return $stato;
		}

		// il vincolo "solo chi segue" non vale per l'autore: deve poter
		// rispondere a casa propria senza seguire se stesso
		if (!empty($this->config['musicshare_wall_follow_only'])
			&& !$stato['is_owner']
			&& !$segue)
		{
			$stato['motivo'] = 'MUSICSHARE_WALL_FOLLOW_FIRST';

			return $stato;
		}

		$stato['can_comment'] = true;

		return $stato;
	}

	/**
	 * Chi guarda puo' modificare o cancellare questo commento?
	 *
	 * @param array $commento riga del commento
	 * @param array $stato quello di get_context()
	 * @return bool
	 */
	public function can_manage(array $commento, array $stato)
	{
		if (empty($stato['enabled']))
		{
			return false;
		}

		if (!empty($stato['can_moderate']))
		{
			return true;
		}

		$utente = (int) $this->user->data['user_id'];

		return $utente !== ANONYMOUS
			&& (int) $commento['user_id'] === $utente
			&& $this->auth->acl_get('u_musicshare_wall_edit');
	}

	/**
	 * Scrive un commento o una risposta.
	 *
	 * @param int $wall_user_id
	 * @param int $parent_id 0 per un commento, altrimenti il commento
	 *                       a cui si risponde
	 * @param string $testo
	 * @param array $stato quello di get_context()
	 * @return int identificativo del commento, 0 se rifiutato
	 */
	public function add($wall_user_id, $parent_id, $testo, array $stato)
	{
		$wall_user_id = (int) $wall_user_id;
		$parent_id = (int) $parent_id;
		$testo = trim((string) $testo);

		if ($testo === '' || empty($stato['can_comment']))
		{
			return 0;
		}

		$testo = utf8_substr($testo, 0, self::MAX_LENGTH);

		if ($parent_id > 0)
		{
			$padre = $this->wall_repository->get_comment($parent_id);

			// si risponde solo a un commento di primo livello della
			// stessa bacheca: un solo livello di rientro, e nessuna
			// risposta dirottata su una bacheca altrui
			if (!$padre
				|| (int) $padre['parent_id'] !== 0
				|| (int) $padre['wall_user_id'] !== $wall_user_id)
			{
				return 0;
			}
		}

		$dati = $this->prepare_text($testo);
		$dati['wall_user_id'] = $wall_user_id;
		$dati['parent_id'] = $parent_id;
		$dati['user_id'] = (int) $this->user->data['user_id'];
		$dati['comment_time'] = time();
		$dati['edit_time'] = 0;
		$dati['edit_user'] = 0;

		$comment_id = $this->wall_repository->add($dati);

		if ($comment_id)
		{
			$this->notify($wall_user_id, $comment_id, $parent_id, isset($padre) ? $padre : null);
		}

		return $comment_id;
	}

	/**
	 * Modifica il testo di un commento.
	 *
	 * @param array $commento riga esistente
	 * @param string $testo
	 * @return bool
	 */
	public function edit(array $commento, $testo)
	{
		$testo = trim((string) $testo);

		if ($testo === '')
		{
			return false;
		}

		$dati = $this->prepare_text(utf8_substr($testo, 0, self::MAX_LENGTH));
		$dati['edit_time'] = time();
		$dati['edit_user'] = (int) $this->user->data['user_id'];

		$this->wall_repository->update((int) $commento['comment_id'], $dati);

		return true;
	}

	/**
	 * Cancella un commento e le sue eventuali risposte.
	 *
	 * @param array $commento
	 * @return bool
	 */
	public function remove(array $commento)
	{
		return $this->wall_repository->delete((int) $commento['comment_id']) > 0;
	}

	/**
	 * Prepara testo, identificativo e maschera del BBCode per il
	 * salvataggio.
	 *
	 * @param string $testo
	 * @return array
	 */
	protected function prepare_text($testo)
	{
		$uid = $bitfield = $options = '';
		generate_text_for_storage($testo, $uid, $bitfield, $options, true, true, true);

		return array(
			'comment_text'		=> $testo,
			'bbcode_uid'		=> $uid,
			'bbcode_bitfield'	=> $bitfield,
			'bbcode_options'	=> (int) $options,
		);
	}

	/**
	 * Testo pronto da mostrare.
	 *
	 * @param array $riga
	 * @return string
	 */
	public function render_text(array $riga)
	{
		return generate_text_for_display(
			$riga['comment_text'],
			$riga['bbcode_uid'],
			$riga['bbcode_bitfield'],
			(int) $riga['bbcode_options']
		);
	}

	/**
	 * Testo così come e' stato scritto, per riempire il modulo di
	 * modifica.
	 *
	 * @param array $riga
	 * @return string
	 */
	public function render_edit(array $riga)
	{
		// generate_text_for_edit() restituisce un array con testo e
		// permessi, non una stringa: qui serve solo il testo
		$dati = generate_text_for_edit(
			$riga['comment_text'],
			$riga['bbcode_uid'],
			(int) $riga['bbcode_options']
		);

		return isset($dati['text']) ? $dati['text'] : '';
	}

	/**
	 * Avvisi: al padrone di casa quando riceve un commento, a chi ha
	 * scritto quando riceve una risposta.
	 *
	 * Un errore qui non deve far fallire il commento, che a questo
	 * punto e' gia' salvato.
	 *
	 * @param int $wall_user_id
	 * @param int $comment_id
	 * @param int $parent_id
	 * @param array|null $padre
	 * @return void
	 */
	protected function notify($wall_user_id, $comment_id, $parent_id, $padre)
	{
		$utente = (int) $this->user->data['user_id'];

		$base = array(
			'wall_user_id'		=> (int) $wall_user_id,
			'comment_id'		=> (int) $comment_id,
			'commenter_id'		=> $utente,
			'commenter_name'	=> (string) $this->user->data['username'],
		);

		try
		{
			if ($parent_id > 0 && $padre)
			{
				$destinatario = (int) $padre['user_id'];

				if ($destinatario !== $utente)
				{
					$this->notification_manager->add_notifications(
						'salvocortesiano.musicshare.notification.type.wall_reply',
						array_merge($base, array('target_id' => $destinatario))
					);
				}

				// il padrone di casa viene avvisato solo se non e' gia'
				// stato avvisato come destinatario della risposta
				if ((int) $wall_user_id !== $utente && (int) $wall_user_id !== $destinatario)
				{
					$this->notification_manager->add_notifications(
						'salvocortesiano.musicshare.notification.type.wall_comment',
						$base
					);
				}

				return;
			}

			if ((int) $wall_user_id !== $utente)
			{
				$this->notification_manager->add_notifications(
					'salvocortesiano.musicshare.notification.type.wall_comment',
					$base
				);
			}
		}
		catch (\Exception $e)
		{
			// il commento resta scritto anche se l'avviso non parte
		}
	}
}

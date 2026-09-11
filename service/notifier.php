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
 * Invio delle notifiche e dei messaggi privati legati ai brani.
 * Raccolto in un solo servizio così ACP e caricamento usano la stessa
 * logica e i due canali restano allineati.
 */
class notifier
{
	protected $config;
	protected $user;
	protected $notification_manager;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\notification\manager $notification_manager,
		$root_path,
		$php_ext
	)
	{
		$this->config = $config;
		$this->user = $user;
		$this->notification_manager = $notification_manager;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Brano approvato: notifica all'autore, più messaggio privato se
	 * l'amministratore lo ha richiesto.
	 *
	 * @param array $song riga del brano
	 * @return void
	 */
	public function song_approved(array $song)
	{
		$data = $this->build_data($song);

		$this->notification_manager->add_notifications(
			'salvocortesiano.musicshare.notification.type.song_approved',
			$data
		);

		$this->send_pm(
			(int) $song['user_id'],
			'MUSICSHARE_PM_APPROVED_SUBJECT',
			'MUSICSHARE_PM_APPROVED_BODY',
			$song['song_title']
		);
	}

	/**
	 * Brano rifiutato o rimosso dal moderatore.
	 *
	 * @param array $song
	 * @param string $reason motivazione facoltativa
	 * @return void
	 */
	public function song_rejected(array $song, $reason = '')
	{
		$data = $this->build_data($song);

		$this->notification_manager->add_notifications(
			'salvocortesiano.musicshare.notification.type.song_rejected',
			$data
		);

		$this->send_pm(
			(int) $song['user_id'],
			'MUSICSHARE_PM_REJECTED_SUBJECT',
			(trim($reason) !== '') ? 'MUSICSHARE_PM_REJECTED_BODY_REASON' : 'MUSICSHARE_PM_REJECTED_BODY',
			$song['song_title'],
			trim($reason)
		);
	}

	/**
	 * Nuovo brano caricato: notifica agli altri utenti abilitati.
	 *
	 * @param array $song
	 * @return void
	 */
	public function song_new(array $song)
	{
		$this->notification_manager->add_notifications(
			'salvocortesiano.musicshare.notification.type.song_new',
			$this->build_data($song)
		);
	}

	/**
	 * Rimuove le notifiche pendenti di un brano eliminato, per non
	 * lasciare voci che rimandano a qualcosa che non esiste più.
	 *
	 * @param int $song_id
	 * @return void
	 */
	public function purge_song_notifications($song_id)
	{
		foreach (array('song_approved', 'song_new') as $type)
		{
			$this->notification_manager->delete_notifications(
				'salvocortesiano.musicshare.notification.type.' . $type,
				(int) $song_id
			);
		}
	}

	/**
	 * Dati passati alle notifiche.
	 *
	 * @param array $song
	 * @return array
	 */
	protected function build_data(array $song)
	{
		// Il nome dell'autore non è sempre presente: get_song() legge la
		// sola tabella dei brani. Chi mostra la notifica lo ricava
		// comunque dall'identificativo, quindi qui è solo un di più.
		return array(
			'song_id'		=> (int) $song['song_id'],
			'song_title'	=> (string) $song['song_title'],
			// artista e album servono alla seconda riga delle notifiche
			// push: senza, resterebbe vuota
			'song_artist'	=> isset($song['song_artist']) ? (string) $song['song_artist'] : '',
			'song_album'	=> isset($song['song_album']) ? (string) $song['song_album'] : '',
			'uploader_id'	=> (int) $song['user_id'],
			'uploader_name'	=> isset($song['username']) ? (string) $song['username'] : '',
		);
	}

	/**
	 * Invia un messaggio privato dal sistema all'autore del brano.
	 *
	 * @param int $user_id
	 * @param string $subject_key
	 * @param string $body_key
	 * @param string $song_title
	 * @param string $reason
	 * @return void
	 */
	protected function send_pm($user_id, $subject_key, $body_key, $song_title, $reason = '')
	{
		if (empty($this->config['musicshare_notify_pm']) || $user_id <= 0)
		{
			return;
		}

		if (!function_exists('submit_pm'))
		{
			include($this->root_path . 'includes/functions_privmsgs.' . $this->php_ext);
		}

		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}

		$body = $this->user->lang($body_key, $song_title, $reason);

		$message_parser = new \parse_message();
		$message_parser->message = $body;
		$message_parser->parse(true, true, true, false, false, true, true);

		$pm_data = array(
			'from_user_id'		=> (int) $this->user->data['user_id'],
			'from_user_ip'		=> (string) $this->user->ip,
			'from_username'		=> (string) $this->user->data['username'],
			'enable_sig'		=> false,
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'icon_id'			=> 0,
			'bbcode_bitfield'	=> $message_parser->bbcode_bitfield,
			'bbcode_uid'		=> $message_parser->bbcode_uid,
			'message'			=> $message_parser->message,
			'address_list'		=> array('u' => array($user_id => 'to')),
		);

		submit_pm('post', $this->user->lang($subject_key), $pm_data, false);
	}
}

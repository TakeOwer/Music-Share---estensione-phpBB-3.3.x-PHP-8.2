<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\cron;

/**
 * Pulizia periodica delle notifiche già lette dell'estensione.
 *
 * Gira una volta al giorno tramite il sistema di attività pianificate di
 * phpBB, che le esegue in coda alla visita di una pagina: non serve un
 * cron di sistema.
 */
class notification_cleanup extends \phpbb\cron\task\base
{
	/** Intervallo fra due esecuzioni, in secondi. */
	const INTERVALLO = 86400;

	protected $config;
	protected $cleaner;
	protected $song_repository;

	public function __construct(
		\phpbb\config\config $config,
		\salvocortesiano\musicshare\service\notification_cleaner $cleaner,
		\salvocortesiano\musicshare\repository\song_repository $song_repository
	)
	{
		$this->config = $config;
		$this->cleaner = $cleaner;
		$this->song_repository = $song_repository;
	}

	/**
	 * L'attività ha senso solo se la pulizia è attiva.
	 */
	public function is_runnable()
	{
		return !empty($this->config['musicshare_cleanup_enabled']);
	}

	/**
	 * Una volta al giorno.
	 */
	public function should_run()
	{
		$ultima = isset($this->config['musicshare_cleanup_last'])
			? (int) $this->config['musicshare_cleanup_last']
			: 0;

		return ($ultima < time() - self::INTERVALLO);
	}

	public function run()
	{
		// Si limita il numero di blocchi: l'attività gira in coda alla
		// visita di un utente, non deve allungarne l'attesa. Quel che
		// resta viene ripreso al ciclo successivo.
		$this->cleaner->clean(null, 20);

		// Anche il registro degli ascolti va contenuto: serve alle
		// classifiche a periodo, quindi oltre la finestra piu' lunga
		// diventa solo peso. Il contatore complessivo del brano non
		// viene toccato.
		$giorni = isset($this->config['musicshare_plays_keep_days'])
			? (int) $this->config['musicshare_plays_keep_days']
			: 90;

		if ($giorni > 0)
		{
			$this->song_repository->purge_old_plays($giorni, 10);
			$this->song_repository->purge_old_downloads($giorni, 10);
		}

		$this->config->set('musicshare_cleanup_last', time());
	}
}

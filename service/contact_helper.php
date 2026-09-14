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
 * Collegamento per scrivere un messaggio privato a un utente.
 *
 * Le regole sono le stesse ovunque - pagina dell'autore, elenchi,
 * righe dei brani, moderazione - quindi vivono in un posto solo: se
 * domani cambiano, cambiano per tutti insieme.
 */
class contact_helper
{
	protected $config;
	protected $user;
	protected $auth;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		$root_path,
		$php_ext
	)
	{
		$this->config = $config;
		$this->user = $user;
		$this->auth = $auth;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Chi guarda puo' scrivere messaggi privati a qualcuno?
	 *
	 * Controlla una volta sola le condizioni che non dipendono dal
	 * destinatario: utile per non ripeterle a ogni riga di un elenco.
	 *
	 * @return bool
	 */
	public function can_send()
	{
		return (int) $this->user->data['user_id'] !== ANONYMOUS
			&& !empty($this->config['allow_privmsg'])
			&& $this->auth->acl_get('u_sendpm');
	}

	/**
	 * Indirizzo per scrivere al destinatario indicato.
	 *
	 * Restituisce una stringa vuota - quindi nessun pulsante - se i
	 * messaggi privati sono spenti sul forum, se chi guarda non puo'
	 * scriverne, se il destinatario non li accetta, o se e' se stesso.
	 *
	 * @param int $destinatario
	 * @param mixed $accetta_pm preferenza del destinatario, null se ignota
	 * @param int $song_id brano di cui si vuole parlare, per il titolo
	 * @return string
	 */
	public function get_pm_url($destinatario, $accetta_pm = null, $song_id = 0)
	{
		$destinatario = (int) $destinatario;

		if ($destinatario <= 0 || $destinatario === (int) $this->user->data['user_id'])
		{
			return '';
		}

		if (!$this->can_send())
		{
			return '';
		}

		if ($accetta_pm !== null && !$accetta_pm)
		{
			return '';
		}

		$parametri = 'i=pm&amp;mode=compose&amp;u=' . $destinatario;

		// il brano serve solo a precompilare il titolo del messaggio
		if ((int) $song_id > 0)
		{
			$parametri .= '&amp;musicshare_song=' . (int) $song_id;
		}

		return append_sid($this->root_path . 'ucp.' . $this->php_ext, $parametri);
	}
}

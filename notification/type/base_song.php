<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\notification\type;

/**
 * Parte comune alle notifiche dell'estensione: i tre tipi differiscono
 * solo per destinatari, testo e indirizzo, quindi tutto il resto sta qui.
 */
abstract class base_song extends \phpbb\notification\type\base
{
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user_loader */
	protected $user_loader;

	/**
	 * Iniettati dal contenitore: la classe base delle notifiche di phpBB
	 * non li prevede, quindi si passano con delle chiamate dedicate.
	 *
	 * @param \phpbb\controller\helper $helper
	 * @return void
	 */
	public function set_controller_helper(\phpbb\controller\helper $helper)
	{
		$this->helper = $helper;
	}

	/**
	 * @param \phpbb\user_loader $user_loader
	 * @return void
	 */
	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	/**
	 * L'elemento notificato è il brano.
	 */
	public static function get_item_id($data)
	{
		return (int) $data['song_id'];
	}

	public static function get_item_parent_id($data)
	{
		return 0;
	}

	public function is_available()
	{
		return $this->auth->acl_get('u_musicshare_view');
	}

	public function users_to_query()
	{
		return array((int) $this->get_data('uploader_id'));
	}

	public function get_style_class()
	{
		return 'notification-musicshare';
	}

	public function get_url()
	{
		if ($this->helper === null)
		{
			return '';
		}

		return $this->helper->route(
			'salvocortesiano_musicshare_user',
			array('user_id' => (int) $this->get_data('uploader_id'))
		);
	}

	public function get_redirect_url()
	{
		return $this->get_url();
	}

	public function get_email_template_variables()
	{
		return array(
			'SONG_TITLE'	=> $this->get_data('song_title'),
			'U_SONGS'		=> $this->get_url(),
		);
	}

	/**
	 * Dati salvati con la notifica: bastano a comporre testo e indirizzo
	 * anche se il brano nel frattempo viene eliminato.
	 */
	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('song_title', $data['song_title']);
		$this->set_data('song_artist', isset($data['song_artist']) ? $data['song_artist'] : '');
		$this->set_data('song_album', isset($data['song_album']) ? $data['song_album'] : '');
		$this->set_data('uploader_id', (int) $data['uploader_id']);
		$this->set_data('uploader_name', $data['uploader_name']);

		parent::create_insert_array($data, $pre_create_data);
	}

	/**
	 * Seconda riga della notifica, usata dalle notifiche push del browser.
	 *
	 * phpBB la fornisce vuota per impostazione predefinita: qui si mostra
	 * artista e album, che sono l'informazione utile accanto al titolo.
	 *
	 * Le notifiche create prima di questa aggiunta non hanno quei dati:
	 * in quel caso si restituisce una stringa vuota, che è quanto
	 * accadeva anche prima.
	 *
	 * @return string
	 */
	public function get_reference()
	{
		$artista = (string) $this->get_data('song_artist');
		$album = (string) $this->get_data('song_album');

		if ($artista === '' && $album === '')
		{
			return '';
		}

		if ($artista !== '' && $album !== '')
		{
			return $artista . " \u{2014} " . $album;
		}

		return ($artista !== '') ? $artista : $album;
	}
}

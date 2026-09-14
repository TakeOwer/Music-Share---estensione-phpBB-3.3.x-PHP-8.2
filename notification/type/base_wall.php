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
 * Parte comune degli avvisi della bacheca.
 *
 * Cambiano solo il destinatario e la frase: uno avvisa il padrone di
 * casa che ha ricevuto un commento, l'altro avvisa chi ha scritto che
 * gli hanno risposto.
 */
abstract class base_wall extends \phpbb\notification\type\base
{
	/** @var \phpbb\controller\helper */
	protected $helper = null;

	/** @var \phpbb\user_loader */
	protected $user_loader = null;

	/**
	 * Iniettati dal contenitore: la classe base delle notifiche di
	 * phpBB non li prevede nel costruttore.
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
	 * L'elemento e' il commento, il contenitore la bacheca.
	 */
	public static function get_item_id($data)
	{
		return (int) $data['comment_id'];
	}

	public static function get_item_parent_id($data)
	{
		return (int) $data['wall_user_id'];
	}

	public function is_available()
	{
		return $this->auth->acl_get('u_musicshare_view');
	}

	public function users_to_query()
	{
		return array((int) $this->get_data('commenter_id'));
	}

	public function get_avatar()
	{
		if ($this->user_loader === null)
		{
			return '';
		}

		return $this->user_loader->get_avatar((int) $this->get_data('commenter_id'), false, true);
	}

	public function get_style_class()
	{
		return 'notification-musicshare';
	}

	/**
	 * Porta al commento sulla pagina dell'autore, non a una pagina
	 * qualsiasi: chi riceve l'avviso vuole leggere quel commento.
	 */
	public function get_url()
	{
		if ($this->helper === null)
		{
			return '';
		}

		return $this->helper->route('salvocortesiano_musicshare_user', array(
			'user_id' => (int) $this->get_data('wall_user_id'),
		)) . '#musicshare-comment-' . (int) $this->item_id;
	}

	public function get_redirect_url()
	{
		return $this->get_url();
	}

	/**
	 * Nessun messaggio di posta: l'avviso della campanella basta, e i
	 * commenti possono essere frequenti.
	 */
	public function get_email_template()
	{
		return false;
	}

	public function get_email_template_variables()
	{
		return array();
	}

	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('wall_user_id', (int) $data['wall_user_id']);
		$this->set_data('commenter_id', (int) $data['commenter_id']);
		$this->set_data('commenter_name', (string) $data['commenter_name']);

		parent::create_insert_array($data, $pre_create_data);
	}

	/**
	 * Nome di chi ha scritto, preso dal caricatore utenti quando c'e'
	 * e dal dato salvato quando manca.
	 *
	 * @return string
	 */
	protected function commenter_name()
	{
		if ($this->user_loader !== null)
		{
			return $this->user_loader->get_username((int) $this->get_data('commenter_id'), 'no_profile');
		}

		return (string) $this->get_data('commenter_name');
	}

	/**
	 * Destinatari possibili, filtrati dalle preferenze di ciascuno.
	 *
	 * @param int $destinatario
	 * @param int $mittente
	 * @param array $options
	 * @return array
	 */
	protected function single_user($destinatario, $mittente, $options)
	{
		$destinatario = (int) $destinatario;

		if ($destinatario <= 0 || $destinatario === ANONYMOUS || $destinatario === (int) $mittente)
		{
			return array();
		}

		$options = array_merge(array('ignore_users' => array()), $options);

		return $this->check_user_notification_options(array($destinatario), $options);
	}
}

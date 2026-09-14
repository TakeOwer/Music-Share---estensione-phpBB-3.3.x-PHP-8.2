<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare\migrations;

/**
 * La bacheca diventa un muro sulla pagina dell'autore.
 *
 * Prima ogni bacheca era un argomento del forum e la tabella teneva
 * solo il legame autore/argomento. Era una doppia via verso la stessa
 * cosa: la discussione di un brano esiste gia', e il pulsante Bacheca
 * portava fuori dalla pagina invece di far commentare dove si sta
 * guardando.
 *
 * Ora i commenti stanno in una tabella propria e restano sulla pagina.
 *
 * Gli argomenti gia' aperti sul forum NON vengono toccati: sono
 * messaggi veri degli utenti, qui si toglie solo il legame.
 */
class rework_walls extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_wall_notification'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_wall_posts'	=> [
					'COLUMNS'	=> [
						'comment_id'		=> ['UINT', null, 'auto_increment'],
						// proprietario della bacheca
						'wall_user_id'		=> ['UINT', 0],
						// 0 per un commento, altrimenti il commento a cui
						// risponde: un solo livello di rientro
						'parent_id'			=> ['UINT', 0],
						// chi ha scritto
						'user_id'			=> ['UINT', 0],
						'comment_text'		=> ['MTEXT_UNI', ''],
						'bbcode_uid'		=> ['VCHAR:8', ''],
						'bbcode_bitfield'	=> ['VCHAR:255', ''],
						'bbcode_options'	=> ['UINT:11', 7],
						'comment_time'		=> ['TIMESTAMP', 0],
						'edit_time'			=> ['TIMESTAMP', 0],
						'edit_user'			=> ['UINT', 0],
					],
					'PRIMARY_KEY'	=> 'comment_id',
					'KEYS'			=> [
						// elenco di una bacheca, dal piu' recente
						'wall_time'		=> ['INDEX', ['wall_user_id', 'comment_time']],
						// risposte di un commento
						'parent_id'		=> ['INDEX', 'parent_id'],
						'user_id'		=> ['INDEX', 'user_id'],
					],
				],
			],
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_walls',
			],
		];
	}

	public function revert_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_walls'	=> [
					'COLUMNS'	=> [
						'user_id'		=> ['UINT', 0],
						'topic_id'		=> ['UINT', 0],
						'created_time'	=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'user_id',
					'KEYS'			=> [
						'topic_id'	=> ['INDEX', 'topic_id'],
					],
				],
			],
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_wall_posts',
			],
		];
	}

	public function update_data()
	{
		return [
			// non serve piu' nessuna sezione del forum
			['config.remove', ['musicshare_wall_forum']],
			['config_text.remove', ['musicshare_wall_title']],

			// l'autore modera la propria bacheca: spento di partenza.
			// Chi puo' cancellare i commenti che riceve mostra solo
			// quelli che gli fanno comodo, e nessuno se ne accorge.
			['config.add', ['musicshare_wall_author_moderates', 0]],

			['permission.add', ['u_musicshare_wall_post']],
			['permission.add', ['u_musicshare_wall_edit']],
			['permission.add', ['m_musicshare_wall']],

			// scrivere e gestire i propri commenti: a chi puo' gia'
			// vedere la sezione, cioe' i registrati
			['permission.permission_set', ['REGISTERED', 'u_musicshare_wall_post', 'group']],
			['permission.permission_set', ['REGISTERED', 'u_musicshare_wall_edit', 'group']],

			// moderare i commenti di tutti: solo staff
			['permission.permission_set', ['ADMINISTRATORS', 'm_musicshare_wall', 'group']],
			['permission.permission_set', ['GLOBAL_MODERATORS', 'm_musicshare_wall', 'group']],

			['custom', [[$this, 'enable_notifications']]],
		];
	}

	public function revert_data()
	{
		return [
			['config.add', ['musicshare_wall_forum', 0]],
			['config_text.add', ['musicshare_wall_title', 'Bacheca di %1$s']],
			['config.remove', ['musicshare_wall_author_moderates']],
			['permission.remove', ['u_musicshare_wall_post']],
			['permission.remove', ['u_musicshare_wall_edit']],
			['permission.remove', ['m_musicshare_wall']],
		];
	}

	/**
	 * @return void
	 */
	public function enable_notifications()
	{
		global $phpbb_container;

		$phpbb_container->get('notification_manager')
			->enable_notifications('salvocortesiano.musicshare.notification.type.wall_reply');
	}
}

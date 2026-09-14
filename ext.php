<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace salvocortesiano\musicshare;

class ext extends \phpbb\extension\base
{
	/**
	 * L'estensione richiede phpBB 3.3.0 o superiore.
	 *
	 * @return bool
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		return version_compare($config['version'], '3.3.0', '>=');
	}
}

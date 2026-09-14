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
 * Dati tecnici dell'audio: bitrate, modalita', frequenza, canali.
 *
 * Non sono campi da riempire a mano: stanno dentro il file e getID3 li
 * legge insieme a titolo e durata. Chiederli all'utente vorrebbe dire
 * accettare per buono quello che sceglie, anche quando non corrisponde
 * al file caricato.
 *
 * I brani gia' in libreria restano a zero finche' non vengono
 * ricaricati: il file c'e' ancora, ma rianalizzarli tutti in una
 * migrazione bloccherebbe l'aggiornamento su una libreria grande.
 */
class add_audio_quality extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\salvocortesiano\musicshare\migrations\add_wall_reactions'];
	}

	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					// kbit/s
					'song_bitrate'		=> ['UINT:5', 0],
					// vbr, cbr, abr
					'song_bitrate_mode'	=> ['VCHAR:8', ''],
					// Hz
					'song_samplerate'	=> ['UINT:6', 0],
					// numero di canali: 1 mono, 2 stereo, 6 per il 5.1
					'song_channels'		=> ['UINT:2', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'musicshare_songs'	=> [
					'song_bitrate',
					'song_bitrate_mode',
					'song_samplerate',
					'song_channels',
				],
			],
		];
	}
}

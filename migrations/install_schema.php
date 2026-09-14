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

class install_schema extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				$this->table_prefix . 'musicshare_genres'	=> [
					'COLUMNS'		=> [
						'genre_id'		=> ['UINT', null, 'auto_increment'],
						'genre_name'	=> ['VCHAR_UNI:100', ''],
						'genre_order'	=> ['UINT:4', 0],
					],
					'PRIMARY_KEY'	=> 'genre_id',
				],
				$this->table_prefix . 'musicshare_songs'	=> [
					'COLUMNS'		=> [
						'song_id'			=> ['UINT', null, 'auto_increment'],
						'user_id'			=> ['UINT', 0],
						'song_title'		=> ['VCHAR_UNI:255', ''],
						'song_artist'		=> ['VCHAR_UNI:255', ''],
						'song_album'		=> ['VCHAR_UNI:255', ''],
						'song_year'			=> ['USINT', 0],
						'song_duration'		=> ['UINT:4', 0],
						'file_path'			=> ['VCHAR_UNI:255', ''],
						'file_ext'			=> ['VCHAR:10', ''],
						'file_size'			=> ['UINT:4', 0],
						'cover_path'		=> ['VCHAR_UNI:255', ''],
						'waveform_path'		=> ['VCHAR_UNI:255', ''],
						'upload_time'		=> ['TIMESTAMP', 0],
						'play_count'		=> ['UINT:4', 0],
						'song_approved'		=> ['BOOL', 1],
					],
					'PRIMARY_KEY'	=> 'song_id',
					'KEYS'			=> [
						'user_id'		=> ['INDEX', 'user_id'],
						'approved'		=> ['INDEX', 'song_approved'],
					],
				],
				$this->table_prefix . 'musicshare_song_genre'	=> [
					'COLUMNS'		=> [
						'song_id'		=> ['UINT', 0],
						'genre_id'		=> ['UINT', 0],
					],
					'KEYS'			=> [
						'song_id'		=> ['INDEX', 'song_id'],
						'genre_id'		=> ['INDEX', 'genre_id'],
					],
				],
				$this->table_prefix . 'musicshare_playlists'	=> [
					'COLUMNS'		=> [
						'playlist_id'		=> ['UINT', null, 'auto_increment'],
						'user_id'			=> ['UINT', 0],
						'playlist_name'		=> ['VCHAR_UNI:150', ''],
						'playlist_desc'		=> ['TEXT_UNI', ''],
						'is_public'			=> ['BOOL', 0],
						'created_time'		=> ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY'	=> 'playlist_id',
					'KEYS'			=> [
						'user_id'		=> ['INDEX', 'user_id'],
					],
				],
				$this->table_prefix . 'musicshare_playlist_songs'	=> [
					'COLUMNS'		=> [
						'playlist_id'	=> ['UINT', 0],
						'song_id'		=> ['UINT', 0],
						'song_order'	=> ['UINT:4', 0],
					],
					'KEYS'			=> [
						'playlist_id'	=> ['INDEX', 'playlist_id'],
					],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables'	=> [
				$this->table_prefix . 'musicshare_playlist_songs',
				$this->table_prefix . 'musicshare_playlists',
				$this->table_prefix . 'musicshare_song_genre',
				$this->table_prefix . 'musicshare_songs',
				$this->table_prefix . 'musicshare_genres',
			],
		];
	}
}

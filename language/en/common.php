<?php
/**
 *
 * Music Share extension for phpBB 3.3.x.
 *
 * @copyright 2026 Salvo Cortesiano, https://netshadows.de
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'MUSICSHARE_NAV_LINK'			=> 'Music',
	'MUSICSHARE_BROWSE_TITLE'		=> 'Browse by genre',
	'MUSICSHARE_UPLOAD_TITLE'		=> 'Upload a song',
	'MUSICSHARE_GENRE_NOT_FOUND'	=> 'Genre not found.',
	'MUSICSHARE_PLAYLIST_NOT_FOUND'	=> 'Playlist not found.',
	'MUSICSHARE_PLAYLIST_PRIVATE'	=> 'This playlist is private.',
	'MUSICSHARE_NO_PERMISSION'		=> 'You do not have permission to upload songs.',

	'MUSICSHARE_UPLOAD_ERR_NOFILE'	=> 'You must select an audio file to upload.',
	'MUSICSHARE_UPLOAD_ERR_TYPE'	=> 'This file format is not allowed.',
	'MUSICSHARE_UPLOAD_ERR_SIZE'	=> 'The file exceeds the maximum allowed size.',
	'MUSICSHARE_UPLOAD_ERR_QUOTA'	=> 'You have reached your maximum storage space for songs.',
	'MUSICSHARE_UPLOAD_ERR_MIME'	=> 'The file does not appear to be a valid audio file.',
	'MUSICSHARE_UPLOAD_ERR_STORAGE'	=> 'The storage folder is not writable. Please contact an administrator.',
	'MUSICSHARE_UPLOAD_ERR_MOVE'	=> 'The uploaded file could not be saved.',
	'MUSICSHARE_UPLOAD_SUCCESS'	=> 'Song uploaded successfully!',

	'MUSICSHARE_SONG_TITLE'		=> 'Title',
	'MUSICSHARE_SONG_ARTIST'		=> 'Artist',
	'MUSICSHARE_SONG_ALBUM'		=> 'Album',
	'MUSICSHARE_SONG_YEAR'			=> 'Year',
	'MUSICSHARE_SONG_FILE'			=> 'Audio file',
	'MUSICSHARE_TITLE_EXPLAIN'		=> 'Leave empty to use the title found in the file tags, if any.',
	'MUSICSHARE_COVER_FILE'		=> 'Cover (optional)',
	'MUSICSHARE_COVER_FILE_EXPLAIN'	=> 'If you do not upload one, the cover embedded in the file tags will be used automatically, if available.',
	'MUSICSHARE_GENRES'			=> 'Genres',
	'MUSICSHARE_GENRES_EXPLAIN'	=> 'Select one or more genres for this song.',
	'MUSICSHARE_ALLOWED_FORMATS'	=> 'Allowed formats',
	'MUSICSHARE_MAX_SIZE'			=> 'Maximum size',

	'MUSICSHARE_SONG_UPDATED'		=> 'Song successfully updated.',
	'MUSICSHARE_SONG_DELETED'		=> 'Song deleted.',
	'MUSICSHARE_SONG_DELETE_CONFIRM'	=> 'Do you really want to delete this song? This cannot be undone.',
	'MUSICSHARE_APPROVED'			=> 'Approved',
	'MUSICSHARE_PENDING_APPROVAL'	=> 'Pending approval',

	'MUSICSHARE_PLAYLIST_NAME'		=> 'Playlist name',
	'MUSICSHARE_PLAYLIST_DESC'		=> 'Description',
	'MUSICSHARE_PLAYLIST_PUBLIC'	=> 'Public playlist',
	'MUSICSHARE_PLAYLIST_SAVED'	=> 'Playlist saved.',
	'MUSICSHARE_PLAYLIST_DELETED'	=> 'Playlist deleted.',
	'MUSICSHARE_PLAYLIST_DELETE_CONFIRM'	=> 'Do you really want to delete this playlist?',
	'MUSICSHARE_ADD_TO_PLAYLIST'	=> 'Add to playlist',
	'MUSICSHARE_REMOVE_FROM_PLAYLIST'	=> 'Remove from playlist',
	'MUSICSHARE_NEW_PLAYLIST'		=> 'New playlist',
	'MUSICSHARE_MANAGE_PLAYLIST'	=> 'Manage songs',

	'MUSICSHARE_USED_SPACE'		=> 'Used space',
	'MUSICSHARE_NO_SONGS'			=> 'No songs to show.',
	'MUSICSHARE_NO_PLAYLISTS'		=> 'You have not created any playlists yet.',
	'MUSICSHARE_PLAY'				=> 'Play',

	'MUSICSHARE_TOP_SONGS'			=> 'Most played',
	'MUSICSHARE_SEARCH'			=> 'Search',
	'MUSICSHARE_SEARCH_PLACEHOLDER'	=> 'Search by title, artist or album...',
	'MUSICSHARE_SEARCH_RESULTS_FOR'	=> 'Results for',
	'MUSICSHARE_NO_RESULTS'		=> 'No songs found.',

	'MUSICSHARE_PLAYLIST_NAME_EMPTY'	=> 'You must enter a name for the new playlist.',
	'MUSICSHARE_NEW_PLAYLIST_NAME'	=> 'Or create a new playlist...',
	'MUSICSHARE_ADDED_TO_PLAYLIST'	=> 'Song added to the playlist.',
	'MUSICSHARE_ALREADY_IN_PLAYLIST'	=> 'The song was already in this playlist.',
	'MUSICSHARE_ADD_ERROR'			=> 'The song could not be added to the playlist.',
	'MUSICSHARE_QUEUE'				=> 'Queue',
	'MUSICSHARE_QUEUE_EMPTY'		=> 'The queue is empty.',
	'MUSICSHARE_REMOVE_FROM_QUEUE'	=> 'Remove from queue',

	'UCP_MUSICSHARE_TITLE'			=> 'Music Share',
	'UCP_MUSICSHARE_SONGS'			=> 'My songs',
	'UCP_MUSICSHARE_UPLOAD'		=> 'Upload song',
	'UCP_MUSICSHARE_PLAYLISTS'		=> 'My playlists',

	'MUSICSHARE_OTHER_GENRES'		=> 'Other genres',

	'MUSICSHARE_UPLOAD_ERR_PHP_SIZE'	=> 'The file exceeds the server upload limit (upload_max_filesize). Please contact an administrator.',
	'MUSICSHARE_UPLOAD_ERR_PARTIAL'		=> 'The file upload was interrupted. Please try again.',

	'MUSICSHARE_UPLOAD_STARTING'	=> 'Uploading...',
	'MUSICSHARE_UPLOAD_PROCESSING'	=> 'Transfer complete, processing the file...',
	'MUSICSHARE_UPLOAD_FAILED'		=> 'Upload failed. Please try again.',

	'MUSICSHARE_CLICK_TO_PLAY'		=> 'Click a song to play it: the player appears at the bottom of the page.',
	'MUSICSHARE_GO_TO_BROWSE'		=> 'Go to the Music section',

	'MUSICSHARE_RESUME_HINT'		=> 'Press Play to resume',

	'MUSICSHARE_EDIT_SONG'			=> 'Edit song',
	'MUSICSHARE_NO_COVER'			=> 'No cover set for this song.',
	'MUSICSHARE_REMOVE_COVER'		=> 'Remove the current cover',
	'MUSICSHARE_COVER_EDIT_EXPLAIN'	=> 'Choose an image to replace the current cover. Allowed formats: jpg, png, gif, webp (max 5 MB).',
	'MUSICSHARE_COVER_ERR_TYPE'		=> 'The cover must be a valid jpg, png, gif or webp image.',
	'MUSICSHARE_COVER_ERR_SIZE'		=> 'The cover exceeds the maximum allowed size (5 MB).',

	'MUSICSHARE_UPLOAD_ERR_DUPLICATE'	=> 'You have already uploaded this exact file. Check "My songs".',
	'MUSICSHARE_SONG_NOT_FOUND'			=> 'Song not found.',
	'MUSICSHARE_DOWNLOAD'				=> 'Download',
	'MUSICSHARE_ALL_GENRES'				=> 'All genres',
	'MUSICSHARE_SHUFFLE'				=> 'Shuffle',
	'MUSICSHARE_REPEAT'					=> 'Repeat',
	'MUSICSHARE_REPEAT_OFF'				=> 'Repeat: off',
	'MUSICSHARE_REPEAT_ALL'				=> 'Repeat the whole queue',
	'MUSICSHARE_REPEAT_ONE'				=> 'Repeat the current song',

	'MUSICSHARE_PENDING_EXPLAIN'	=> 'Only you can see and play this song until a moderator approves it.',

	'MUSICSHARE_MENU'				=> 'Player options',
	'MUSICSHARE_CLOSE_PLAYER'		=> 'Close the player',
	'MUSICSHARE_CLEAR_QUEUE'		=> 'Clear the queue',

	'MUSICSHARE_DURATION'			=> 'Duration',
	'MUSICSHARE_UPLOADED_BY'		=> 'Uploaded by',
	'MUSICSHARE_USER_SONGS'			=> 'Songs by %s',
	'MUSICSHARE_USER_SUMMARY'		=> '%1$d songs uploaded, %2$d plays in total.',
	'MUSICSHARE_POST_SONGS'			=> 'Songs',
	'MUSICSHARE_POST_SONGS_TITLE'	=> 'See the songs uploaded by this user',
	'MUSICSHARE_PROFILE_SONGS'		=> 'Songs uploaded',
	'MUSICSHARE_PROFILE_PLAYS'		=> 'Plays received',
	'MUSICSHARE_PROFILE_DURATION'	=> 'Total duration',
	'MUSICSHARE_DURATION_HM'		=> '%1$d h %2$d min',
	'MUSICSHARE_DURATION_M'			=> '%d min',

	'MUSICSHARE_RECENT_SONGS'		=> 'Recently uploaded',
	'MUSICSHARE_NEW_UPLOAD'			=> 'New song',

	'MUSICSHARE_PAUSE'				=> 'Pause',

	'MUSICSHARE_UPLOAD_DATE'		=> 'Uploaded on',

	'MUSICSHARE_LIKE'				=> 'Like',
	'MUSICSHARE_DISLIKE'			=> 'Dislike',
	'MUSICSHARE_VOTE_OWN'			=> 'You cannot vote on a song you uploaded yourself.',
	'MUSICSHARE_VOTE_LOGIN'			=> 'You must be logged in to vote on a song.',
	'MUSICSHARE_VOTE_ERROR'			=> 'Invalid vote.',
	'MUSICSHARE_VOTES_DISABLED'		=> 'Song voting is disabled.',

	'MUSICSHARE_ALLOW_DOWNLOAD_SONG'		=> 'Download of this song',
	'MUSICSHARE_ALLOW_DOWNLOAD_SONG_YES'	=> 'Let other users download the file',
	'MUSICSHARE_ALLOW_DOWNLOAD_SONG_EXPLAIN'	=> 'If you clear this box the song can still be streamed, but nobody will be able to download the file. You and the moderators can still download it.',
	'MUSICSHARE_DOWNLOAD_OFF_BOARD'			=> 'Song downloads are disabled board-wide by the administrator, so this choice has no effect.',

	'MUSICSHARE_DOWNLOAD_ON'		=> 'Download allowed',
	'MUSICSHARE_DOWNLOAD_OFF'		=> 'Download not allowed',

	'MUSICSHARE_SONGS_COUNT'		=> array(
		0	=> 'No songs',
		1	=> '%d song',
		2	=> '%d songs',
	),

	'MUSICSHARE_NO_VIEW_PERMISSION'	=> 'You do not have permission to access the Music section.',

	'MUSICSHARE_SHOW_ALL_GENRES'	=> 'Show all genres, including empty ones',
	'MUSICSHARE_SHOW_USED_GENRES'	=> 'Show only genres with at least one song',

	'MUSICSHARE_PLAYS_COUNT'		=> array(
		0	=> 'No plays',
		1	=> '%d play',
		2	=> '%d plays',
	),

	'MUSICSHARE_UPLOADERS'			=> 'Who shares music',
	'MUSICSHARE_UPLOADER'			=> 'User',
	'MUSICSHARE_UPLOADERS_SEARCH'	=> 'Search by username or email...',
	'MUSICSHARE_NO_UPLOADERS'		=> 'No user has uploaded any songs yet.',
	'MUSICSHARE_LAST_UPLOAD'		=> 'Last upload',
	'MUSICSHARE_SEE_SONGS'			=> 'View songs',
	'MUSICSHARE_SORT_BY'			=> 'Sort by',
	'MUSICSHARE_SORT_SONGS'			=> 'songs uploaded',
	'MUSICSHARE_SORT_PLAYS'			=> 'plays',
	'MUSICSHARE_SORT_RECENT'		=> 'most recent upload',
	'MUSICSHARE_SORT_NAME'			=> 'username',
	'MUSICSHARE_UPLOADERS_COUNT'	=> array(
		0	=> 'No user has uploaded songs yet',
		1	=> '%d user has uploaded songs',
		2	=> '%d users have uploaded songs',
	),

	'MUSICSHARE_DISCLAIMER_TITLE'	=> 'Important notice about copyright',
	'MUSICSHARE_DISCLAIMER_TEXT'	=> 'This section exists to share <strong>loops and tracks made by the users themselves</strong> and <strong>royalty-free music</strong> that may be freely distributed.<br />By uploading a file you declare that you are its author or that you hold the rights needed to distribute it. Uploading copyrighted material without permission is not allowed, and responsibility for what is published rests solely with the user who uploads it. The staff may remove reported or non-compliant songs at any time and without notice.',

	// Notifications and private messages
	'MUSICSHARE_NOTIFICATION_GROUP'			=> 'Music Share',
	'MUSICSHARE_NOTIFICATION_SONG_APPROVED'	=> 'One of my songs is approved',
	'MUSICSHARE_NOTIFICATION_SONG_REJECTED'	=> 'One of my songs is rejected or removed',
	'MUSICSHARE_NOTIFICATION_SONG_NEW'		=> 'Someone uploads a new song',
	'MUSICSHARE_NOTIFICATION_APPROVED_TITLE'	=> 'Your song <strong>%s</strong> has been approved.',
	'MUSICSHARE_NOTIFICATION_REJECTED_TITLE'	=> 'Your song <strong>%s</strong> was not approved and has been removed.',
	'MUSICSHARE_NOTIFICATION_NEW_TITLE'		=> '<strong>%1$s</strong> uploaded the song <strong>%2$s</strong>.',

	'MUSICSHARE_PM_APPROVED_SUBJECT'		=> 'Your song has been approved',
	'MUSICSHARE_PM_APPROVED_BODY'			=> 'The song [b]%1$s[/b] you uploaded has been approved and is now visible to all board users.',
	'MUSICSHARE_PM_REJECTED_SUBJECT'		=> 'Your song was not approved',
	'MUSICSHARE_PM_REJECTED_BODY'			=> 'The song [b]%1$s[/b] you uploaded was not approved and has been removed from the Music section.[br][br]You may contact the staff for further details.',
	'MUSICSHARE_PM_REJECTED_BODY_REASON'	=> 'The song [b]%1$s[/b] you uploaded was not approved and has been removed from the Music section.[br][br]Reason: [i]%2$s[/i]',

	// Song recognition
	'MUSICSHARE_RECO_MATCH_TITLE'	=> 'Possibly copyrighted song',
	'MUSICSHARE_RECO_MATCH_TEXT'	=> 'The file you uploaded matches a commercial release found in the databases: <strong>%s</strong>.<br /><br />This may be a <strong>false positive</strong>: a lot of royalty-free music is registered in the same databases. If the track is your own work or you hold the rights, you can leave it: a moderator will review it.<br /><br />The song has been uploaded but remains <strong>pending approval</strong>. By uploading material you do not hold the rights to, you take responsibility for it.',

	'MUSICSHARE_NOTICE'				=> 'Music Share',

	'MUSICSHARE_DESCRIPTION'			=> 'Description (optional)',
	'MUSICSHARE_DESCRIPTION_EXPLAIN'	=> 'A couple of lines about the song: where it comes from, how you made it, why you are sharing it. It appears under the title in every list. HTML markup is not allowed.',

	'MUSICSHARE_TAG_FALLBACK'		=> 'Leave empty to use the value from the file tags, if present.',

	'MUSICSHARE_SONG_FILE_EXPLAIN'	=> 'Choose the audio file to upload. Title, artist, album, year and cover are read from the file tags, if present.',
	'MUSICSHARE_ARTIST_EXPLAIN'		=> 'Leave empty to use the artist from the file tags, if present.',
	'MUSICSHARE_EDIT_FIELD_EXPLAIN'	=> 'Correct the value if the file tag was wrong or incomplete.',

	'MUSICSHARE_UPLOAD_PENDING'		=> 'Upload complete. The song is awaiting approval by a moderator and is not visible to other users yet.',

	'MUSICSHARE_STOP'				=> 'Stop',
	'MUSICSHARE_SEEK'				=> 'Position in the song',
	'MUSICSHARE_EMBED_LOADING'		=> 'Loading song...',
	'MUSICSHARE_EMBED_MISSING'		=> 'Song not available: it may have been removed or may not be visible to you.',
	'MUSICSHARE_EMBED_PENDING'		=> 'Awaiting approval',
	'MUSICSHARE_COPY_BBCODE'		=> 'Copy code for posts',
	'MUSICSHARE_BBCODE_COPIED'		=> 'Code copied: paste it into a post to embed the song.',
	'MUSICSHARE_BBCODE_MANUAL'		=> 'Copy this code and paste it into a post:',

	'MUSICSHARE_COPY_BBCODE_SHORT'	=> 'Copy code',

	'MUSICSHARE_LAST_SONG'			=> 'Last uploaded song',
]);

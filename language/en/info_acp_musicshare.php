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
	'ACP_MUSICSHARE_TITLE'		=> 'Music Share',
	'ACP_MUSICSHARE_GENRES'		=> 'Genres',
	'ACP_MUSICSHARE_MODERATE'	=> 'Moderation',
	'ACP_MUSICSHARE_SETTINGS'	=> 'Settings',

	'MUSICSHARE_UPLOADED_BY'		=> 'Uploaded by',
	'MUSICSHARE_UPLOAD_DATE'		=> 'Upload date',
	'MUSICSHARE_APPROVE'			=> 'Approve',
	'MUSICSHARE_REJECT'			=> 'Reject and delete',
	'MUSICSHARE_REJECT_CONFIRM'	=> 'Do you really want to reject and permanently delete this song?',
	'MUSICSHARE_NO_PENDING_SONGS'	=> 'No songs pending approval.',
	'MUSICSHARE_SONG_APPROVED'		=> 'Song approved.',
	'MUSICSHARE_SONG_REJECTED'		=> 'Song rejected and deleted.',

	'MUSICSHARE_GENRE_NAME'			=> 'Genre name',
	'MUSICSHARE_GENRE_ORDER'		=> 'Order',
	'MUSICSHARE_GENRE_ORDER_EXPLAIN'	=> 'Genres are shown in ascending order; ties are broken alphabetically.',
	'MUSICSHARE_GENRE_NAME_EMPTY'	=> 'You must enter a genre name.',
	'MUSICSHARE_GENRE_ADDED'		=> 'Genre added.',
	'MUSICSHARE_GENRE_UPDATED'		=> 'Genre updated.',
	'MUSICSHARE_GENRE_DELETED'		=> 'Genre deleted.',
	'MUSICSHARE_GENRE_DELETE_CONFIRM'	=> 'Do you really want to delete this genre? Associated songs will not be deleted, but will lose this genre.',
	'MUSICSHARE_ADD_GENRE'			=> 'Add genre',
	'MUSICSHARE_EDIT_GENRE'			=> 'Edit genre',

	'MUSICSHARE_STORAGE_PATH'		=> 'Storage folder',
	'MUSICSHARE_STORAGE_PATH_EXPLAIN'	=> 'Path where uploaded songs are stored, relative to the board root (e.g. files/musicshare/) or absolute (e.g. /var/www/musicshare/). Must be writable by the web server.',
	'MUSICSHARE_STORAGE_WRITABLE'	=> 'The configured folder is writable.',
	'MUSICSHARE_STORAGE_NOT_WRITABLE'	=> 'Warning: the configured folder does not exist or is not writable. Uploads will not work until this is fixed.',

	'MUSICSHARE_ALLOWED_EXT'		=> 'Allowed extensions',
	'MUSICSHARE_ALLOWED_EXT_EXPLAIN'	=> 'Comma-separated list, without the dot (e.g. mp3,ogg,flac,wav,m4a,aac).',
	'MUSICSHARE_MAX_FILESIZE'		=> 'Maximum file size (bytes)',
	'MUSICSHARE_MAX_FILESIZE_EXPLAIN'	=> 'Also bound by the server/PHP setting (upload_max_filesize), which remains the absolute limit.',
	'MUSICSHARE_MAX_USER_SPACE'	=> 'Maximum space per user (bytes)',
	'MUSICSHARE_MAX_USER_SPACE_EXPLAIN'	=> 'Total allowed size of all songs uploaded by a single user. Set to 0 for no limit.',
	'MUSICSHARE_WAVEFORM_ENABLED'	=> 'Show waveform in the player',
	'MUSICSHARE_WAVEFORM_ENABLED_EXPLAIN'	=> 'The waveform is drawn client-side while a song is playing.',
	'MUSICSHARE_SONGS_PER_PAGE'	=> 'Songs per page',
	'MUSICSHARE_REQUIRE_APPROVAL'	=> 'Require approval for uploaded songs',
	'MUSICSHARE_REQUIRE_APPROVAL_EXPLAIN'	=> 'If enabled, uploaded songs will not be visible or playable until approved by a moderator (moderation UI to be added via ACP or database).',

	'MUSICSHARE_MB'					=> '%d MB',
	'MUSICSHARE_GB'					=> '%d GB',
	'MUSICSHARE_UNLIMITED'			=> 'No limit',
	'MUSICSHARE_CUSTOM_VALUE'		=> 'Current custom value (%s MB)',
	'MUSICSHARE_PHP_LIMIT'			=> 'Limit enforced by the PHP server (upload_max_filesize)',

	'MUSICSHARE_GENRE_CATEGORY'			=> 'Category',
	'MUSICSHARE_GENRE_CATEGORY_EXPLAIN'	=> 'Groups related genres together (e.g. Rock, Metal, Jazz and Blues). Leave empty for no category. You can pick an existing category or type a new one.',
	'MUSICSHARE_NO_CATEGORY'			=> 'Uncategorised',
	'MUSICSHARE_GENRES_INTRO'			=> 'The extension already ships with a list of default genres grouped by category. You are free to add, edit or delete them.',

	'MUSICSHARE_PERSIST_PLAYER'			=> 'Resume playback across pages',
	'MUSICSHARE_PERSIST_PLAYER_EXPLAIN'	=> 'When enabled, playback resumes from where it left off as the user browses the board. Note: navigating reloads the page, so there is a very brief gap. Some browsers block automatic resume until the user interacts with the new page; in that case the player stays ready and only needs a Play click.',

	'ACP_MUSICSHARE_GROUPS'			=> 'Allowed groups',
	'MUSICSHARE_GROUPS_INTRO'		=> 'Choose which groups can upload songs and create playlists. This page writes the same permissions you find under ACP -> Permissions (u_musicshare_upload and u_musicshare_playlist); it is just a convenient shortcut. Groups shown in italics are phpBB defaults. Note: unchecked boxes set the permission to "No", not "Never", so a user can still get the permission through another group they belong to.',
	'MUSICSHARE_CAN_UPLOAD'			=> 'Can upload songs',
	'MUSICSHARE_CAN_PLAYLIST'		=> 'Can create playlists',
	'MUSICSHARE_GROUPS_UPDATED'		=> 'Group permissions updated.',

	'MUSICSHARE_STORAGE_EXPOSED'		=> 'Warning: the storage folder sits inside the board root and does not appear to be protected. Audio files could be downloaded directly by anyone who knows the address, bypassing permissions and moderation. The extension tried to create a protective .htaccess file: if this message persists, move the folder outside the board root or protect it from your hosting panel.',
	'MUSICSHARE_ALLOW_DOWNLOAD'			=> 'Allow song downloads',
	'MUSICSHARE_ALLOW_DOWNLOAD_EXPLAIN'	=> 'Master switch for downloads. When disabled, no song can be downloaded. When enabled, the download button appears next to each song, but each uploader can still forbid it for their own songs from the upload or edit page.',
	'MUSICSHARE_BLOCK_DUPLICATES'		=> 'Block duplicate uploads',
	'MUSICSHARE_BLOCK_DUPLICATES_EXPLAIN'	=> 'Compares the file fingerprint to stop the same user uploading the same song twice and wasting space.',
	'MUSICSHARE_PENDING_SECTION'		=> 'Songs pending approval',
	'MUSICSHARE_ALL_SONGS_SECTION'		=> 'All songs',
	'MUSICSHARE_UNAPPROVE'				=> 'Revoke approval',
	'MUSICSHARE_SONG_UNAPPROVED'		=> 'Approval revoked: the song is no longer visible to users.',
	'MUSICSHARE_SIZE'					=> 'Size',

	'MUSICSHARE_FOLDER_NAMING'			=> 'User folder names',
	'MUSICSHARE_FOLDER_BY_USERNAME'		=> 'Username',
	'MUSICSHARE_FOLDER_BY_ID'			=> 'Numeric ID only',
	'MUSICSHARE_FOLDER_NAMING_EXPLAIN'	=> 'How each user subfolder inside the storage folder is named. "Username" produces for example <em>salvo_2/</em> (the trailing number is the user ID, which avoids collisions between similar names and keeps the folder valid if the user is renamed). Characters not allowed in file names are replaced. Songs already uploaded stay where they are and keep working: this only affects future uploads.',

	'MUSICSHARE_SHOW_STATS'			=> 'Show music statistics',
	'MUSICSHARE_SHOW_STATS_EXPLAIN'	=> 'Adds the number of uploaded songs under the poster profile in topics and on the user profile page. The count for a page of posts is resolved with a single database query, so the performance impact is negligible.',

	'MUSICSHARE_TOAST_ENABLED'			=> 'Notify about new uploads',
	'MUSICSHARE_TOAST_ENABLED_EXPLAIN'	=> 'Shows logged-in users a brief pop-up notice when someone else uploads a song. The check only runs while the browser tab is in the foreground, and the notice is never shown to the uploader.',
	'MUSICSHARE_TOAST_INTERVAL'			=> 'Check interval (seconds)',
	'MUSICSHARE_TOAST_INTERVAL_EXPLAIN'	=> 'How often the browser asks the board for new songs. Lower values make the notice more immediate but increase load; below 30 seconds is not allowed.',
	'MUSICSHARE_RECENT_COUNT'			=> 'Songs in the "Recently uploaded" box',

	'MUSICSHARE_INDEX_FEED'				=> '"Recently uploaded" box',
	'MUSICSHARE_INDEX_FEED_EXPLAIN'		=> 'Where to show the list of the latest songs uploaded by users. The content is cached for one minute, so even the "all pages" option does not add a database query on every page load.',
	'MUSICSHARE_FEED_OFF'				=> 'Do not show it',
	'MUSICSHARE_FEED_INDEX'				=> 'Board index only',
	'MUSICSHARE_FEED_ALL'				=> 'On every board page',
	'MUSICSHARE_INDEX_FEED_COUNT'		=> 'Songs to show in total',
	'MUSICSHARE_INDEX_FEED_PER_USER'	=> 'Maximum songs per user',
	'MUSICSHARE_INDEX_FEED_PER_USER_EXPLAIN'	=> 'Prevents a single very active user from filling the whole box. Set to 0 for no limit.',

	'MUSICSHARE_SHIELD_VERSION'		=> 'version',
	'MUSICSHARE_SHIELD_LICENSE'		=> 'license',
	'MUSICSHARE_SHIELD_REQUIRES'	=> 'Requires:',

	'MUSICSHARE_PLAYER_SCOPE'			=> 'Where to show the bottom player',

	'MUSICSHARE_PLAYER_ALWAYS'			=> 'Always show the player while a song is playing',
	'MUSICSHARE_PLAYER_ALWAYS_EXPLAIN'	=> 'With "Yes" the player bar appears at the bottom of any board page as soon as a song starts. With "No" it only appears on Music section pages; elsewhere playback is controlled from the buttons on the song row, where the progress bar can also be clicked to seek within the song. "No" also avoids downloading the audio file a second time to draw the waveform. In any case, if the playing song is not listed on the current page the player is shown anyway, so the user is never left without controls.',

	'MUSICSHARE_VOTES_ENABLED'			=> 'Allow likes / dislikes on songs',
	'MUSICSHARE_VOTES_ENABLED_EXPLAIN'	=> 'Adds two buttons next to each song. Each user has a single vote per song and can change or cancel it by pressing again. Uploaders cannot vote on their own songs, and guests can see the counts but the buttons stay disabled.',

	'MUSICSHARE_FEED_TITLE'			=> 'Box heading',
	'MUSICSHARE_FEED_TITLE_EXPLAIN'	=> 'Text shown at the top of the recent songs box, one per installed language. Leave empty to use the extension default ("Recently uploaded"). Each user sees the text for their own language; if none is set for that language, the board default language is used, and failing that the built-in translation.',

	'MUSICSHARE_TOAST_SELF'			=> 'Also notify the uploader',
	'MUSICSHARE_TOAST_SELF_EXPLAIN'	=> 'By default the pop-up notice is not shown to the person who uploaded the song, since they already know. With "Yes" they receive it too, as a confirmation. The notice arrives at the first check after the upload, so within the interval set below.',

	'MUSICSHARE_CAN_VIEW'			=> 'Sees the Music section',
	'MUSICSHARE_CAN_FEED'			=> 'Sees the songs box',

	'MUSICSHARE_FEED_COUNT_ALL'			=> 'All',
	'MUSICSHARE_INDEX_FEED_COUNT_EXPLAIN'	=> 'How many songs to list in the box. Beyond twenty songs the box does not grow but becomes scrollable, so it never takes up half the page. With "All" there is no cap; on boards with many uploads it is still wise to pick a number, to keep every page light.',

	'MUSICSHARE_FEED_SCROLL_AFTER'			=> 'Start scrolling after',
	'MUSICSHARE_FEED_SCROLL_AFTER_EXPLAIN'	=> 'Number of songs beyond which the box stops growing and becomes scrollable. Allowed values from 10 to 10000; set 0 so that it never scrolls and grows as needed.',

	'MUSICSHARE_DISCLAIMER_ACP'		=> 'This extension is intended for sharing user-made loops and tracks and royalty-free music. It does not by itself prevent copyrighted material from being uploaded: watching over published content remains the responsibility of the board staff. If you enable this section, consider requiring approval for uploaded songs and checking the Moderation tab regularly.',

	'MUSICSHARE_NOTIFY_PM'			=> 'Also notify by private message',
	'MUSICSHARE_NOTIFY_PM_EXPLAIN'	=> 'In addition to the notification, sends the uploader a private message when one of their songs is approved, rejected or removed. The message is sent from the user performing the action. Notifications remain active in any case, and each user can adjust them under "Manage notifications" in their own panel.',

	'ACP_MUSICSHARE_RECOGNITION'	=> 'Song recognition',
	'MUSICSHARE_RECO_WARNING_TITLE'	=> 'What this check actually does',
	'MUSICSHARE_RECO_WARNING'		=> 'These services do NOT determine whether a song is protected by copyright: they compare the audio against a database of commercial releases and report whether it matches one. A match is a strong hint that the file is not the user\'s own work, but it can be a false positive, because a lot of royalty-free music is registered in those same databases. No match does not mean the song is free: obscure tracks, remixes and live recordings often go unrecognised. Treat it as an aid to moderation, not as a legal check.',
	'MUSICSHARE_RECO_SERVICE'		=> 'Recognition service',
	'MUSICSHARE_RECO_SERVICE_EXPLAIN'	=> 'Choose which service to use. With "Disabled" no song is analysed and no request is spent. The credentials for the chosen service must be filled in below, otherwise the check stays inactive.',
	'MUSICSHARE_RECO_OFF'			=> 'Disabled',
	'MUSICSHARE_RECO_SECONDS'		=> 'Seconds of audio to analyse',
	'MUSICSHARE_RECO_SECONDS_EXPLAIN'	=> 'Only the beginning of the song is sent, not the whole file. The services analyse a few seconds: 10 to 15 is usually enough.',
	'MUSICSHARE_AUDD_TOKEN'			=> 'AudD API token',
	'MUSICSHARE_AUDD_TOKEN_EXPLAIN'	=> 'Obtained by signing up at dashboard.audd.io. A few free requests are included, after which the service is paid.',
	'MUSICSHARE_ACR_HOST'			=> 'ACRCloud host',
	'MUSICSHARE_ACR_HOST_EXPLAIN'	=> 'Your project address, for example identify-eu-west-1.acrcloud.com. You will find it in the ACRCloud console together with the keys.',
	'MUSICSHARE_ACR_KEY'			=> 'ACRCloud access key',
	'MUSICSHARE_ACR_SECRET'			=> 'ACRCloud access secret',
	'MUSICSHARE_RECO_GROUPS'		=> 'Groups subject to the check',
	'MUSICSHARE_RECO_GROUPS_EXPLAIN'	=> 'Only uploads from users in the selected groups are analysed. If you select no group the check never runs: this is deliberate, so that requests are never spent without your knowledge. It is wise to exclude staff and trusted users.',
	'MUSICSHARE_RECO_CHECKED'		=> 'Check uploads',
	'MUSICSHARE_RECO_TEST'			=> 'Test credentials',
	'MUSICSHARE_RECO_TEST_EXPLAIN'	=> 'The test really queries the service, so it spends one request from your plan. With AudD the sample file from their documentation is analysed, because an empty sample would be rejected even with a valid token.',
	'MUSICSHARE_RECO_TEST_OK'		=> 'The credentials work: the service answered correctly.',
	'MUSICSHARE_RECO_TEST_FAILED'	=> 'Test failed. Service response: %s',
	'MUSICSHARE_RECO_TEST_NOT_CONFIGURED'	=> 'Choose a service and fill in its credentials before running the test.',
	'MUSICSHARE_RECO_TEST_NETWORK'	=> 'Could not reach the service. The server may not have internet access.',
	'MUSICSHARE_RECO_TEST_RESPONSE'	=> 'The service answered in an unrecognised format.',

	// API key guide
	'MUSICSHARE_RECO_GUIDE_TITLE'	=> 'How to obtain the API keys (step by step)',
	'MUSICSHARE_GUIDE_AUDD_1'		=> 'Open <strong>dashboard.audd.io</strong> and sign up with your email. No credit card is needed to start.',
	'MUSICSHARE_GUIDE_AUDD_2'		=> 'After confirming your email you reach the dashboard: the API token is shown right on the main page, a long string of letters and numbers.',
	'MUSICSHARE_GUIDE_AUDD_3'		=> 'Copy it and paste it below into the "AudD API token" field.',
	'MUSICSHARE_GUIDE_AUDD_4'		=> 'Select "AudD" as the service, save, then press "Test credentials" to make sure it works.',
	'MUSICSHARE_GUIDE_ACR_1'		=> 'Open <strong>console.acrcloud.com/signup</strong> and create a free account.',
	'MUSICSHARE_GUIDE_ACR_2'		=> 'In the console create a new <strong>Audio &amp; Video Recognition</strong> project, choosing the region closest to you: for Europe <em>eu-west-1</em> works well.',
	'MUSICSHARE_GUIDE_ACR_3'		=> 'Once the project is open, the console shows three values: <strong>host</strong> (for example identify-eu-west-1.acrcloud.com), <strong>access key</strong> and <strong>access secret</strong>.',
	'MUSICSHARE_GUIDE_ACR_4'		=> 'Copy the three values into the matching fields below. The host must be written without "https://" in front.',
	'MUSICSHARE_GUIDE_ACR_5'		=> 'Select "ACRCloud" as the service, save, then press "Test credentials".',
	'MUSICSHARE_GUIDE_AFTER_TITLE'	=> 'Once the keys are in place',
	'MUSICSHARE_GUIDE_AFTER_1'		=> 'Tick in the table below the groups whose uploads should be checked. Until you select at least one group the check never runs and no request is spent.',
	'MUSICSHARE_GUIDE_AFTER_2'		=> 'It is wise to leave staff and trusted users out: every check costs one request.',
	'MUSICSHARE_GUIDE_AFTER_3'		=> 'Test it by uploading a well-known commercial track from an account in a checked group: the warning must appear and the song must be held for approval.',
	'MUSICSHARE_GUIDE_PRICING'		=> 'pricing',
	'MUSICSHARE_GUIDE_SERVICE_PAGE'	=> 'service page',
	'MUSICSHARE_GUIDE_COST'			=> 'Mind the cost: one request is spent for every song uploaded by a user in a checked group, even when nothing matches. AudD includes a few free requests and is then pay-as-you-go; ACRCloud offers a free trial. Check the current plans on their websites before opening the check to all users.',

	'MUSICSHARE_SEARCH_EXPLAIN'		=> 'Search by title, artist, album or the name of the user who uploaded the song.',
	'MUSICSHARE_SEARCH_RESET'		=> 'Clear search',
	'MUSICSHARE_SEARCH_ACTIVE'		=> 'Search active on "%1$s": %2$d matching songs. Press "Clear search" to see the full list again.',

	'MUSICSHARE_DESCRIPTIONS'			=> 'Allow a description for each song',
	'MUSICSHARE_DESCRIPTIONS_EXPLAIN'	=> 'Adds a field to the upload form where the user can describe the song in their own words. The description appears under the title in every list, including the box on board pages. Disabling it hides the field and stops showing existing descriptions, but they stay in the database and come back if you enable the option again.',
	'MUSICSHARE_DESCRIPTION_MAX'		=> 'Maximum description length',
	'MUSICSHARE_DESCRIPTION_MAX_EXPLAIN'	=> 'Maximum number of characters, from 50 to 1000. The recommended value is 300: enough for a couple of lines, not so much that it unbalances the lists. The limit is enforced server-side too, not only in the form.',

	'MUSICSHARE_TOAST_SOUND'		=> 'Sound on notices',
	'MUSICSHARE_TOAST_SOUND_EXPLAIN'	=> 'Plays two short notes when a new song notice appears. The sound is generated by the browser, there is no file to download. Many browsers block playback until the user has interacted with the page: in that case the notice still appears, just silently.',
	'MUSICSHARE_TOAST_VOLUME'		=> 'Notice sound volume',
	'MUSICSHARE_TOAST_VOLUME_EXPLAIN'	=> 'From 0 to 100. The recommended value is 30: audible without being intrusive while browsing.',

	'MUSICSHARE_BBCODE'				=> 'Allow embedding songs in posts',
	'MUSICSHARE_BBCODE_EXPLAIN'		=> 'Enables the [musicshare]12[/musicshare] code, which embeds a full player for the given song in the post, with play, pause, stop and a progress bar. In "My songs" each user gets a button that copies the ready-made code. Permissions are checked when the post is read: a song that was removed, is unapproved or is not visible to the reader is not played.',

	'MUSICSHARE_CAN_NOTIFY'			=> 'Receives notifications',

	'MUSICSHARE_CLEANUP'			=> 'Notification cleanup',
	'MUSICSHARE_CLEANUP_ENABLED'	=> 'Daily automatic cleanup',
	'MUSICSHARE_CLEANUP_ENABLED_EXPLAIN'	=> 'Once a day removes the extension notifications that have already been read and are older than the period set below. It uses phpBB scheduled tasks, so no system cron is needed: it runs after a page visit, in batches, without slowing browsing down.',
	'MUSICSHARE_CLEANUP_DAYS'		=> 'Keep read notifications for',
	'MUSICSHARE_CLEANUP_DAYS_EXPLAIN'	=> 'Days, from 0 to 365. With 0 read notifications are removed at the first run, with no waiting. Unread notifications are never touched.',
	'MUSICSHARE_CLEANUP_NOW'		=> 'Clean now',
	'MUSICSHARE_CLEANUP_NOW_EXPLAIN'	=> 'Immediately removes all extension notifications that have been read, ignoring the retention period. Unread notifications are kept.',
	'MUSICSHARE_CLEANUP_STATE'		=> 'Read notifications removable now: %1$d. Last cleanup: %2$s.',
	'MUSICSHARE_CLEANUP_NEVER'		=> 'never run',
	'MUSICSHARE_CLEANUP_DONE'		=> 'Cleanup complete: %d notifications removed.',

	// ---- Maintenance and checks tab ----
	'ACP_MUSICSHARE_MAINTENANCE'	=> 'Maintenance and checks',
	'MS_MAINT_INTRO'		=> 'This tab checks the actual state of the extension: server environment, storage folder, database structure, consistency between songs and files on disk, notifications and optional features. Next to each problem you will find how to fix it.',
	'MS_SUMMARY'			=> 'Summary',
	'MS_STATE_OK'			=> 'fine',
	'MS_STATE_WARN'			=> 'worth a look',
	'MS_STATE_ERROR'		=> 'need fixing',
	'MS_ALL_GOOD'			=> 'No problems found: the extension appears to be configured correctly.',
	'MS_HOWTO'				=> 'How to fix it',
	'MS_RELOAD'				=> 'Run the checks again',
	'MS_RELOAD_EXPLAIN'		=> 'The checks run every time the tab is opened: use this button after fixing something.',
	'MS_CLEANUP_AVAILABLE'	=> 'Read notifications removable now',

	'MS_SEC_ENV'			=> '1. Server environment',
	'MS_SEC_STORAGE'		=> '2. Storage folder',
	'MS_SEC_DB'				=> '3. Database structure',
	'MS_SEC_INTEGRITY'		=> '4. Consistency between database and files',
	'MS_SEC_NOTIF'			=> '5. Notification system',
	'MS_SEC_FEATURES'		=> '6. Optional features',
	'MS_SEC_ACTIONS'		=> 'Actions',

	'MS_CHK_PHP'			=> 'PHP version',
	'MS_CHK_EXECTIME'		=> 'Maximum execution time',
	'MS_CHK_MEMORY'			=> 'Available memory',
	'MS_CHK_UPLOADSIZE'		=> 'Maximum upload size',
	'MS_CHK_POSTSIZE'		=> 'Maximum post size',
	'MS_CHK_CURL'			=> 'cURL library',
	'MS_CHK_HMAC'			=> 'hash_hmac function',
	'MS_CHK_GETID3'			=> 'getID3 library (tag reading)',
	'MS_CHK_PATH'			=> 'Folder path',
	'MS_CHK_PATH_EXISTS'	=> 'The folder exists',
	'MS_CHK_WRITABLE'		=> 'The folder is writable',
	'MS_CHK_PROTECTED'		=> 'Protection from direct access',
	'MS_CHK_DISKFREE'		=> 'Free disk space',
	'MS_CHK_SONGS'			=> 'Stored songs',
	'MS_CHK_TABLES'			=> 'Extension tables',
	'MS_CHK_COLUMNS'		=> 'Columns of the songs table',
	'MS_CHK_MIGRATIONS'		=> 'Applied migrations',
	'MS_CHK_MISSING_FILES'	=> 'Songs whose file no longer exists',
	'MS_CHK_MISSING_COVERS'	=> 'Missing covers',
	'MS_CHK_ORPHANS'		=> 'Files on disk not linked to any song',
	'MS_CHK_ORPHAN_VOTES'	=> 'Votes referring to missing songs',
	'MS_CHK_ORPHAN_PLAYLIST'	=> 'Playlist entries referring to missing songs',
	'MS_CHK_ORPHAN_GENRES'	=> 'Genre links referring to missing songs',
	'MS_CHK_NOTIF_TYPES'	=> 'Registered notification types',
	'MS_CHK_NOTIF_SERVICES'	=> 'Notification type services',
	'MS_CHK_NOTIF_USERS'	=> 'Notification recipients',
	'MS_CHK_NOTIF_ROWS'		=> 'Notifications in the database',
	'MS_CHK_CLEANUP'		=> 'Automatic cleanup',
	'MS_CHK_RECO'			=> 'Song recognition',
	'MS_CHK_BBCODE'			=> 'Embedding in posts',
	'MS_CHK_TOAST'			=> 'Pop-up notices',
	'MS_CHK_APPROVAL'		=> 'Approval of uploads',
	'MS_CHK_PENDING'		=> 'Songs awaiting approval',

	'MS_FIX_PHP'			=> 'The extension requires PHP 7.2 or later. Update the PHP version from your hosting panel.',
	'MS_FIX_EXECTIME'		=> 'Less than 30 seconds may not be enough to upload a long song, read its tags and contact the recognition service. Raise max_execution_time if you can; otherwise reduce the maximum file size or disable recognition.',
	'MS_FIX_MEMORY'			=> 'With less than 64 MB, reading tags of large files may fail. Raise memory_limit from your hosting panel.',
	'MS_FIX_UPLOADSIZE'		=> 'The maximum size set in the extension exceeds the one allowed by PHP: files above the PHP limit are rejected before the extension ever sees them. Lower the value in Settings or raise upload_max_filesize on the server.',
	'MS_FIX_POSTSIZE'		=> 'post_max_size should be greater than or equal to upload_max_filesize, otherwise uploads near the limit fail without a clear message.',
	'MS_FIX_CURL'			=> 'Without cURL, song recognition uses PHP streams, which are slower and less reliable. Not a problem if recognition stays disabled.',
	'MS_FIX_HMAC'			=> 'Without hash_hmac, requests to ACRCloud cannot be signed. Use AudD instead, or ask your host to enable the PHP hash extension.',
	'MS_FIX_GETID3'			=> 'The library that reads audio file tags is missing: title, artist, duration and cover will not be detected. Re-upload the extension\'s vendor/getid3 folder to the server.',
	'MS_FIX_PATH_EXISTS'	=> 'The folder does not exist: create it on the server or correct the path in Settings. It will be created automatically at the first upload if the parent folder is writable.',
	'MS_FIX_WRITABLE'		=> 'The server cannot write into the folder: no upload will succeed. Set permissions to 755 or 777 from your hosting file manager.',
	'MS_FIX_PROTECTED'		=> 'The folder has no .htaccess preventing direct reading. Files have random names and cannot be guessed, but the protection is still advisable. It will be created at the first upload; if the server is not Apache, moving the folder outside the site root is the safest solution.',
	'MS_FIX_DISKFREE'		=> 'Less than 100 MB free: the next uploads may fail. Free some space or reduce the per-user quota.',
	'MS_FIX_TABLES'			=> 'Some extension tables do not exist: the installation is incomplete. Disable and re-enable the extension from Manage extensions so the migrations run again.',
	'MS_FIX_COLUMNS'		=> 'Some columns are missing: a migration probably stopped halfway. Disable and re-enable the extension; if the problem persists, check the database user permissions.',
	'MS_FIX_MISSING_FILES'	=> 'These songs are in the database but the file is no longer on disk: they cannot be played. Delete them from the Moderation tab or restore the files from a backup.',
	'MS_FIX_MISSING_COVERS'	=> 'The covers of these songs are no longer on disk: the default icon is shown instead. You can upload them again by editing the song.',
	'MS_FIX_ORPHANS'		=> 'There are files on disk that no song claims, usually leftovers from interrupted uploads or deleted songs. They take up space but cause no malfunction: you can remove them by hand from your hosting file manager.',
	'MS_FIX_ORPHAN_ROWS'	=> 'There are rows referring to songs that no longer exist. They cause no errors, but if there are many it is worth removing them with a direct database query.',
	'MS_FIX_NOTIF_TYPES'	=> 'The notification types are not registered or not enabled: no notification will be sent. Disable and re-enable the extension so the migration that registers them runs again.',
	'MS_FIX_NOTIF_SERVICES'	=> 'Some notification types cannot be built: there is a problem in the service configuration. Purge the phpBB cache; if the problem persists, re-upload the extension files.',
	'MS_FIX_NOTIF_NOBODY'	=> 'No group has permission to receive new song notifications, so none will be sent. Grant the permission to the groups you want in the Authorised groups tab.',
	'MS_FIX_NOTIF_TOOMANY'	=> 'There are %1$d recipients: every uploaded song would create as many rows in the database, about %2$d per hundred songs. This can slow down or break uploads. Restrict the "Receives notifications of new songs" permission to a few groups in the Authorised groups tab.',
	'MS_FIX_NOTIF_ROWS'		=> 'The notifications table is very large and is queried on every board page. There are %1$d already-read notifications you can remove right now with the button below.',
	'MS_FIX_CLEANUP'		=> 'Automatic cleanup is disabled: read notifications will pile up without limit. Enable it in Settings.',
	'MS_FIX_RECO_GROUPS'	=> 'Recognition is configured but no group is subject to the check, so it never runs. Select the groups in the Song recognition tab.',
	'MS_FIX_TOAST'			=> 'An interval below 30 seconds generates many requests from every open tab. Raise it to at least 30 seconds in Settings.',
	'MS_FIX_APPROVAL'		=> 'Uploaded songs become visible to everyone immediately. Given the nature of music content, consider enabling prior approval in Settings.',
	'MS_FIX_PENDING'		=> 'There are songs awaiting approval: their uploaders cannot share them yet. Review them in the Moderation tab.',

	'MS_PURGE'				=> 'Delete all notifications',
	'MS_PURGE_EXPLAIN'		=> 'Removes ALL extension notifications, including those users have not read yet: those notices will disappear without ever being seen. It does not touch songs, playlists or votes, only notifications. Useful to start over after restricting the recipients.',
	'MS_PURGE_CONFIRM'		=> 'You are about to remove %d extension notifications, including ones users have not read yet. Songs and data are not touched, but unread notices will be lost. Do you want to proceed?',
	'MS_PURGE_DONE'			=> 'Removed %d notifications.',
	'MS_NOTIF_TOTAL_LABEL'	=> 'Extension notifications in total',

	'MS_FIX_NOTIF_ROWS_UNREAD'	=> 'There are %1$d notifications, nearly all still unread: cleaning only read notifications would remove none. They are the result of sending to too many recipients. First restrict the "Receives notifications of new songs" permission in the Authorised groups tab, then use "Delete all notifications" below to start over.',

	'MUSICSHARE_GROUPS_UNCHANGED'		=> 'Nothing to save: the permissions were already as shown.',
	'MUSICSHARE_GROUPS_ROLES_TITLE'		=> 'How these permissions are saved',
	'MUSICSHARE_GROUPS_ROLES_NOTE'		=> 'Only the groups you actually change are modified: the others are left untouched. If a group uses a role for user permissions (for example "Standard registered users"), the change is applied to the role, so the assignment is not lost. Keep in mind that a role can be shared by several groups: in that case the change applies to all of them. After saving you are told which roles were modified.',
	'MUSICSHARE_GROUPS_ROLES_TOUCHED'	=> 'Modified roles: %s. The change applies to every group using these roles.',

	'MUSICSHARE_SONG_NOT_FOUND'			=> 'Song not found: it may have been deleted in the meantime.',
	'MUSICSHARE_SONG_ALREADY_APPROVED'	=> 'This song was already approved: nothing was changed and no second notice was sent to the uploader. If you still saw it among the pending ones, the page was out of date.',
	'MUSICSHARE_SONG_ALREADY_UNAPPROVED'	=> 'This song was already awaiting approval: nothing was changed and no second notice was sent to the uploader.',
]);

<?php
/**#@+
 * This extension sends Webhook notifications to a hubot with the hubot-incoming plugin installed.
 * This file contains configuration options for the extension.
 *
 * @ingroup Extensions
 * @link https://github.com/67P/mediawiki-hubot-incoming
 * @author Sebastian Kippe <sebastian@kip.pe>
 * @copyright Copyright © Sebastian Kippe
 * @license http://en.wikipedia.org/wiki/MIT_License MIT
 */

if(!defined('MEDIAWIKI')) die();
if (!isset($hpc_attached)) die();

###############################
# Hubot/Webhook configuration #
###############################

  // Your Hubot incoming webhook URL. Learn more: https://github.com/67P/hubot-incoming-webhook
  $wgHubotIncomingWebhookUrl = "";
  // Room/channel to post notifications to
  $wgHubotIncomingRoomName   = "";
  // Method for sending hooks. Can be "curl" or "file_get_contents". Defaults to "curl"
  // Note: "curl" needs the curl extension to be enabled. "file_get_contents" needs "allow_url_fopen" to be enabled in php.ini
  $wgHubotIncomingSendMethod = "curl";

##################
# MEDIAWIKI URLS #
##################

  // REQUIRED

  // URL of your MediaWiki installation incl. the trailing /.
  $wgWikiUrl       = "";
  // Wiki script name. Leave this to default one if you do not have URL rewriting enabled.
  $wgWikiUrlEnding = "index.php?title=";

  // OPTIONAL

  $wgWikiUrlEndingUserRights        = "Special%3AUserRights&user=";
  $wgWikiUrlEndingBlockUser         = "Special:Block/";
  $wgWikiUrlEndingUserPage          = "User:";
  $wgWikiUrlEndingUserTalkPage      = "User_talk:";
  $wgWikiUrlEndingUserContributions = "Special:Contributions/";
  $wgWikiUrlEndingBlockList         = "Special:BlockList";
  $wgWikiUrlEndingEditArticle       = "action=edit";
  $wgWikiUrlEndingDeleteArticle     = "action=delete";
  $wgWikiUrlEndingHistory           = "action=history";

#####################
# MEDIAWIKI ACTIONS #
#####################

// Set desired options to false to disable notifications of those actions.

  // New user added
  $wgHubotIncomingNewUser = true;
  // Article added
  $wgHubotIncomingAddedArticle = true;
  // Article removed
  $wgHubotIncomingRemovedArticle = true;
  // Article moved to another title
  $wgHubotIncomingMovedArticle = true;
  // Article edited
  $wgHubotIncomingEditedArticle = true;

?>

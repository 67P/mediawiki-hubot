<?php
/**#@+
 * This extension sends Webhook notifications to a hubot with the
 * hubot-incoming-webhook plugin installed.  This file contains configuration
 * options for the extension.
 *
 * @ingroup Extensions
 * @link https://github.com/67P/mediawiki-hubot
 * @author Sebastian Kippe <sebastian@kip.pe>
 * @copyright Copyright © Sebastian Kippe
 * @license http://en.wikipedia.org/wiki/MIT_License MIT
 */

if (!defined('MEDIAWIKI')) die();

$hpc_attached = true;
require_once("DefaultConfig.php");

$wgHooks['PageContentSaveComplete'][] = 'hubot_incoming_page_content_saved';
$wgHooks['ArticleInsertComplete'][] = 'hubot_incoming_article_inserted';
$wgHooks['ArticleDeleteComplete'][] = 'hubot_incoming_article_deleted';
$wgHooks['TitleMoveComplete'][] = 'hubot_incoming_article_moved';
$wgHooks['AddNewAccount'][] = 'hubot_incoming_new_user_account';
$wgHooks['BlockIpComplete'][] = 'hubot_incoming_user_blocked';
/* $wgHooks['UploadComplete'][] = 'hubot_incoming_file_uploaded'; */

$wgExtensionCredits['other'][] = array(
  'path' => __FILE__,
  'name' => 'Hubot Incoming Webhooks',
  'author' => 'Sebastian Kippe',
  'description' => 'Sends notifications for MediaWiki actions to a Hubot',
  'url' => 'https://github.com/67P/mediawiki-hubot-incoming',
  'version' => '0.1.0'
);

/**
 * Occurs after the save page request has been processed.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
 */
function hubot_incoming_page_content_saved(WikiPage $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
{
  global $wgHubotEditedArticle, $wgWikiUrl, $wgWikiUrlEnding;
  if (!$wgHubotEditedArticle) return;
  if (!$wgHubotEditedArticleMinorChange && $isMinor) return;

  // Skip for new articles
  $isNew = $status->value['new'];
  if ($isNew) { return true; }

  $rev = $status->value['revision'];

  $message = sprintf(
    "%s %s %s %s %s",
    $user,
    $isMinor == true ? "made a minor edit to" : "edited",
    $article->getTitle()->getFullText(),
    $summary == "" ? "" : "(".trim($summary).")",
    $wgWikiUrl.$wgWikiUrlEnding.urlencode($article->getTitle()->getFullText())."&diff=".$rev->getId()."&oldid=".$rev->getParentId()
  );

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Occurs after a new article has been created.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleInsertComplete
 */
function hubot_incoming_article_inserted(WikiPage $article, $user, $text, $summary, $isminor, $iswatch, $section, $flags, $revision)
{
  global $wgHubotAddedArticle, $wgWikiUrl, $wgWikiUrlEnding;;
  if (!$wgHubotAddedArticle) return;

  // Do not announce newly added file uploads as articles...
  if ($article->getTitle()->getNsText() == "File") return true;

  $message = sprintf(
    "%s created %s %s",
    $user,
    $article->getTitle()->getFullText(),
    $wgWikiUrl.$wgWikiUrlEnding.urlencode($article->getTitle()->getFullText())
  );

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Occurs after the delete article request has been processed.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
 */
function hubot_incoming_article_deleted(WikiPage $article, $user, $reason, $id)
{
  global $wgHubotRemovedArticle;
  if (!$wgHubotRemovedArticle) return;

  $message = sprintf(
    "%s deleted %s. Reason: %s",
    $user,
    $article->getTitle()->getFullText(),
    $reason);

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Occurs after a page has been moved.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
 */
function hubot_incoming_article_moved($title, $newtitle, $user, $oldid, $newid, $reason = null)
{
  global $wgHubotMovedArticle;
  if (!$wgHubotMovedArticle) return;

  $message = sprintf(
    "%s moved %s to %s. Reason: %s",
    $user,
    $title,
    $newtitle,
    $reason);

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Called after a user account is created.
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
 */
function hubot_incoming_new_user_account($user, $byEmail)
{
  global $wgHubotNewUser;
  if (!$wgHubotNewUser) return;

  $message = sprintf(
    "New wiki user created: %s", $user
  );

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Called after a user account or IP is blocked
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
 */
function hubot_incoming_user_blocked(Block $block, $user)
{
  global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingBlockList;

  $message = sprintf(
    "%s has blocked %s %s. %s",
    $user,
    $block->getTarget(),
    $block->mReason == "" ? "" : "with reason '".$block->mReason,
    "All blocks: ".$wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingBlockList);

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Sends the webhook
 * @param message Message to be sent.
*/
function push_hubot_incoming_notify($message)
{
  global $wgHubotWebhookUrl, $wgHubotRoomName, $wgHubotSendMethod;

  // Don't break JSON with " in message
  $message = str_replace('"', "'", $message);

  $payload = sprintf('{"message": "%s", "room": "%s"}',
                     $message, $wgHubotRoomName);

  // Send data via file_get_contents. Note that you will need to have allow_url_fopen enabled in php.ini for this to work.
  if ($wgHubotSendMethod == "file_get_contents") {
    $extradata = array(
      'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $payload,
      ),
    );
    $context = stream_context_create($extradata);
    $result = file_get_contents($wgHubotWebhookUrl, false, $context);
  }
  // Send data via cURL (default). Note that you will need to have cURL enabled for this to work.
  else {
    $h = curl_init();
    curl_setopt($h, CURLOPT_URL, $wgHubotWebhookUrl);
    curl_setopt($h, CURLOPT_POST, 1);
    curl_setopt($h, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($h);
    curl_close($h);
  }
}
?>

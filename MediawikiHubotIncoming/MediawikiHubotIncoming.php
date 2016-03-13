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

if (!defined('MEDIAWIKI')) die();

$hpc_attached = true;
require_once("DefaultConfig.php");

$wgHooks['PageContentSaveComplete'][] = 'hubot_incoming_page_content_saved';
$wgHooks['ArticleInsertComplete'][] = 'hubot_incoming_article_inserted';
$wgHooks['ArticleDeleteComplete'][] = 'hubot_incoming_article_deleted';
$wgHooks['TitleMoveComplete'][] = 'hubot_incoming_article_moved';
$wgHooks['AddNewAccount'][] = 'hubot_incoming_new_user_account';
$wgHooks['BlockIpComplete'][] = 'hubot_incoming_user_blocked';
$wgHooks['UploadComplete'][] = 'hubot_incoming_file_uploaded';

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
  global $wgHubotIncomingEditedArticle, $wgWikiUrl, $wgWikiUrlEnding;
  if (!$wgHubotIncomingEditedArticle) return;

  // Skip for new articles
  $isNew = $status->value['new'];
  if ($isNew) { return true; }

  $rev = $status->value['revision'];

  $message = sprintf(
    "%s %s %s %s %s",
    $user,
    $isMinor == true ? "made a minor edit to" : "edited",
    $article->getTitle()->getFullText(),
    $summary == "" ? "" : "($summary)",
    $wgWikiUrl.$wgWikiUrlEnding.$article->getTitle()->getFullText()."&diff=".$rev->getId()."&oldid=".$rev->getParentId()
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
  global $wgHubotIncomingAddedArticle;
  if (!$wgHubotIncomingAddedArticle) return;

  // Do not announce newly added file uploads as articles...
  if ($article->getTitle()->getNsText() == "File") return true;

  $message = sprintf(
    "%s created %s %s",
    $user,
    $article->getTitle()->getFullText(),
    $wgWikiUrl.$wgWikiUrlEnding.$article->getTitle()->getFullText()
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
  global $wgHubotIncomingRemovedArticle;
  if (!$wgHubotIncomingRemovedArticle) return;

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
  global $wgHubotIncomingMovedArticle;
  if (!$wgHubotIncomingMovedArticle) return;

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
  global $wgHubotIncomingNewUser;
  if (!$wgHubotIncomingNewUser) return;

  $message = sprintf(
    "New wiki user created: %s %s",
    $user,
    $wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserPage.$user
  );

  push_hubot_incoming_notify($message);
  return true;
}

/**
 * Sends the webhook
 * @param message Message to be sent.
*/
function push_hubot_incoming_notify($message)
{
  global $wgHubotIncomingWebhookUrl, $wgHubotIncomingRoomName, $wgHubotIncomingSendMethod;

  // Don't break JSON with " in message
  $message = str_replace('"', "'", $message);

  $payload = sprintf('{"message": "%s", "room": "%s"}',
                     $message, $wgHubotIncomingRoomName);

  // Send data via file_get_contents. Note that you will need to have allow_url_fopen enabled in php.ini for this to work.
  if ($wgHubotIncomingSendMethod == "file_get_contents") {
    $extradata = array(
      'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $payload,
      ),
    );
    $context = stream_context_create($extradata);
    $result = file_get_contents($wgHubotIncomingWebhookUrl, false, $context);
  }
  // Send data via cURL (default). Note that you will need to have cURL enabled for this to work.
  else {
    $h = curl_init();
    curl_setopt($h, CURLOPT_URL, $wgHubotIncomingWebhookUrl);
    curl_setopt($h, CURLOPT_POST, 1);
    curl_setopt($h, CURLOPT_POSTFIELDS, $post);
    curl_exec($h);
    curl_close($h);
  }
}
?>

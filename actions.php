<?php
/**
 * @file
 * Implements moderation related actions on cockpit collections.
 */

/**
 * Exclude unpublished entries.
 */
$app->on('collections.find.before', function ($name, &$options) use ($app) {

  // Get the collection.
  $collection = $this->module('collections')->collection($name);
  // Exclude on unpublished state.
  foreach ($collection['fields'] as $field) {
    if ($field['type'] === 'moderation') {
      $options['filter']['$and'][] = ["{$field['name']}" => ['$exists' => TRUE]];
      $options['filter']['$and'][] = ["{$field['name']}" => ['$ne' => 'Unpublished']];
      break;
    }
  }
});

/**
 * Iterate over the collection entries.
 *
 * For the draft ones check if we have a previous published revision.
 */
$app->on('collections.find.after', function ($name, &$entries) use ($app) {
  $token = $app->param('previewToken', FALSE);
  // If we have a valid previewToken don't need to go further.
  if ($token && $app->module('moderation')->validateToken($token)) {
    return;
  }

  // Get the moderation field.
  $moderation_field = $app->module('moderation')->getModerationField($name);
  if (!$moderation_field) {
    return;
  }

  foreach ($entries as $idx => $entry) {
    if (!isset($entry[$moderation_field])) {
      continue;
    }

    // If Draft ensure we retrieve the latest published revision.
    if ($entry[$moderation_field] == 'Draft') {
      $revisions = $app->helper('revisions')->getList($entry['_id']);
      $published = $app->module('moderation')->getLastPublished($entry['_id'], $moderation_field, $revisions);
      if ($published) {
        $entries[$idx] = $published;
      }
      else {
        unset($entries[$idx]);
      }
    }

    // Remove moderation field from the answer.
    if (isset($entries[$idx][$moderation_field])) {
      unset($entries[$idx][$moderation_field]);
    }
  }
  // Rebuild array indices.
  $entries = array_values($entries);
});

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
    if ($field['name'] === 'status' && $field['type'] === 'moderation') {
      $options['filter']['$and'] = [
         ["{$field['name']}" => ['$exists' => TRUE]],
         ["{$field['name']}" => ['$ne' => 'Unpublished']],
      ];
      break;
    }
  }
});

/**
 * Iterate over the collection entries.
 * For the draft ones check if we have a previous published revision.
 */
$app->on('collections.find.after', function ($name, &$entries) use ($app) {
  $token = $app->param('previewToken', false);
  // If we have a valid previewToken don't need to go further.
  if ($token && $app->module('moderation')->validateToken($token)) {
    return;
  }

  foreach ($entries as $idx => $entry) {
    if (!isset($entry['status'])) {
      continue;
    }

    // If Draft ensure we retrieve the latest published revision.
    if ($entry['status'] == 'Draft') {
      $revisions = $app->helper('revisions')->getList($entry['_id']);
      $published = $app->module('moderation')->getLastPublished($entry['_id'], $revisions);
      if ($published) {
        $entries[$idx] = $published;
      }
      else {
        unset($entries[$idx]);
      }
    }
  }
});

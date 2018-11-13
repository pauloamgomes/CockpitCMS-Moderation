<?php
/**
 * @file
 * Implements moderation related actions on cockpit collections.
 */

$app->on('collections.find.before', function ($name, &$options) use ($app) {
  // Get the collection.
  $collection = $this->module('collections')->collection($name);
  // Exclude on unpublished state.
  foreach ($collection['fields'] as $field) {
    if ($field['name'] == 'status') {
      $options['filter']['$and'] = [
         ["{$field['name']}" => ['$exists' => TRUE]],
         ["{$field['name']}" => ['$ne' => 'Unpublished']],
      ];
      break;
    }
  }
});

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

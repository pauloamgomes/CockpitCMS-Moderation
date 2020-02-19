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
      $field_name = $field_name_lang = $field['name'];
      if ($field['localize'] && $lang = $app->param("lang", false)) {
        $field_name_lang .= "_{$lang}";
      }

      $options['filter']['$and'][] = [$field_name_lang => ['$exists' => TRUE]];
      $options['filter']['$and'][] = [$field_name_lang => ['$ne' => 'Unpublished']];
      break;
    }
  }

  if (!isset($field_name)) {
    return;
  }

  // If we are using filter/fields we need to use original field name.
  if (!empty($options['fields'])) {
    $options['fields'][$field_name] = 1;
  }

  $app->trigger("moderation.find.before", [$name, &$options]);
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
  $field = $app->module('moderation')->getModerationField($name);

  if (!$field) {
    return;
  }

  $lang = $app->param('lang', FALSE);
  $moderation_field = $field['name'];
  $localize = $field['localize'] ?? FALSE;
  $populate = $app->param('populate', 1);

  foreach ($entries as $idx => $entry) {
    if (!isset($entry[$moderation_field])) {
      continue;
    }

    // If Draft ensure we retrieve the latest published revision.
    // Please note that the entry being checked has already been thru lang filtering.
    if ($entry[$moderation_field] == 'Draft') {
      $revisions = $app->helper('revisions')->getList($entry['_id']);

      if ($lang && $localize) {
        $moderation_field .= "_{$lang}";
      }
      // However, this has not been filtered:
      $published = $app->module('moderation')->getLastPublished($entry['_id'], $moderation_field, $revisions);

      if ($published) {
        $published = $app->module('moderation')->removeLangSuffix($name, $published, $lang);
        $published = array_merge($entry, array_intersect_key($published, $entry));
        $published = [$published];
        $populated = cockpit_populate_collection($published, $populate);
        $published = current($populated);
        $entries[$idx] = $published;
      }
      else {
        unset($entries[$idx]);
      }
    }
  }
  // Rebuild array indices.
  $entries = array_values($entries);
});

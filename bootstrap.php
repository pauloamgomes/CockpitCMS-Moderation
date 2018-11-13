<?php

/**
 * @file
 * Cockpit Addon Bootstrap file.
 */

/**
 * Image Style module functions.
 */
$this->module('moderation')->extend([

  'getLastPublished' => function ($id, array $revisions = []) {
    $revisons = array_reverse($revisions);

    foreach ($revisions as $revision) {
      // If we have an Unpublished version before draft entry is ignored.
      if ($revision['data']['status'] === 'Unpublished') {
        return FALSE;
      }
      // If we have at least one Published revision.
      // And we don't have an unpublished one before we return the entry.
      if ($revision['data']['status'] === 'Published') {
        return $revision['data'];
      }
    }

    // In all other cases (no revisions, only drafts we ignore the entry).
    return FALSE;
  },

  'validateToken' => function($token) {
    $keys = $this->app->module('cockpit')->loadApiKeys();
    if (!empty($keys['moderation']) && $keys['moderation'] === $token) {
      return TRUE;
    }
    return FALSE;
  },

  'isEnabled' => function($name) {
    $collection = $this->app->module('collections')->collection($name);
    if ($collection && !empty($collection['fields'])) {
      foreach ($collection['fields'] as $field) {
        if ($field['type'] === 'moderation') {
          return TRUE;
        }
      }
    }
    return FALSE;
  },

  'saveSettings' => function($settings) {
    $keys = $this->app->module('cockpit')->loadApiKeys();
    $keys['moderation'] = $settings['key'];

    return ['success' => $this->app->module('cockpit')->saveApiKeys($keys)];
  },

]);

// Incldude admin.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
 include_once __DIR__ . '/admin.php';
}

// Include actions.
if (COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/actions.php';
}

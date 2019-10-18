<?php

/**
 * @file
 * Cockpit Addon Bootstrap file.
 */

/**
 * Moderation addon functions.
 */
$this->module('moderation')->extend([

  'getLastPublished' => function ($id, $moderation_field, array $revisions = []) {
    $revisons = array_reverse($revisions);

    foreach ($revisions as $revision) {

      // If we have an Unpublished version before draft entry is ignored.
      if ($revision['data'][$moderation_field] === 'Unpublished') {
        return FALSE;
      }

      // If we have at least one Published revision.
      // And we don't have an unpublished one before we return the entry.
      if ($revision['data'][$moderation_field] === 'Published') {
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

  'getModerationField' => function($name) {
    $collection = $this->app->module('collections')->collection($name);
    if ($collection && !empty($collection['fields'])) {
      foreach ($collection['fields'] as $field) {
        if ($field['type'] === 'moderation') {
          return $field['name'];
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

  'setSchedule' => function($data) {
    $id = $data['id'];
    $lang = $data['lang'] ?? "";

    $user = $this->app->module('cockpit')->getUser();

    $existing = $this->app->storage->findOne('moderation/schedule', ['_oid' => $id, 'lang' => $lang]);

    $entry = [
      '_oid' => trim($id),
      'schedule' => $data['schedule'],
      '_lang' => trim($data['lang']),
      '_creator' => $user['_id'] ?? NULL,
      '_modified' => time()
    ];

    if ($existing) {
      $entry['_id'] = $existing['_id'];
      $this->app->storage->save('moderation/schedule', $entry);
    }
    else {
      $this->app->storage->insert('moderation/schedule', $entry);
    }

    return $entry;
  },

  'getSchedule' => function($data) {
    $id = $data['id'];
    $lang = $data['lang'];
    return $this->app->storage->findOne('moderation/schedule', ['_oid' => $id, '_lang' => $lang]);
  },

  'removeSchedule' => function($data) {
    $id = $data['id'];
    $lang = $data['lang'];
    return $this->app->storage->remove('moderation/schedule', ['_oid' => $id, '_lang' => $lang]);
  },

]);

// Incldude admin.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/admin.php';
}

// Include actions.
if (COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/actions.php';
  include_once __DIR__ . '/cockpitql.php';
}

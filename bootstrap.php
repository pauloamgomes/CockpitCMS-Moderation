<?php
/**
 * Cockpit moderation addon
 *
 * @author  Paulo Gomes
 * @package CockpitCMS-Moderation
 * @license MIT
 *
 * @source  https://github.com/pauloamgomes/CockpitCMS-Moderation
 * @see     { README.md } for usage info.
 */

/**
 * Moderation addon functions.
 */
$this->module('moderation')->extend([

  'getLastPublished' => function ($moderation_field, array $revisions = []) {
    $revisions = array_reverse($revisions);

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

  'getModerationField' => function($type, $name) {
    if ($type === "collection") {
      $entry = $this->app->module('collections')->collection($name);
    }
    else {
      $entry = $this->app->module('singletons')->singleton($name);
    }
    if ($collection && !empty($collection['fields'])) {
      foreach ($collection['fields'] as $field) {
        if ($field['type'] === 'moderation') {
          return $field;
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

  'removeCollectionLangSuffix' => function($name, $entry, $lang, $ignoreDefaultFallback) {
    if ($lang) {

      $collection = $this->app->module('collections')->collection($name);

      foreach ($collection['fields'] as $field) {

        if ($field['localize']) {
          $fieldName = $field['name'];
          $suffixedFieldName = $fieldName."_$lang";

          if (
            isset($entry[$suffixedFieldName]) &&
            $entry[$suffixedFieldName] !== ''
          ) {
            $entry[$fieldName] = $entry[$suffixedFieldName];

            if (isset($entry["{$suffixedFieldName}_slug"]) && $entry["{$suffixedFieldName}_slug"] !== '') {
              $entry["{$fieldName}_slug"] = $entry["{$suffixedFieldName}_slug"];
            }
          } elseif (
            $ignoreDefaultFallback === true ||
            (
              is_array($ignoreDefaultFallback) &&
              in_array($fieldName, $ignoreDefaultFallback)
            )
          ) {
            $entry[$fieldName] = null;
          }
        }
      }
    }
    return $entry;
  },

  'removeSingletonLangSuffix' => function($singleton, $published, $lang, $ignoreDefaultFallback) {
    if ($lang) {

      foreach ($singleton['fields'] as $field) {

        if ($field['localize']) {
          $fieldName = $field['name'];
          $suffixedFieldName = $fieldName."_$lang";

          if (
            isset($published[$suffixedFieldName]) &&
            $published[$suffixedFieldName] !== ''
          ) {
            $published[$fieldName] = $published[$suffixedFieldName];

            if (isset($published["{$suffixedFieldName}_slug"]) && $published["{$suffixedFieldName}_slug"] !== '') {
              $published["{$fieldName}_slug"] = $published["{$suffixedFieldName}_slug"];
            }
          } elseif (
            $ignoreDefaultFallback === true ||
            (
              is_array($ignoreDefaultFallback) &&
              in_array($fieldName, $ignoreDefaultFallback)
            )
          ) {
            $published[$fieldName] = null;
          }
        }
      }
    }
    return $published;
  },

  'setSchedule' => function(array $data) {
    $id = $data['id'];
    $lang = $data['lang'] ?? "";

    $user = $this->app->module('cockpit')->getUser();

    $existing = $this->app->storage->findOne('moderation/schedule', ['_oid' => $id, 'lang' => $lang]);

    if (isset($data['collection']) {
        $type = '_collection';
        $scheduleData = $data['collection'];
    }
    else {
        $type = '_singleton';
        $scheduleData = $data['singleton'];
    }

    $entry = [
      '_oid' => trim($id),
      'schedule' => $data['schedule'],
      '_field' => $data['field'],
      $type => $scheduleData,
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

  'getSchedule' => function(array $data) {
    $filter = ['_oid' => $data['id'], '_lang' => $data['lang']];
    return $this->app->storage->findOne('moderation/schedule', $filter);
  },

  'removeSchedule' => function(array $data) {
    $id = $data['id'];
    $lang = $data['lang'];
    return $this->app->storage->remove('moderation/schedule', ['_oid' => $id, '_lang' => $lang]);
  },

  'getLastPublishedStatus' => function (array $params) {
    $revisions = $this->app->helper('revisions')->getList($params['id']);
    if ($revisions) {
      if (isset($params['collection'])) {
        $moderationField = $this->getModerationField('collection', $params['collection']);
      }
      else {
        $moderationField = $this->getModerationField('singleton', $params['singleton']);
      }
      if ($moderationField) {
        $field = $moderationField['name'];
        if ($moderationField['localize'] && $params['lang']) {
          $field .= "_{$params['lang']}";
        }
        foreach ($revisions as $revision) {
          if ($revision['data'][$field] != "Draft") {
            return $revision['data'][$field];
          }
        }
      }
    }
    return "Unpublished";
  },

]);

// Include admin.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/admin.php';
}

// Include actions.
if (COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/actions.php';
  include_once __DIR__ . '/cockpitql.php';
  $this->on('cockpit.rest.init', function ($routes) {
    $routes['schedule'] = 'Moderation\\Controller\\RestApi';
  });
}

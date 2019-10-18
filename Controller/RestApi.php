<?php

namespace Moderation\Controller;

use \LimeExtra\Controller;

/**
 * RestApi class for handling moderation/scheduling actions.
 */
class RestApi extends Controller {

  protected $moderation = [
    'Publish' => 'Published',
    'Unpublish' => 'Unpublished',
  ];

  /**
   * Run schedulling.
   */
  public function list() {
    $range = $this->param('range', 10);
    $results = (array) $this->app->storage->find('moderation/schedule');
    $outdated = [];
    $scheduled = [];
    $active = [];
    $ago = strtotime("-{$range} minutes");
    $now = time();
    foreach ($results as $result) {
      $time = strtotime("{$result['schedule']['date']} {$result['schedule']['time']}");
      if ($time < $ago) {
        $outdated[] = $result;
      }
      elseif ($time > $now) {
        $scheduled[] = $result;
      }
      else {
        $active[] = $result;
      }
    }
    return [
      'outdated' => $outdated,
      'active' => $active,
      'scheduled' => $scheduled,
    ];
  }

  /**
   * Run schedulling.
   */
  public function run() {
    $results = $this->list();
    $processed = [];
    $collections = [];
    $entries = [];
    foreach ($results['active'] as $data) {
      $type = $data['schedule']['type'];
      if (!isset($collections[$data['_collection']])) {
        $collection = $this->app->module('collections')->collection($data['_collection']);
        $collections[$data['_collection']] = $collection;
      }
      else {
        $collection = $collections[$data['_collection']];
      }

      $entry = (array) $this->app->storage->findOne("collections/{$collection['_id']}", ['_id' => $data['_oid']]);
      $field = $data['_field'];
      if ($data['_lang']) {
        $field .= "_{$data['_lang']}";
      }
      if (isset($entry[$field]) && isset($this->moderation[$type])) {
        $old_status = $entry[$field];
        $entry[$field] = $this->moderation[$type];
        $this->app->module('collections')->save($data['_collection'], $entry, ['revision' => TRUE]);
        $this->app->storage->remove('moderation/schedule', ['_id' => $data['_id']]);
        $processed[] = [
          '_id' => $entry['_id'],
          'old_status' => $old_status,
          'new_status' => $entry[$field],
          'schedule_data' => $data,
        ];
      }
    }
    return $processed;
  }

}

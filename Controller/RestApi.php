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
   * Run scheduling.
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
      $time = strtotime("{$result['schedule']['date']}T{$result['schedule']['time']}Z");
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
   * Run scheduling.
   */
  public function run() {
    $results = $this->list();
    $processed = [];
    $data = [];
    $entries = [];
    foreach ($results['active'] as $result) {
      $type = $result['schedule']['type'];
      if (isset($result['_collection'])) {
        if (isset($data[$result['_collection']])) {
          $item = $data[$result['_collection']];
        }
        else {
          $item = $this->app->module('collections')->collection($result['_collection']);
          $data[$result['_collection']] = $item;
        }
        $entry = (array) $this->app->storage->findOne("collections/{$item['_id']}", ['_id' => $result['_oid']]);
      }
      else {
        if (isset($data[$result['_singleton']])) {
          $item = $data[$result['_singleton']];
        }
        else {
          $item = $this->app->module('singletons')->singleton($result['_singleton']);
          $data[$result['_singleton']] = $item;
        }
        $entry = (array) $this->app->storage->findOne('singletons', ['filter' => ['_id' => $result['_oid']]);
      }

      $field = $data['_field'];
      if ($data['_lang']) {
        $field .= "_{$data['_lang']}";
      }
      if (isset($entry[$field]) && isset($this->moderation[$type])) {
        $old_status = $entry[$field];
        $entry[$field] = $this->moderation[$type];
        if (isset($result['_collection'])) {
          $this->app->module('collections')->save($result['_collection'], $entry, ['revision' => TRUE]);
        }
        else {
          $this->app->module('singletons')->saveData($result['_singleton'], $entry, ['revision' => TRUE]);
        }
        $this->app->storage->remove('moderation/schedule', ['_id' => $result['_id']]);
        $processed[] = [
          '_id' => $entry['_id'],
          'old_status' => $old_status,
          'new_status' => $entry[$field],
          'schedule_data' => $result,
        ];
      }
    }
    return $processed;
  }

}

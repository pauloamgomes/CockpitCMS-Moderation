<?php

namespace Moderation\Controller;

use \Cockpit\AuthController;

/**
 * Admin controller class.
 */
class Admin extends AuthController {

  /**
   * Default index controller.
   */
  public function index() {
    if (!$this->app->module('cockpit')->hasaccess('moderation', 'manage')) {
      return FALSE;
    }

    $keys = $this->app->module('cockpit')->loadApiKeys();

    $key = $keys['moderation'] ?? '';

    return $this->render('moderation:views/settings/index.php', ['key' => $key]);
  }

  public function save() {
    $key = $this->param('key', false);

    if (!$key) {
      return false;
    }

    $keys = $this->app->module('cockpit')->loadApiKeys();
    $keys['moderation'] = $keys;

    return ['success' => $this->app->module('cockpit')->saveApiKeys($keys)];

  }

}

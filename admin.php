<?php

// Module ACL definitions.
$this("acl")->addResource('moderation', [
  'manage',
]);

$this->on('collections.entry.aside', function() {
  $this->renderView("moderation:views/partials/entry-aside.php");
});

$app->on('admin.init', function () {
  $this->helper('admin')->addAssets('moderation:assets/field-moderation.tag');

  // Bind admin routes.
  $this->bindClass('Moderation\\Controller\\Admin', 'settings/moderation');
});

/*
 * add menu entry if the user has access to group stuff
 */
$this->on('cockpit.view.settings.item', function () use ($app) {
  if ($app->module('cockpit')->hasaccess('moderation', 'manage')) {
     $this->renderView("moderation:views/partials/settings.php");
  }
});


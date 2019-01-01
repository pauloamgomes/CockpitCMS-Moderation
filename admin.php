<?php

// Module ACL definitions.
$this("acl")->addResource('moderation', [
  'manage',
]);

/**
 * Add moderation markup to collections sidebar.
 */
$this->on('collections.entry.aside', function() {
  $this->renderView("moderation:views/partials/entry-aside.php");
});

/**
 * Initialize addon for admin pages.
 */
$app->on('admin.init', function () {
  // Add field tag.
  $this->helper('admin')->addAssets('moderation:assets/field-moderation.tag');

  // Bind admin routes.
  $this->bindClass('Moderation\\Controller\\Admin', 'settings/moderation');
});

/*
 * Add menu entry if the user has access to group stuff.
 */
$this->on('cockpit.view.settings.item', function () use ($app) {
  if ($app->module('cockpit')->hasaccess('moderation', 'manage')) {
     $this->renderView("moderation:views/partials/settings.php");
  }
});

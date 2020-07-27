<?php

// Module ACL definitions.
$this("acl")->addResource('moderation', [
  'manage',
  'publish',
  'unpublish',
  'schedule',
]);

/**
 * Add moderation markup to collections sidebar.
 */
$this->on('collections.entry.aside', function($name) use ($app) {
  $canSchedule = $app->module('cockpit')->hasaccess('moderation', ['manage', 'schedule']);
  $settings = $this->retrieve('config/moderation', ['schedule' => []]);

  $scheduleEnabled = $canSchedule && isset($settings['schedule']) && ($settings['schedule'] === '*' || in_array($name, $settings['schedule']));

  $this->renderView("moderation:views/partials/entry-aside.php", ['enabled' => $scheduleEnabled]);
});

/**
 * Add moderation markup to singletons sidebar.
 */
$this->on('singletons.form.aside', function($name) use ($app) {
  $this->renderView("moderation:views/partials/singleton-aside.php");
});


/**
 * Initialize addon for admin pages.
 */
$app->on('admin.init', function () use ($app)  {
  // Check moderation capabilities for the user.
  $canPublish = $app->module('cockpit')->hasaccess('moderation', ['manage', 'publish']);
  $canUnpublish = $app->module('cockpit')->hasaccess('moderation', ['manage', 'unpublish']);

  $this('admin')->data["extract/moderation"] = [
    'canPublish' => $canPublish,
    'canUnpublish' => $canUnpublish,
  ];

  // Add field tag.
  $this->helper('admin')->addAssets('moderation:assets/field-moderation.tag');
  $this->helper('admin')->addAssets('moderation:assets/moderation.css');
  $this->helper('admin')->addAssets('moderation:assets/moderation.js');
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

/**
 * Provide modififications on the preview url (Helpers addon).
 */
$this->on('helpers.preview.url', function(&$preview) use ($app) {
  $keys = $app->module('cockpit')->loadApiKeys();
  $preview['token'] = $keys['moderation'] ?? '';
});


<?php

use GraphQL\Type\Definition\Type;

$app->on('cockpitql.type.moderation', function ($field, &$def) use ($app) {
  $def['type'] = Type::string();
});

// API includes.
if (COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/cockpitql.php';
}

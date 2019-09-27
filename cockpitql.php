<?php

use GraphQL\Type\Definition\Type;

$app->on('cockpitql.type.moderation', function ($field, &$def) use ($app) {
  $def['type'] = Type::string();
});

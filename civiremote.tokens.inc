<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\UserInterface;

/**
 * Implements hook_token_info().
 */
function civiremote_token_info() {
  // Current user tokens.
  $info['tokens']['user']['civiremote-id'] = [
    'name' => t('CiviRemote ID'),
    'description' => t('The unique CiviRemote ID for the user in the connected CiviCRM.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function civiremote_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type == 'user' && !empty($data['user'])) {
    /** @var UserInterface $account */
    $account = $data['user'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'civiremote-id':
          $replacements[$original] = $account->get('civiremote_id')->value;
          break;
      }
    }
  }

  return $replacements;
}

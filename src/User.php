<?php

namespace Drupal\civiremote;

use Drupal\user\UserInterface;

/**
 * Class User
 *
 * @package Drupal\civiremote
 */
class User {

  /**
   * Act on User entity creation.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see civiremote_entity_insert()
   *
   */
  public static function create(UserInterface $user) {
    $config = \Drupal::config('civiremote.settings');
    if ($config->get('acquire_civiremote_id')) {
      self::matchContact($user);
    }
  }

  /**
   * Match a CiviCRM contact and set the returned CiviRemote ID on the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity object.
   * @param string $prefix
   *   A prefix to be added to the CiviRemote ID by the CiviRemote API.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function matchContact(UserInterface $user, $prefix = '') {
    /* @var \Drupal\civiremote\CiviMRF $cmrf */
    $cmrf = \Drupal::service('civiremote.cmrf');
    $config = \Drupal::config('civiremote.settings');

    // Use base URL as default key prefix.
    if (empty($prefix)) {
      global $base_url;
      $base_url_parts = parse_url($base_url);
      $prefix = $base_url_parts['host'];
      $params = [
        'key_prefix' => $prefix,
      ];
    }

    // Map user properties/fields to params.
    foreach ($config->get('match_contact_mapping') as $mapping) {
      $params[$mapping['contact_field']] = $user->{$mapping['user_field']}->value;
    }

    // Send API call and store the returned CiviRemote ID.
    if ($civiremote_id = $cmrf->matchContact($params)) {
      $user->set('civiremote_id', $civiremote_id);
      $user->save();
    }
  }

  /**
   * @param \Drupal\user\UserInterface $user
   */
  public static function synchroniseRoles(UserInterface $user) {
    if (!empty($civiremote_id = $user->get('civiremote_id'))) {
      /* @var \Drupal\civiremote\CiviMRF $cmrf */
      $cmrf = \Drupal::service('civiremote.cmrf');
      $params = [
        'remote_contact_id' => $civiremote_id,
      ];
      $roles = $cmrf->getRoles($params);
      // TODO: Check for existence of roles and (un-)assign them to/from the user.
    }
  }

}

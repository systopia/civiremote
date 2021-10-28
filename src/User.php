<?php
/*------------------------------------------------------------+
| CiviRemote - CiviCRM Remote Integration                     |
| Copyright (C) 2020 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

namespace Drupal\civiremote;

use Drupal;
use Drupal\Core\Entity;
use Drupal\user\UserInterface;
use Drupal\user\Entity\Role;

/**
 * Class User
 *
 * @package Drupal\civiremote
 */
class User {

  /**
   * Act on User entity creation.
   *
   * @param UserInterface $user
   *   The User entity object.
   *
   * @throws Entity\EntityStorageException
   *
   * @see civiremote_entity_insert()
   *
   */
  public static function create(UserInterface $user) {
    $config = Drupal::config('civiremote.settings');
    if ($config->get('acquire_civiremote_id')) {
      self::matchContact($user);
    }
  }

  /**
   * Match a CiviCRM contact and set the returned CiviRemote ID on the user.
   *
   * @param UserInterface $user
   *   The User entity object.
   * @param string $prefix
   *   A prefix to be added to the CiviRemote ID by the CiviRemote API.
   *
   * @throws Entity\EntityStorageException
   */
  public static function matchContact(UserInterface $user, $prefix = '') {
    /* @var \Drupal\civiremote\CiviMRF $cmrf */
    $cmrf = Drupal::service('civiremote.cmrf');
    $config = Drupal::config('civiremote.settings');
    $params = [];

    // Use base URL as default key prefix.
    if (empty($prefix)) {
      global $base_url;
      $base_url_parts = parse_url($base_url);
      $prefix = $base_url_parts['host'];
      $params['key_prefix'] = $prefix;
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
   * Synchronises CiviRemote roles retrieved from CiviCRM with the Drupal user's
   * CiviRemote roles and creates CiviRemote roles unknown to Drupal.
   *
   * A CiviRemote user role is being identified by a "civiremote_" prefix in the
   * user roles ID.
   *
   * @param UserInterface $user
   *   The Drupal user object to synchronise user roles for.
   *
   * @throws Entity\EntityStorageException
   *   When updating the user object fails.
   */
  public static function synchroniseRoles(UserInterface $user) {
    if (!empty($civiremote_id = $user->get('civiremote_id')->value)) {
      // Fetch all CiviRemote roles for the current user from CiviCRM.
      /* @var \Drupal\civiremote\CiviMRF $cmrf */
      $cmrf = Drupal::service('civiremote.cmrf');
      $params = [
        'remote_contact_id' => $civiremote_id,
      ];
      $remote_roles = [];
      foreach ($cmrf->getRoles($params) as $remote_role_id => $remote_role_label) {
        // Transform to valid role machine name.
        $remote_role_id = preg_replace('/[^a-z0-9_]+/', '_', $remote_role_id);
        $remote_roles['civiremote_' . $remote_role_id] = 'CiviRemote: ' . $remote_role_label;
      }

      // Fetch all CiviRemote roles known to Drupal.
      $all_roles = user_role_names(TRUE);
      $civiremote_roles = [];
      foreach ($all_roles as $id => $label) {
        if (strpos($id, 'civiremote_') === 0) {
          $civiremote_roles[$id] = $label;
        }
      }

      // Fetch the user's current CiviRemote roles.
      $user_civiremote_roles = [];
      foreach ($user->getRoles() as $id) {
        if (strpos($id, 'civiremote_') === 0) {
          $user_civiremote_roles[$id] = $all_roles[$id];
        }
      }

      // Create CiviRemote roles unknown to Drupal.
      foreach (array_diff_key($remote_roles, $civiremote_roles) as $id => $label) {
        $role = Role::create(['id' => $id, 'label' => $label]);
        $role->save();
      }

      // Un-assign old CiviRemote roles from user.
      foreach (array_diff_key($user_civiremote_roles, $remote_roles) as $id => $label) {
        $user->removeRole($id);
        $user->save();
      }

      // Assign new roles to user.
      foreach (array_keys(array_diff($remote_roles, $user_civiremote_roles)) as $new_role) {
        $user->addRole($new_role);
        $user->save();
      }
    }
  }

}

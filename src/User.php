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
   * @see \civiremote_entity_insert()
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity object.
   */
  public static function create(UserInterface $user) {
    $config = \Drupal::config('civiremote.settings');
    if ($config->get('acquire_civiremote_id')) {
      /* @var \Drupal\civiremote\CiviMRF $cmrf */
      $cmrf = \Drupal::service('civiremote.cmrf');
      // TODO: Map user properties/fields to params.
      $params = [
        'email' => $user->getEmail(),
      ];
      if ($civiremote_id = $cmrf->matchContact($params)) {
        $user->set('civiremote_id', $civiremote_id);
        $user->save();
      }
    }
  }

}

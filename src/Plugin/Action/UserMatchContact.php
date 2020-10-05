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

namespace Drupal\civiremote\Plugin\Action;


use Drupal\civiremote\User;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Matches a user with a CiviCRM contact.
 *
 * @Action(
 *   id = "civiremote_match_contact_action",
 *   label = @Translation("CiviRemote: Match contact(s)"),
 *   type = "user"
 * )
 */
class UserMatchContact extends ActionBase {

  /**
   * @inheritDoc
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var UserInterface $object */
    return
      $object->access('update', $account, $return_as_object)
      && $account->hasPermission('administer civiremote');
  }

  /**
   * @inheritDoc
   */
  public function execute(UserInterface $user = NULL) {
    if (!$civiremote_id = $user->get('civiremote_id')->getValue()) {
      $config = \Drupal::config('civiremote.settings');
      if ($config->get('acquire_civiremote_id')) {
        User::matchContact($user);
      }
    }
  }

}

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
 * Synchronises a user's roles with its CiviRemote roles in CiviCRM.
 *
 * @Action(
 *   id = "civiremote_synchronise_roles_action",
 *   label = @Translation("CiviRemote: Synchronise CiviRemote roles"),
 *   type = "user"
 * )
 */
class UserSynchroniseRoles extends ActionBase {

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
    User::synchroniseRoles($user);
  }

}

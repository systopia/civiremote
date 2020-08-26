<?php


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
    $config = \Drupal::config('civiremote.settings');
    if ($config->get('acquire_civiremote_id')) {
      User::matchContact($user);
    }
  }

}

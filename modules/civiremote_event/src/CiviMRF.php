<?php


namespace Drupal\civiremote_event;

use Drupal\civiremote;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Exception;

/**
 * Class CiviMRF
 *
 * @package Drupal\civiremote_event
 */
class CiviMRF extends civiremote\CiviMRF {

  /**
   * @param $event_id
   *   The remote event ID.
   * @param AccountInterface $account
   *   the currently loged-in user account.
   *
   * @return array
   *   The remote event.
   *
   * @throws \Exception
   *   When the event could not be retrieved.
   */
  public function getEvent($event_id, AccountInterface $account) {
    $current_user = User::load($account->id());
    $params = [
      'remote_contact_id' => $current_user->get('civiremote_id')->value,
      'id' => $event_id,
    ];
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteEvent',
      'getsingle',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    if (!isset($reply['id'])) {
      throw new Exception(t('Could not retrieve remote event.'));
    }

    return $reply;
  }

  public function validateEventRegistration($event_id, $profile, $params) {
    $current_user = User::load(\Drupal::currentUser()->id());
    $params = array_merge($params, [
      'remote_contact_id' => $current_user->get('civiremote_id')->value,
      'id' => $event_id,
      'profile' => $profile,
    ]);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'validate',
      $params,
      []
    );
    $this->core->executeCall($call);
    return $call->getReply();
  }

}

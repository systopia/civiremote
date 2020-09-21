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
    $params = [
      'id' => $event_id,
    ];
    self::addRemoteContactId($params);
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

  public function getRegistrationForm($event_id, $profile) {
    $params = [
      'event_id' => $event_id,
      'profile' => $profile,
    ];
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteEvent',
      'get_registration_form',
      $params,
      []
    );
    $this->core->executeCall($call);
    if ($call->getStatus() !== $call::STATUS_DONE) {
      throw new Exception(t('Retrieving registration form failed.'));
    }
    $reply = $call->getReply();
    return $reply['values'];
  }

  public function validateEventRegistration($event_id, $profile, $params) {
    self::addRemoteContactId($params);
    $params = array_merge($params, [
      'event_id' => $event_id,
      'profile' => $profile,
    ]);
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'validate',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    if ($call->getStatus() !== $call::STATUS_DONE && empty($reply['values'])) {
      $errors = [t('Validation failed')];
    }
    else {
      $errors = $reply['values'];
    }
    return $errors;
  }

  public function submitEventRegistration($event_id, $profile, $params) {
    $params = array_merge($params, [
      'event_id' => $event_id,
      'profile' => $profile,
    ]);
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'submit',
      $params,
      []
    );
    $this->core->executeCall($call);
    if ($call->getStatus() !== $call::STATUS_DONE) {
      throw new Exception(t('Registration failed.'));
    }
    $reply = $call->getReply();
    return $reply['values'];
  }

  public static function addRemoteContactId(&$params) {
    $current_user = User::load(\Drupal::currentUser()->id());
    $params['remote_contact_id'] = $current_user->get('civiremote_id')->value;
  }

}

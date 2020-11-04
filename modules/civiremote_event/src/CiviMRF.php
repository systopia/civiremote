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

namespace Drupal\civiremote_event;

use Drupal;
use Drupal\civiremote;
use Drupal\user\Entity\User;
use Exception;
use stdClass;

/**
 * CiviMRF implementations for CiviRemote events.
 *
 * @package Drupal\civiremote_event
 */
class CiviMRF extends civiremote\CiviMRF {

  /**
   * Retrieves a remote event with a given ID.
   *
   * @param int $event_id
   *   The remote event ID.
   *
   * @return stdClass
   *   The remote event.
   *
   * @throws Exception
   *   When the event could not be retrieved.
   */
  public function getEvent($event_id) {
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

    return (object) $reply;
  }

  /**
   * Retrieves a remote event by a given token.
   *
   * @param $registration_token
   *   The registration token to retrieve the remote event for.
   *
   * @return int
   *   The event ID.
   *
   * @throws Exception
   *   When the event could not be retrieved.
   */
  public function getEventFromToken($registration_token) {
    $params = [
      'remote_registration_token' => $registration_token,
      'probe' => 1,
    ];
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'cancel',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    if (!isset($reply['event_id'])) {
      throw new Exception(t('Could not retrieve remote event.'));
    }

    return (int) $reply['event_id'];
  }

  /**
   * Retrieves the registration form definition of a remote event with a given
   * event ID for a specific profile, or with a remote event token.
   *
   * @param int $event_id
   *   The remote event ID.
   * @param string $profile
   *   The remote event profile name.
   * @param string $remote_token
   *   The remote event token.
   * @param string $context
   *   The action the form definition should be retrieved for.
   *
   * @return array
   *   The remote event registration form definition.
   *
   * @throws Exception
   *   When the registration form definition could not be retrieved.
   */
  public function getRegistrationForm($event_id, $profile, $remote_token = NULL, $context = 'create') {
    $params = [
      'event_id' => $event_id,
      'profile' => $profile,
      'token' => $remote_token,
      'context' => $context,
    ];
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'get_form',
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

  /**
   * Validates a remote event registration submission .
   *
   * @param int $event_id
   *   The remote event ID.
   * @param string $profile
   *   The remote event profile name.
   * @param string $remote_token
   *   The remote token.
   * @param array $params
   *   Additional parameters to send to the API.
   *
   * @return array
   *   The errors that occurred during the remote event registration validation.
   */
  public function validateEventRegistration($event_id, $profile, $remote_token = NULL, $params = []) {
    self::addRemoteContactId($params);
    $params = array_merge($params, [
      'event_id' => $event_id,
      'profile' => $profile,
      'token' => $remote_token
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

  /**
   * Submits a remote event registration submission.
   *
   * @param int $event_id
   *   The remote event ID.
   * @param string $profile
   *   The remote event profile name.
   * @param string $remote_token
   *   The remote token.
   * @param array $params
   *   Additional parameters to send to the API.
   *
   * @return array
   *   The API response of the remote event registration.
   *
   * @throws Exception
   *   When the remote event registration could not be submitted.
   */
  public function createEventRegistration($event_id, $profile, $remote_token = NULL, $params = []) {
    $params = array_merge($params, [
      'event_id' => $event_id,
      'profile' => $profile,
      'token' => $remote_token
    ]);
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'create',
      $params,
      []
    );
    $this->core->executeCall($call);
    if ($call->getStatus() !== $call::STATUS_DONE) {
      throw new Exception(t('The event registration failed.'));
    }
    $reply = $call->getReply();
    return $reply['values'];
  }

  /**
   * Cancels a remote event registration.
   *
   * @param int $event_id
   *   The remote event ID.
   *
   * @return array
   *   The API response of the remote event registration cancellation.
   *
   * @throws Exception
   *   When the remote event registration could not be cancelled.
   */
  public function cancelEventRegistration($event_id) {
    $params = [
      'event_id' => $event_id,
    ];
    self::addRemoteContactId($params);
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteParticipant',
      'cancel',
      $params,
      []
    );
    $this->core->executeCall($call);
    if ($call->getStatus() !== $call::STATUS_DONE) {
      throw new Exception(t('The event registration cancellation failed.'));
    }
    $reply = $call->getReply();
    return $reply['values'];
  }

  /**
   * Adds the currently logged-in user's CiviRemote ID to the given parameters
   * array.
   *
   * @param array $params
   *   The parameters array to add the CiviRemote ID to.
   */
  public static function addRemoteContactId(&$params) {
    /* @var User $current_user */
    $current_user = User::load(Drupal::currentUser()->id());
    $params['remote_contact_id'] = $current_user->get('civiremote_id')->value;
  }

}

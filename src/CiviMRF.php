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
use Drupal\cmrf_core\Core;
use Drupal\civiremote\Event\ConnectorEvent;
use \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class CiviMRF
 *
 * @package Drupal\civiremote
 */
class CiviMRF {

  /**
   * @var Core
   */
  public $core;

  public function __construct(Core $core) {
    $this->core = $core;
  }

  protected function connector(array $context = []) {
    $event = new ConnectorEvent(
      Drupal::config('civiremote.settings')->get('cmrf_connector'),
      $context
    );
    /* @var ContainerAwareEventDispatcher $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, ConnectorEvent::EVENT_NAME);
    return $event->getConnectorId();
  }

  /**
   * @param $params
   *
   * @return string | false
   *   The CiviRemote ID, or FALSE when no contact could be matched.
   */
  public function matchContact($params) {
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteContact',
      'match',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    return !empty($reply['key']) ? $reply['key'] : FALSE;
  }

  /**
   * @param $params
   *
   * @return array | bool
   *   The CiviRemote roles, or FALSE when synchronising roles failed.
   */
  public function getRoles($params) {
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteContact',
      'get_roles',
      $params,
      []
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    // TODO: Throw an exception when the call failed?
    return $reply['values'] ?? FALSE;
  }

}

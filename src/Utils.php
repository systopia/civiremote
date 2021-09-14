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
use Drupal\Core\Messenger\MessengerInterface;

class Utils {

  /**
   * Retrieves the Drupal messenger severity type for a given CiviRemote API
   * message severity type.
   *
   * @param string $api_severity
   *   A CiviREmote API message severity type.
   *
   * @return string|null
   *   The Drupal messenger severity type, or NULL if the given CiviRemote API
   *   severity type could not be matched.
   */
  public static function messageSeverity($api_severity) {
    $mapping= [
      'status' => MessengerInterface::TYPE_STATUS,
      'warning' => MessengerInterface::TYPE_WARNING,
      'error' => MessengerInterface::TYPE_ERROR,
    ];

    return isset($mapping[$api_severity]) ? $mapping[$api_severity] : NULL;
  }

  /**
   * Displays messages retrieved by the CiviRemote API as Drupal messages.
   *
   * @param $messages
   *   An array of messages as retrieved by the CiviRemote API.
   */
  public static function setMessages($messages) {
    foreach ($messages as $message) {
      Drupal::messenger()->addMessage(
        $message['message'],
        Utils::messageSeverity($message['severity'] ?? 'status')
      );
    }
  }

}

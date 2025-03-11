<?php
/*------------------------------------------------------------+
| CiviRemote - CiviCRM Remote Integration                     |
| Copyright (C) 2025 SYSTOPIA                                 |
| Author: SYSTOPIA GmbH (info@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

namespace Drupal\civiremote_event\Controller;

use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\Controller\ControllerBase;

class MailingListConfirmSubscriptionController extends ControllerBase {

  protected CiviMRF $cmrf;

  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * @phpstan-return array{}
   *
   * @throws \CMRF\Exception\ApiCallFailedException
   */
  public function confirm(string $token): array {
    $result = $this->cmrf->confirmMailingListSubscription($token);
    if ($result['success']) {
      $this->messenger()->addStatus($result['message']);
    }
    else {
      $this->messenger()->addError($result['message']);
    }

    return [];
  }

}

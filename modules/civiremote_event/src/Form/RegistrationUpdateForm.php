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

namespace Drupal\civiremote_event\Form;


use Drupal;
use Drupal\civiremote\Utils;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Exception;
use stdClass;

class RegistrationUpdateForm extends RegisterForm {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'civiremote_event_registration_update_form';
  }

  /**
   * @inheritDoc
   */
  protected function submitCmrf(array $values): array {
    return $this->cmrf->updateEventRegistration(
      $this->event->id,
      $this->profile,
      $this->remote_token,
      $values,
      TRUE
    );
  }

}

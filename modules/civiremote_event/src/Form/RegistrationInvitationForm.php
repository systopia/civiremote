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


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use stdClass;

class RegistrationInvitationForm extends RegistrationUpdateForm {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'civiremote_event_registration_invitation_form';
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   *
   * @param string $remote_token
   *   The remote token.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event, which will have been
    // retrieved using a given remote token in the constructor already.
    return AccessResult::allowedIf(
      !empty($this->event)
      && $this->event->can_edit_registration
    );
  }

}

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

namespace Drupal\civiremote_event\Controller;

use Drupal;
use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use stdClass;

class RegisterFormController extends ControllerBase {

  public function form(stdClass $event = NULL, $profile = NULL) {
    // Use default profile if not given.
    if (!isset($profile) && isset($event)) {
      $profile = $event->default_profile;
    }

    // Try to find our own implementation.
    $form_id = $this->getFormId($profile);

    // Build the form.
    return $this->formBuilder()->getForm($form_id);
  }

  public function title(stdClass $event = NULL, $remote_token = NULL) {
    // If the form is being requested with a token, the event will have been
    // resolved in $remote_token by the EventTokenConverter.
    if ($remote_token) {
      $event = $remote_token;
    }
    return $event->event_title;
  }

  private function getFormId($profile = NULL) {
    // Use generic form ID.
    $form_id = '\Drupal\civiremote_event\Form\RegisterForm';

    // Try to find a profile-specific implementation.
    if ($profile) {
      $profile_form_id = '\Drupal\civiremote_event\Form\RegisterForm\\' . $profile;
      if (
        class_exists($profile_form_id)
        && in_array(RegisterFormInterface::class,class_implements($profile_form_id))
      ) {
        $form_id = $profile_form_id;
      }

      // Use form ID for given profile ID from configuration.
      $profile_form_mapping = Drupal::config('civiremote_event.settings')
        ->get('profile_form_mapping');
      if (!empty($profile_form_mapping)) {
        foreach ($profile_form_mapping as $mapping) {
          if ($mapping['profile_id'] == $profile) {
            $form_id = $mapping['form_id'];
            break;
          }
        }
      }
    }

    return $form_id;
  }

  /**
   * Custom access callback for registration form routes.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   * @param string $remote_token
   *   The remote token to use for retrieving the form.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(stdClass $event = NULL, $profile = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      !empty($event)
      && $event->can_register
      && (
        !isset($profile)
        || in_array(
          $profile,
          explode(',', $event->enabled_profiles)
        )
      )
    );
  }

  /**
   * Custom access callback for the update form routes.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   * @param string $remote_token
   *   The remote token to use for retrieving the form.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function accessUpdate(stdClass $event = NULL, $profile = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      !empty($event)
      && $event->can_edit_registration
      && (
        !isset($profile)
        || in_array(
          $profile,
          explode(',', $event->enabled_update_profiles)
        )
      )
    );
  }

}

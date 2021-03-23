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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    if (
      isset($form['actions']['back'])
      && $form_state->getTriggeringElement()['#value'] == $form['actions']['back']['#value']
    ) {
      // Handle backwards navigation.
      $form_state->set('step', --$step);
      $form_state->setValues($form_state->get('values'));
      $form_state->setRebuild();
    }
    else {
      // Handle forward navigation.
      if ($step == array_search('confirm', $form_state->get('steps'))) {
        // Submit the form values to the CiviRemote Event API.
        $values = $form_state->get('values') ?: [];
        $this->preprocessValues($values);
        try {
          $result = $this->cmrf->updateEventRegistration(
            $this->event->id,
            $this->profile,
            $this->remote_token,
            $values
          );

          // Show messages returned by the API.
          if (!empty($result['status_messages'])) {
            Utils::setMessages($result['status_messages']);
          }

          // Advance to "Thank you" step (when this is no invitation or it has
          // been confirmed).
          if (
            !array_key_exists('confirm', $this->fields)
            || !empty($values['confirm'])
          ) {
            $form_state->set(
              'step',
              array_search('thankyou', $form_state->get('steps'))
            );
            $form_state->setRebuild();
          }
          else {
            // Redirect to target from configuration.
            $config = Drupal::config('civiremote_event.settings');
            /* @var Url $url */
            $url = Drupal::service('path.validator')
              ->getUrlIfValid($config->get('form_redirect_route'));
            $form_state->setRedirect(
              $url->getRouteName(),
              $url->getRouteParameters(),
              $url->getOptions()
            );
          }
        }
        catch (Exception $exception) {
          $form_state->set('error', TRUE);
          Drupal::messenger()->addMessage(
            t('Registration failed, please try again later.'),
            MessengerInterface::TYPE_ERROR
          );
          $form_state->setRebuild();
        }
      }
      else {
        $form_state->cleanValues();
        $values = $form_state->getValues();
        // Advance to next step and rebuild the form.
        $form_state->set('values', $values);
        $form_state->set('step', ++$step);
        $form_state->setRebuild();
      }
    }
  }

  /**
   * Custom access callback for this form's route.
   *
   * Note: The parameters passed in here are not being used, since they have
   * been processed in the constructor already. Instead, class members are being
   * used for deciding about access.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   * @param string $remote_token
   *   The remote token to use for retrieving the form.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event = NULL, $profile = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      !empty($this->event)
      && $this->event->can_edit_registration
      && (
        !isset($this->profile)
        || in_array(
          $this->profile,
          explode(',', $this->event->enabled_update_profiles)
        )
      )
    );
  }

}

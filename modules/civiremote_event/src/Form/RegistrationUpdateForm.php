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
            $values,
            TRUE
          );

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

}

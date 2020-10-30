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

namespace Drupal\civiremote_event\Form\RegisterForm;


use Drupal\civiremote_event\Form\RegisterForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Remote event registration form for the "OneClick" profile.
 *
 * This class basically skips the "form" step, since there are no data to enter.
 * Thus, only the "confirm" and the "thankyou" form steps are being shown.
 *
 * @package Drupal\civiremote_event\Form\RegisterForm
 */
class OneClick extends RegisterForm implements RegisterFormInterface {

  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Add 'form' step when there are fields (e.g. for invitation).

    if (empty($form_state->get('steps'))) {
      $steps = [
        // Confirmation step, right before final submission.
        'confirm',
        // Thank you step, right after final submission.
        'thankyou',
      ];
      $form_state->set('steps', $steps);
      // Initialize with first step.
      $form_state->set('step', 0);
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

}

<?php


namespace Drupal\civiremote_event\Form\RegisterForm;


use Drupal\civiremote_event\Form\RegisterForm;
use Drupal\Core\Form\FormStateInterface;

class OneClick extends RegisterForm implements RegisterFormInterface {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $steps = [
      // Confirmation step, right before final submission.
      'confirm',
      // Thank you step, right after final submission.
      'thankyou',
    ];
    $form_state->set('steps', $steps);
    // Initialize with first step.
    $form_state->set('step', 0);

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

}

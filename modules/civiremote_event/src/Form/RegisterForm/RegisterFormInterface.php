<?php

namespace Drupal\civiremote_event\Form\RegisterForm;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use stdClass;

interface RegisterFormInterface extends FormInterface {

  /**
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state);

}

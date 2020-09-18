<?php


namespace Drupal\civiremote_event\Form\RegisterForm;


use Drupal\civiremote_event\Form\RegisterForm;
use Drupal\Core\Form\FormStateInterface;
use stdClass;

class Standard3 extends RegisterForm implements RegisterFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    stdClass $event = NULL,
    $profile = NULL
  ) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('E-mail address'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register'),
    ];

    return $form;
  }

}

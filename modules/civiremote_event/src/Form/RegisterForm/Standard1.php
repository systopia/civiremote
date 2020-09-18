<?php


namespace Drupal\civiremote_event\Form\RegisterForm;


use Drupal\civiremote_event\Form\RegisterForm;
use Drupal\Core\Form\FormStateInterface;

class Standard1 extends RegisterForm implements RegisterFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#required' => TRUE,
    ];

    return $form;
  }

}

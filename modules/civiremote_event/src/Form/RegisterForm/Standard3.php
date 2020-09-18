<?php


namespace Drupal\civiremote_event\Form\RegisterForm;


use Drupal\civiremote_event\Form\RegisterForm;
use Drupal\Core\Form\FormStateInterface;
use stdClass;

class Standard3 extends RegisterForm implements RegisterFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /**
     * Email, prefix, title, first name, last name, postal address and phone
     */

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#required' => TRUE,
    ];

    $form['prefix_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Prefix'),
      '#options' => [
        1 => 'Mrs.',
        2 => 'Ms.',
        3 => 'Mr.',
        4 => 'Dr.',
      ],
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['formal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Formal title'),
    ];
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
    ];

    $form['street_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street address'),
    ];
    $form['postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code'),
    ];
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
    ];

    return $form;
  }

}

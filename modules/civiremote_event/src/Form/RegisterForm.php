<?php


namespace Drupal\civiremote_event\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

class RegisterForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'civiremote_event_register_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $event_id = NULL, $profile_id = NULL) {
    // TODO: Implement buildForm() method.
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $event_id
   * @param $profile_id
   */
  public function access(AccountInterface $account, $event_id, $profile_id) {
    // TODO: Grant access depending on flags returned by the RemoteEvent.get API.
  }

}
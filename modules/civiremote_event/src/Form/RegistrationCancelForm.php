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
use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegistrationCancelForm extends ConfirmFormBase {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF core service.
   */
  protected $cmrf;

  /**
   * @var stdClass $event
   *   The remote event to build the registration form for.
   */
  protected $event;

  /**
   * @var string $remote_token
   *   The remote event token to use for retrieving information about the
   *   registration form.
   */
  protected $remote_token;

  /**
   * @var array $messages
   *   Status messages to be displayed on the remote event form.
   */
  protected $messages;

  /**
   * RegistrationCancelForm constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF core service.
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies.
     * @var CiviMRF $cmrf
     */
    $cmrf = $container->get('civiremote_event.cmrf');
    return new static(
      $cmrf
    );
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t(
      'Cancel registration for %event_title?',
      ['%event_title' => $this->event->event_title]
    );
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    // TODO: Show configurable description of the event cancellation?
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'civiremote_event_registration_cancel_form';
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit to the RemoteParticipant.cancel API.
    try {
      $result = $this->cmrf->cancelEventRegistration(
        $this->event->id,
        $this->remote_token,
        TRUE
      );
    }
    catch (Exception $exception) {
      $form_state->set('error', TRUE);
      $form_state->setRebuild();
    }

    // Redirect to target from configuration.
    $config = Drupal::config('civiremote_event.settings');
    /* @var Url $url */
    $url = Drupal::service('path.validator')
      ->getUrlIfValid($config->get('form_redirect_route'));
    $form_state->setRedirectUrl($url);
  }

  /**
   * @inheritDoc
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    stdClass $event = NULL,
    string $profile = NULL,
    string $context = NULL,
    string $raw_event_token = NULL,
    array $fields = NULL,
    array $messages = NULL
  ) {
    // Initialize.
    $this->event = $event;
    $this->remote_token = $raw_event_token;
    $this->messages = $messages;

    // Show messages returned by the API.
    if (!empty($this->messages)) {
      Utils::setMessages($this->messages);
    }

    // TODO: Display information about the event registration.

    return parent::buildForm($form, $form_state);
  }
}

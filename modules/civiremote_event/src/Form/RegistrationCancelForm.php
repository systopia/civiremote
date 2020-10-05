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
use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatch;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegistrationCancelForm extends ConfirmFormBase {

  /**
   * @var CiviMRF $cmrf_core
   */
  protected $cmrf;

  /**
   * @var RedirectDestinationInterface $redirect
   */
  protected $redirect;

  /**
   * @var stdClass $event
   */
  protected $event;

  /**
   * @var string $cancel_token
   */
  protected $cancel_token;

  /**
   * RegistrationCancelForm constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF core service.
   * @param RedirectDestinationInterface $redirect
   */
  public function __construct(CiviMRF $cmrf, RedirectDestinationInterface $redirect) {
    $this->cmrf = $cmrf;
    $this->redirect = $redirect;

    // Extract form parameters and set them here so that implementations do not
    // have to care about that.
    $routeMatch = RouteMatch::createFromRequest($this->getRequest());
    $this->cancel_token = $routeMatch->getRawParameter('cancel_token');
    $this->event = $routeMatch->getParameter('event');
    // TODO: Set $this->event from the token (using RemoteParticipant.cancel with probe=1).
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies.
     * @var CiviMRF $cmrf
     * @var RedirectDestinationInterface $redirect
     */
    $cmrf = $container->get('civiremote_event.cmrf');
    $redirect = $container->get('redirect.destination');
    return new static(
      $cmrf,
      $redirect
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
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return $this->redirect->get();
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
      $result = $this->cmrf->cancelEventRegistration($this->event->id);
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

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Display information about the event registration.

    return parent::buildForm($form, $form_state);
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   *
   * @param string $cancel_token
   *   The cancellation token.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event = NULL, $cancel_token = NULL) {
    // TODO: Grant access depending on flags on the remote event and the
    //   cancellation token.
    //   Either the event or the token will be available, the latter of which
    //   can be used to retrieve the event.
    return AccessResult::allowed();
    return AccessResult::allowedIf($event->can_edit_registration);
  }
}

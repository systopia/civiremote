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


use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatch;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RegistrationUpdateForm extends FormBase {

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
   * @var string $remote_token
   */
  protected $remote_token;

  /**
   * RegistrationUpdateForm constructor.
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
    $this->event = $routeMatch->getParameter('event');
    $this->remote_token = $routeMatch->getRawParameter('remote_token');
    // Retrieve event using the remote token, overwriting the event object.
    if (!empty($this->remote_token)) {
      try {
        $this->event = $this->cmrf->getEvent(
          $this->cmrf->getEventFromToken($this->remote_token)
        );
      }
      catch (Exception $exception) {
        // Do nothing here. Access is being checked in self::access().
      }
    }
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
  public function getFormId() {
    return 'civiremote_event_registration_update_form';  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   *
   * @param string $remote_token
   *   The remote token.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event, which will have been
    // retrieved using a given remote token in the constructor already.
    return AccessResult::allowedIf(
      !empty($this->event)
      && $this->event->can_edit_registration
      && $this->event->is_registered
    );
  }

}

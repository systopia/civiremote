<?php
/*------------------------------------------------------------+
| CiviRemote - CiviCRM Remote Integration                     |
| Copyright (C) 2021 SYSTOPIA                                 |
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
use Drupal\civiremote_event\Utils as EventUtils;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatch;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckinForm extends FormBase {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF core service.
   */
  protected CiviMRF $cmrf;

  /**
   * @var string $checkin_token
   *   The remote event checkin token to use for retrieving information about
   *   the checkin form.
   */
  protected $checkin_token;

  /**
   * @var array $checkin_info
   *   The remote event checkin fields.
   */
  protected $checkin_info;

  /**
   * RegistrationCancelForm constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF core service.
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;

    // Extract form parameters and set them here so that implementations do not
    // have to care about that.
    $routeMatch = RouteMatch::createFromRequest($this->getRequest());
    $this->checkin_token = $routeMatch->getRawParameter('checkin_token');
    $this->checkin_info = $routeMatch->getParameter('checkin_token');
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
  public function getFormId() {
    return 'civiremote_event_checkin_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Display fields.
    foreach ($this->checkin_info['fields'] as $field) {
      $form[$field['path']] = [
        '#type' => 'item',
        '#title' => $field['label'],
        '#value' => $field['value'],
      ];
      // Set markup.
      $type = EventUtils::fieldType($field, 'confirm');
      switch ($type) {
        case 'date';
          try {
            $form[$field['path']]['#markup'] = Drupal::service('date.formatter')
              ->format(strtotime($field['value']));
          } catch (Exception $exception) {
            // There was no valid date value, do nothing.
          }
          break;
        case 'checkbox':
          $form[$field['path']]['#markup'] = $field['value'] ? $this->t('Yes') : $this->t('No');
          break;
        case 'radio':
          $form[$field['path']]['#markup'] = $field['value'] == $field['name'] ? $this->t('Yes') : $this->t('No');
          break;
        case 'value':
          break;
        default:
          $form[$field['path']]['#markup'] = (!empty($field['options']) ? $field['options'][$field['value']] : $field['value']);
          break;
      }
    }

    // Add submit buttons for all checkin options.
    if (!empty($this->checkin_info['checkin_options'])) {
      $form['actions'] = [
        '#type' => 'actions',
      ];
      foreach ($this->checkin_info['checkin_options'] as $checkin_status_id => $checkin_status_label) {
        $form['actions']['checkin_' . $checkin_status_id] = [
          '#type' => 'submit',
          '#name' => 'checkin_' . $checkin_status_id,
          '#value' => $this->t('Check-In as %status', ['%status' => $checkin_status_label]),
          '#civiremote_event_checkin_status' => $checkin_status_id,
        ];
      }
      $form['actions']['checkin_foobar'] = [
        '#type' => 'submit',
        '#name' => 'checkin_0',
        '#value' => $this->t('Check-In as %status', ['%status' => 'Foobar']),
        '#civiremote_event_checkin_status' => 0,
      ];
    }

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The Form API does not try to identify the clicked button by name when
    // identifying by value fails, but sets the triggering_element property to
    // the first button in the form.
    // @url https://www.drupal.org/project/drupal/issues/2546700
    $triggering_element = $form_state->getTriggeringElement();
    // If there was no specific triggering button detected by an element
    // value, try to find a triggering element by name in the user input.
    $input = $form_state->getUserInput();
    foreach ($form_state->getButtons() as $button_element) {
      if (isset($button_element['#name'])) {
        // Try to find an ajax-submitted element by name matched to _triggering_element_name.
        if ((!empty($input['_triggering_element_name']) && $button_element['#name'] == $input['_triggering_element_name'])
          // Or lookup for a non-empty value in the user input by element name.
          || (!empty($input[$button_element['#name']]))) {
          $triggering_element = $button_element;
          break;
        }
      }
    }
    $form_state->setTriggeringElement($triggering_element);

    // TODO: Check-in via API.
    Drupal::messenger()
      ->addStatus($this->t('Check-in with status ID %status_id requested',
        [
          '%status_id' => $form_state->getTriggeringElement()['#civiremote_event_checkin_status'],
        ]
      ));
  }

}

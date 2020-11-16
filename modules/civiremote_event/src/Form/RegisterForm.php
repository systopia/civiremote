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
use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatch;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic remote event registration form.
 *
 * This class handles all profiles not explicitly implemented in a separate form
 * class and serves as a base class for those implementations.
 *
 * @package Drupal\civiremote_event\Form
 */
class RegisterForm extends FormBase implements RegisterFormInterface {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected $cmrf;

  /**
   * @var stdClass $event
   *   The remote event to build the registration form for.
   */
  protected $event;

  /**
   * @var string $profile
   *   The remote event profile to use for building the registration form.
   */
  protected $profile;

  /**
   * @var string $remote_token
   *   The remote event token to use for retrieving information about the
   *   registration form.
   */
  protected $remote_token;

  /**
   * @var array $fields
   *   The remote event form fields.
   */
  protected $fields;

  /**
   * RegisterForm constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF service.
   */
  public function __construct(CiviMRF $cmrf) {
    // Store dependency references to passed-in services.
    $this->cmrf = $cmrf;

    // Extract form parameters and set them here so that implementations do not
    // have to care about that.
    $routeMatch = RouteMatch::createFromRequest($this->getRequest());
    $this->event = $routeMatch->getParameter('event');
    $this->profile = $routeMatch->getRawParameter('profile');
    $this->remote_token = $routeMatch->getRawParameter('remote_token');
    $this->fields = $this->cmrf->getRegistrationForm(
      (isset($this->event) ? $this->event->id : NULL),
      $this->profile,
      $this->remote_token
    );
    $this->event = $this->cmrf->getEvent(
      $this->fields['event_id']['value'],
      $this->remote_token
    );
    $this->profile = $this->fields['profile']['value'] ?: $this->event->default_profile;
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
    return 'civiremote_event_register_form';
  }

  public static function fieldTypes() {
    return [
      'Text' => 'textfield',
      'Textarea' => 'textarea',
      'Select' => 'select', // Can be replaced with 'radios' in buildForm().
      'Multi-Select' => 'select',
      'Checkbox' => 'checkbox',
      'Radio' => 'radio',
      'Date' => 'date',
      'Timestamp' => 'date',
      'Value' => 'value',
    ];
  }

  public function hasFields() {
    return !empty(array_filter($this->fields, function($field) {
      return in_array($field['type'], array_keys(self::fieldTypes()));
    }));
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare form steps.
    if (empty($form_state->get('steps'))) {
      $steps = [];
      if ($this->hasFields()) {
        // Generic form step, can be replaced in implementations.
        $steps[] = 'form';
      }
      // Confirmation step, right before final submission.
      $steps[] = 'confirm';
      // Thank you step, right after final submission.
      $steps[] = 'thankyou';

      $form_state->set('steps', $steps);
      // Initialize with first step.
      $form_state->set('step', 0);
    }
    $step = $form_state->get('step');
    $steps = $form_state->get('steps');

    // Build form depending on current step.
    if ($step == array_search('confirm', $steps)) {
      $form = $this->buildConfirmForm($form, $form_state);
      $submit_label = $this->t('Submit');
    }
    elseif ($step == array_search('thankyou', $steps)) {
      $form = $this->buildThankyouPage($form, $form_state);
      $form_state->setSubmitted();
    }
    else {
      $form = $this->buildRegisterForm($form, $form_state);
      $submit_label = $this->t('Next');
    }

    // Add submit button.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    if ($step > 0 && $step != array_search('thankyou', $steps)) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        // Do not validate when using this button. #submit is required for
        // #limit_validation_errors to work.
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'submitForm']],
      ];
    }
    if ($step < array_search('thankyou', $steps)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $submit_label,
      ];
    }

    // Do not validate the confirm form, since it only contains "Item" fields.
    if ($step == array_search('confirm', $steps)) {
      $form['actions']['submit']['#limit_validation_errors'] = [];
      $form['actions']['submit']['#submit'] = [[$this, 'submitForm']];
    }

    return $form;
  }

  /**
   * Form builder for the actual registration form step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @throws Exception
   */
  protected function buildRegisterForm(array $form, FormStateInterface $form_state) {
    // Add event form intro text.
    if (!empty($this->event->intro_text)) {
      $form[] = [
        '#markup' => $this->event->intro_text,
      ];
    }

    // Fetch fields from RemoteEvent.get_form API when no specific
    // implementation is present.
    $fields = $this->fields;
    $form_state->set('fields', $fields);
    $types = self::fieldTypes();

    foreach ($fields as $field_name => $field) {
      // Create and reference the group to place the field into.
      if (!empty($field['group_name'])) {
        if (empty($form[$field['group_name']])) {
          $form[$field['group_name']] = [
            '#type' => 'fieldset',
            '#title' => $field['group_label'],
          ];
          if (
            array_key_exists('confirm', $this->fields)
            && $field['group_name'] != 'confirmation'
          ) {
            $form[$field['group_name']]['#states'] = [
              'visible' => [[':input[name="confirm"]' => ['value' => 1]]],
            ];
          }
        }
        $group = &$form[$field['group_name']];
      }
      else {
        $group = &$form;
      }

      // Set field type to "email" when its input is to be validated as such.
      if ($field['validation'] == 'Email') {
        $type = 'email';
      }
      // Use radio buttons for select fields with up to 10 options.
      elseif (
        $types[$field['type']] == 'select'
        && count($field['options']) <= 10
      ) {
        $type = 'radios';
      }
      // Use default field types from mapping.
      else {
        $type = $types[$field['type']];
      }

      // Prepare field default values.
      if ($types[$field['type']] == 'date' && !empty($field['value'])) {
        $default_value = date_create_from_format('Ymd', $field['value'])
          ->format('Y-m-d');
      }
      else {
        $default_value = $field['value'];
      }

      // Build the field.
      $group[$field_name] = [
        '#type' => $type,
        '#name' => $field['name'],
        '#title' => $field['label'],
        '#description' => $field['description'],
        // We don't use #required here, since this depends on #states.
//        '#required' => !empty($field['required']),
        // Instead, we use our own attribute for the label, which serves as
        // a distinction in template_preprocess implementations.
        '#label_attributes' => ['display_required' => !empty($field['required'])],
        '#options' => ($type == 'select' || $type == 'radios' ? $field['options'] : NULL),
        '#multiple' => ($field['type'] == 'Multi-Select'),
        '#weight' => $field['weight'],
        '#default_value' => $form_state->getValue($field_name, $default_value ?: NULL),
      ];

      // Set #return_value for single Radios for later processing.
      if ($type == 'radio' && $field_name != $field['name']) {
        $group[$field_name]['#return_value'] = $field_name;
        $group[$field_name]['#parents'][] = $field['name'];
      }

      if (
        array_key_exists('confirm', $this->fields)
        && $field_name != 'confirm'
      ) {
        $group[$field_name]['#states'] = [
          'visible' => [[':input[name="confirm"]' => ['value' => 1]]],
        ];

        // Only add #required state when the field is actually required.
        if (!empty($field['required'])) {
          $group[$field_name]['#states']['required'] = [
            [':input[name="confirm"]' => ['value' => 1]]
          ];
        }
      }
      else {
        $group[$field_name]['#required'] = !empty($field['required']);
      }

      // Display prefix/suffix content.
      if (!empty($field['prefix'])) {
        $group[$field_name]['#prefix'] = $field['prefix'];
        if ($field['prefix_display'] == 'dialog') {
          $html_id = Html::getUniqueId('dialog-' . $field_name . '-prefix');
          $group[$field_name]['#prefix'] =
            '<div class="dialog-wrapper" data-dialog-id="' . $html_id . '">'
            . '<div class="dialog-content js-hide" id="' . $html_id . '">'
            . $group[$field_name]['#prefix']
            . '</div>'
            . '</div>';
          $group[$field_name]['#attached']['library'][] = 'civiremote/dialog';
        }
      }
      if (!empty($field['suffix'])) {
        $group[$field_name]['#suffix'] = $field['suffix'];
        if ($field['suffix_display'] == 'dialog') {
          $html_id = Html::getUniqueId('dialog-' . $field_name . '-suffix');
          $group[$field_name]['#suffix'] =
            '<div class="dialog-wrapper" data-dialog-id="' . $html_id . '">'
            . '<div class="dialog-content js-hide" id="' . $html_id . '">'
            . $group[$field_name]['#suffix']
            . '</div>'
            . '</div>';
          $group[$field_name]['#attached']['library'][] = 'civiremote/dialog';
        }
      }
    }

    // Add event form footer text.
    if (!empty($this->event->footer_text)) {
      $form[] = [
        '#markup' => $this->event->footer_text,
      ];
    }

    return $form;
  }

  /**
   * Form builder for the confirmation form step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @throws Exception
   */
  protected function buildConfirmForm(array $form, FormStateInterface $form_state) {
    // Set confirmation page title.
    if (!empty($this->event->confirm_title)) {
      $form['#title'] = $this->event->confirm_title;
    }

    // Add confirmation text.
    if (!empty($this->event->confirm_text)) {
      $form[] = [
        '#markup' => $this->event->confirm_text,
      ];
    }

    // Show summary.
    foreach ($form_state->get('fields') as $field_name => $field) {
      // Create and reference the group to place the field into.
      if (!empty($field['group_name'])) {
        if (empty($form[$field['group_name']])) {
          $form[$field['group_name']] = [
            '#type' => 'fieldset',
            '#title' => $field['group_label'],
          ];
          if (
            array_key_exists('confirm', $this->fields)
            && $field['group_name'] != 'confirmation'
          ) {
            $form[$field['group_name']]['#states'] = [
              'visible' => [[':input[name="confirm"]' => ['value' => 1]]],
            ];
          }
        }
        $group = &$form[$field['group_name']];
      }
      else {
        $group = &$form;
      }

      // Build the field.
      $value = $form_state->get('values')[$field['name']];
      $type = self::fieldTypes()[$field['type']];
      if ($type == 'value') {
        $group[$field_name] = [
          '#type' => 'value',
          '#value' => $value,
        ];
      }
      else {
        $group[$field_name] = [
          '#type' => 'item',
          '#name' => $field_name,
          '#title' => $field['label'],
          '#description' => $field['description'],
          // We don't use #required here, since this depends on #states.
//          '#required' => !empty($field['required']),
          // Instead, we use our own attribute for the label, which serves as
          // a distinction in template_preprocess implementations.
          '#label_attributes' => ['display_required' => !empty($field['required'])],
          '#multiple' => ($field['type'] == 'Multi-Select'),
          '#weight' => $field['weight'],
          '#markup' => (!empty($field['options']) ? $field['options'][$value] : $value),
          '#value' => $form_state->getValue($field_name, NULL),
        ];
        if ($type == 'date') {
          $group[$field_name]['#markup'] = Drupal::service('date.formatter')
            ->format(strtotime($group[$field_name]['#value']));
        }
        if ($type == 'checkbox') {
          $group[$field_name]['#markup'] = $value ? $this->t('Yes') : $this->t('No');
        }
        if ($type == 'radio') {
          $group[$field_name]['#markup'] = $value == $field_name ? $this->t('Yes') : $this->t('No');
        }
        if (
          array_key_exists('confirm', $this->fields)
          && $field_name != 'confirm'
        ) {
          $group[$field_name]['#states'] = [
            'visible' => [[':input[name="confirm"]' => ['value' => 1]]],
            'optional' => [[':input[name="confirm"]' => ['value' => 0]]],
          ];

          // Only add #required state when the field is actually required.
          if (!empty($field['required'])) {
            $group[$field_name]['#states']['required'] = [
              [':input[name="confirm"]' => ['value' => 1]]
            ];
          }
        }
        else {
          $group[$field_name]['#required'] = !empty($field['required']);
        }
      }

      // Display prefix/suffix content.
      if (!empty($field['prefix'])) {
        $group[$field_name]['#prefix'] = $field['prefix'];
        // TODO: Display with Dialog API when $field['prefix_display'] = 'dialog'.
      }
      if (!empty($field['suffix'])) {
        $group[$field_name]['#suffix'] = $field['suffix'];
        // TODO: Display with Dialog API when $field['suffix_display'] = 'dialog'.
      }
    }

    // Add confirmation footer text.
    if (!empty($this->event->confirm_footer_text)) {
      $form[] = [
        '#markup' => $this->event->confirm_footer_text,
      ];
    }

    return $form;
  }

  /**
   * Form builder for the thankyou form step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @throws Exception
   */
  public function buildThankyouPage(array &$form, FormStateInterface $form_state) {
    // Set confirmation page title.
    if (!empty($this->event->thankyou_title)) {
      $form['#title'] = $this->event->thankyou_title;
    }

    // Add confirmation text.
    if (!empty($this->event->thankyou_text)) {
      $form[] = [
        '#markup' => $this->event->thankyou_text,
      ];
    }

    // TODO: Show summary?

    // Add confirmation footer text.
    if (!empty($this->event->thankyou_footer_text)) {
      $form[] = [
        '#markup' => $this->event->thankyou_footer_text,
      ];
    }

    return $form;
  }

  /**
   * Pre-processes submitted form values for sending to the CiviCRM API.
   *
   * @param array $values
   *   The form values, keyed by field name.
   */
  public function preprocessValues(array &$values) {
    $new_values = [];
    $radio_fields = array_filter($this->fields, function($field) {
      return self::fieldTypes()[$field['type']] == 'radio';
    });
    $radio_field_names = array_map(function($field) {
      return $field['name'];
    }, $radio_fields);
    foreach ($values as $field_name => &$value) {
      // Use CiviCRM date format for date fields.
      if (self::fieldTypes()[$this->fields[$field_name]['type']] == 'date') {
        $value = date_create($value)->format('Ymd');
      }

      // Set single radio element values.
      if (
        !array_key_exists($field_name, $this->fields)
        && in_array($field_name, $radio_field_names)
      ) {
        if (!empty($value)) {
          $new_values[$value] = (int) !empty($value);
        }
        unset($values[$field_name]);
      }
    }
    $values += $new_values;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do not validate backward navigation.
    if (
      isset($form['actions']['back'])
      && $form_state->getTriggeringElement()['#value'] == $form['actions']['back']['#value']
    ) {
      return;
    }

    if ($form_state->get('step') == array_search('confirm', $form_state->get('steps'))) {
      $values = $form_state->get('values');
    }
    else {
      $form_state->cleanValues();
      $values = $form_state->getValues();
    }
    $this->preprocessValues($values);
    $errors = $this->cmrf->validateEventRegistration(
      $this->event->id,
      $this->profile,
      $this->remote_token,
      $values
    );
    if (!empty($errors)) {
      $form_state->set('step', array_search('form', $form_state->get('steps')));
      foreach ($errors as $field => $message) {
        $form_state->setErrorByName($field, $message);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    if (
      isset($form['actions']['back'])
      && $form_state->getTriggeringElement()['#value'] == $form['actions']['back']['#value']
    ) {
      // Handle backwards navigation.
      $form_state->set('step', --$step);
      $form_state->setValues($form_state->get('values'));
      $form_state->setRebuild();
    }
    else {
      // Handle forward navigation.
      if ($step == array_search('confirm', $form_state->get('steps'))) {
        // Submit the form values to the CiviRemote Event API.
        $values = $form_state->get('values') ?: [];
        $this->preprocessValues($values);
        try {
          $result = $this->cmrf->createEventRegistration(
            $this->event->id,
            $this->profile,
            $this->remote_token,
            $values
          );

          // Advance to "Thank you" step.
          $form_state->set(
            'step',
            array_search('thankyou', $form_state->get('steps'))
          );
          $form_state->setRebuild();
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
      else {
        $form_state->cleanValues();
        $values = $form_state->getValues();
        // Advance to next step and rebuild the form.
        $form_state->set('values', $values);
        $form_state->set('step', ++$step);
        $form_state->setRebuild();
      }
    }
  }

  /**
   * Custom access callback for this form's route.
   *
   * Note: The parameters passed in here are not being used, since they have
   * been processed in the constructor already. Instead, class members are being
   * used for deciding about access.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   * @param string $remote_token
   *   The remote token to use for retrieving the form.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event = NULL, $profile = NULL, $remote_token = NULL) {
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      !empty($this->event)
      && $this->event->can_register
      && (
        !isset($this->profile)
        || in_array(
          $this->profile,
          explode(',', $this->event->enabled_profiles)
        )
      )
    );
  }
}

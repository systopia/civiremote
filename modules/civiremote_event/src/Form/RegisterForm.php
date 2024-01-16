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
use Drupal\civiremote_event\Utils as EventUtils;
use Drupal\civiremote_event\CiviMRF;
use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * @var array $messages
   *   Status messages to be displayed on the remote event form.
   */
  protected $messages;

  /**
   * @var string $context
   *   The context which the form is being used for, one of
   *   - create
   *   - update
   *   - cancel
   */
  protected $context;

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
    switch ($routeMatch->getRouteName()) {
      case 'civiremote_event.register_form':
      case 'civiremote_event.register_token_form':
        $this->context = 'create';
        break;
      case 'civiremote_event.registration_update_form':
      case 'civiremote_event.registration_update_token_form':
        $this->context = 'update';
        break;
    }
    if (!isset($this->context)) {
      throw new NotFoundHttpException(
        $this->t('Invalid CiviRemote Event form context')
      );
    }
    $this->event = $routeMatch->getParameter('event');
    $this->profile = $routeMatch->getRawParameter('profile');
    $this->remote_token = $routeMatch->getRawParameter('event_token');
    try {
      $form = $this->cmrf->getForm(
        (isset($this->event) ? $this->event->id : NULL),
        $this->profile,
        $this->remote_token,
        $this->context
      );
      $this->fields = $form['values'];
      $this->messages = isset($form['status_messages']) ? $form['status_messages'] : [];
      $this->event = $this->cmrf->getEvent(
        $this->fields['event_id']['value'],
        $this->remote_token
      );
      if (!empty($this->fields['profile']['value'])) {
        $this->profile = $this->fields['profile']['value'];
      }
      else {
        switch ($this->context) {
          case 'create':
            $this->profile = $this->event->default_profile;
            break;
          case 'update':
            $this->profile = $this->event->default_update_profile;
            break;
          default:
            throw new NotFoundHttpException(
              $this->t('No profile found for CiviRemote event form.')
            );
        }
      }
    }
    catch (Exception $exception) {
      Drupal::messenger()->addMessage(
        $exception->getMessage(),
        MessengerInterface::TYPE_ERROR
      );
    }
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

  public static function highestWeight($context) {
    $weight = 0;
    foreach (Element::getVisibleChildren($context) as $element) {
      if (isset($context[$element]['#weight']) && $context[$element]['#weight'] >= $weight) {
        $weight = $context[$element]['#weight'];
      }
    }
    return $weight;
  }

  public function groupParents($field_name) {
    $parents = [];
    $parent = isset($this->fields[$field_name]['parent']) ? $this->fields[$field_name]['parent'] : NULL;
    if (
      !empty($parent)
      && array_key_exists($parent, $this->fields)
    ) {
      array_unshift($parents, $parent);
      $parents = array_merge($this->groupParents($parent), $parents);
    }

    return $parents;
  }

  public static function defaultValue($field, $field_name, $type) {
    $default_value = NULL;

    switch ($type) {
      case 'date':
        if (!empty($field['value'])) {
          $default_value = date_create_from_format('Ymd', $field['value'])
            ->format('Y-m-d');
        }
        break;
      case 'datetime':
        if (!empty($field['value'])) {
          $default_value = date_create_from_format('YmdHis', $field['value'])
            ->format('Y-m-d H:i:s');
          $default_value = new DrupalDateTime($default_value);
        }
        break;
      case 'checkboxes':
        if (
          isset($field['value'])
          && array_key_exists($field['value'], $field['options'])
        ) {
          $default_value = $field['value'];
        }
        break;
      case 'radios':
        if (
          isset($field['value'])
          && array_key_exists($field['value'], $field['options'])
        ) {
          $default_value = $field['value'];
        }
        // Select "empty" option for non-required fields or required fields that
        // already have an "empty" option (0 or '').
        // Non-required fields without an "empty" option will get that added
        // later.
        elseif (
          !$field['required']
          || array_key_exists('', $field['options'])
          || array_key_exists(0, $field['options'])
        ) {
          $default_value = 0; // also evaluates to '' option.
        }
        // Select first option for required fields without an "empty" option.
        elseif ($field['required']) {
          reset($field['options']);
          $default_value = key($field['options']);
        }
        break;
      case 'radio':
        if (!empty($field['value'])) {
          $default_value = $field_name;
        }
        break;
      default:
        if (isset($field['value'])) {
          $default_value = $field['value'];
        }
        break;
    }

    return $default_value;
  }

  public function addConfirmDependencies($field, $field_name, &$group) {
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
          [':input[name="confirm"]' => ['value' => 1]],
        ];
        $group[$field_name]['#label_attributes']['display_required'] = TRUE;
      }
    }
    else {
      $group[$field_name]['#required'] = !empty($field['required']);
    }
  }

  public function addPrefixSuffix($field, $field_name, &$group) {
    if (!empty($field['prefix'])) {
      $group[$field_name]['#prefix'] = '<div class="form-element-prefix">';
      if ($field['prefix_display'] == 'dialog') {
        $html_id = Html::getUniqueId('dialog-' . $field_name . '-prefix');
        $group[$field_name]['#prefix'] .=
          '<div
            class="dialog-wrapper"
            data-dialog-id="' . $html_id . '"
            data-dialog-label="' . $field['prefix_dialog_label'] . '"
            >'
          . '<div class="dialog-content js-hide" id="' . $html_id . '">'
          . $field['prefix']
          . '</div>'
          . '</div>';
        $group[$field_name]['#attached']['library'][] = 'civiremote/dialog';
      }
      else {
        $group[$field_name]['#prefix'] .= $field['prefix'];
      }
      $group[$field_name]['#prefix'] .= '</div>';
    }
    if (!empty($field['suffix'])) {
      $group[$field_name]['#suffix'] = '<div class="form-element-suffix">';
      if ($field['suffix_display'] == 'dialog') {
        $html_id = Html::getUniqueId('dialog-' . $field_name . '-suffix');
        $group[$field_name]['#suffix'] .=
          '<div
            class="dialog-wrapper"
            data-dialog-id="' . $html_id . '"
            data-dialog-label="' . $field['suffix_dialog_label'] . '"
            >'
          . '<div class="dialog-content js-hide" id="' . $html_id . '">'
          . $field['suffix']
          . '</div>'
          . '</div>';
        $group[$field_name]['#attached']['library'][] = 'civiremote/dialog';
      }
      else {
        $group[$field_name]['#suffix'] .= $field['suffix'];
      }
      $group[$field_name]['#suffix'] .= '</div>';
    }
    // Wrap elements with prefix or suffix in a separate container for better
    // theming.
    if (!empty($field['prefix']) || !empty($field['suffix'])) {
      $container = [
        '#type' => 'container',
        '#weight' => (isset($group[$field_name]['#weight']) ? $group[$field_name]['#weight'] : NULL),
      ];
      if (!empty($group[$field_name]['#zebra_context'])) {
        $container['#zebra_context'] = $group[$field_name]['#zebra_context'];
        unset($group[$field_name]['#zebra_context']);
      }
      $container[$field_name] = $group[$field_name];
      unset($group[$field_name]);
      $group[] = $container;
    }
  }

  public function hasFields() {
    return !empty(array_filter($this->fields, function($field) {
      return in_array($field['type'], array_keys(EventUtils::fieldTypes()));
    }));
  }

  /**
   * Retrieves fields dependent on the given field from the current form.
   *
   * @param array $field
   *   The dependee field, i.e. the field other fields are dependent on.
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array of references to fields dependent on the dependee field after
   *   they have been processed dependency-wise.
   */
  public function getDependencies(array &$field, array &$form, FormStateInterface $form_state) {
    $field_name = $field['#name'];
    $fields = $form_state->get('fields');
    $dependent_fields = [];
    if (isset($fields[$field_name])) {
      if (!empty($dependencies = $fields[$field_name]['dependencies'])) {
        foreach ($dependencies as $dependency) {
          // Retrieve the parent fieldset/form of the dependent field.
          $dependent_field_name = $dependency['dependent_field'];
          $dependent_group_parents = $this->groupParents($dependent_field_name);
          $dependent_group = &NestedArray::getValue($form, $dependent_group_parents);
          $dependent_field = &$dependent_group[$dependent_field_name];
          $dependent_fields[$dependent_field_name] = &$dependent_field;
        }
      }
    }
    return $dependent_fields;
  }

  /**
   * Applies dependencies on fields, i.e. restricts fields' options or sets
   * values depending on other fields' values.
   *
   * @param array $field
   *   The dependee field, i.e. the field other fields are dependent on.
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array of references to fields dependent on the dependee field after
   *   they have been processed dependency-wise.
   */
  public function applyDependencies(array &$field, array &$form, FormStateInterface $form_state) {
    $field_name = $field['#name'];
    $field_value = $form_state->getValue($field_name) ?: $field['#default_value'] ?? '';
    $fields = $form_state->get('fields');
    $dependent_fields = [];
    if (isset($fields[$field_name])) {
      if (!empty($dependencies = $fields[$field_name]['dependencies'])) {
        foreach ($dependencies as $dependency) {
          // Retrieve the parent fieldset/form of the dependent field.
          $dependent_field_name = $dependency['dependent_field'];
          $dependent_group_parents = $this->groupParents($dependent_field_name);
          $dependent_group = &NestedArray::getValue($form, $dependent_group_parents);
          $dependent_field = &$dependent_group[$dependent_field_name];
          $dependent_fields[$dependent_field_name] = &$dependent_field;
          $dependent_field_options = $form_state->get('fields')[$dependent_field_name]['options'];

          // Restrict the dependent field's values according to the regex.
          if ($dependency['command'] == 'restrict') {
            $regex = str_replace(
              '{current_value}',
              // @todo $field_value depends on $dependency['regex_subject'].
              ($field_value ?? ''),
              $dependency['regex']
            );
            $matches = preg_grep(
              "/$regex/",
              array_keys($dependent_field_options)
            );
            $dependent_field['#options'] = array_intersect_key(
              $dependent_field_options,
              array_flip($matches)
            );

            // Hide dependent fields with no options if requested and prevent
            // submitting a value.
            if (
              (
                $dependency['hide_restricted_empty']
                && empty($dependent_field['#options'])
              )
              || (
                $dependency['hide_unrestricted']
                && empty($field_value)
              )
            ) {
              $dependent_field['#wrapper_attributes']['class'][] = 'visually-hidden';
              $dependent_field['#disabled'] = TRUE;
              $dependent_field['#value'] = NULL;
            }
          }

          // TODO: Set the dependent field's value according to the regex.
          elseif ($dependency['command'] == 'set') {

          }
        }
      }
    }

    return $dependent_fields;
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
      // Show messages returned by the API.
      if (!empty($this->messages)) {
        Utils::setMessages($this->messages);
      }
      $form = $this->buildRegisterForm($form, $form_state);
      $submit_label = $this->t('Next');
    }

    // Add submit buttons.
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => self::highestWeight($form) + 1,
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
    $form_state->set('fields', $this->fields);

    $unselect_radios = [];

    foreach ($form_state->get('fields') as $field_name => $field) {
      // Build hierarchy and retrieve the parent fieldset (or the form itself).
      $group_parents = $this->groupParents($field_name);
      $group = &NestedArray::getValue($form, $group_parents);

      // Set field type to "email" when its input is to be validated as such.
      $type = EventUtils::fieldType($field, 'form');

      // Prepare field default values.
      $default_value = $form_state->getValue(
        $field['name'],
        $this->defaultValue($field, $field_name, $type)
      );

      // Do not render fields for additional participants when not applicable.
      $matches = [];
      if (preg_match('#^additional_([0-9]+)(_|$)#', $field_name, $matches)) {
        $participant_no = $matches[1];
        $participant_count = $form_state->get('additional_participants_count') ?? 0;
        if (
          empty($this->event->is_multiple_registrations)
          || $participant_count < $participant_no
        ) {
          continue;
        }
      }
      if (
        empty($this->event->is_multiple_registrations)
        && $field_name == 'additional_participants'
      ) {
        continue;
      }

      // Add "None" radio button for de-selecting single radio buttons.
      if ($type == 'radio' && !in_array($field['name'], $unselect_radios)) {
        $unselect_radios[] = $field['name'];
        $group[$field['name'] . '_unselect'] = [
          '#type' => 'radio',
          '#name' => $field['name'],
          '#title' => $this->t('- None -'),
          '#weight' => $field['weight'] - 0.1,
          '#return_value' => '',
          '#default_value' => '',
          '#disabled' => !empty($field['disabled']),
          '#zebra_context' => $field['name'],
        ];
        $this->addConfirmDependencies(
          $field,
          $field['name'] . '_unselect', $group
        );
      }

      // Build the field (or fieldset).
      $group[$field_name] = [
        '#type' => $type,
        '#name' => $field['name'],
      ];
      if (!empty($field['label'])) {
        $group[$field_name]['#title'] = $field['label'];
      }
      if (!empty($field['description'])) {
        $group[$field_name]['#description'] = $field['description'];
      }
      if (!empty($field['weight'])) {
        $group[$field_name]['#weight'] = $field['weight'];
      }
      $group[$field_name]['#default_value'] = $default_value;
      if ($type == 'select' || $type == 'radios' || $type == 'checkboxes') {
        $group[$field_name]['#options'] = $field['options'];
      }
      // Add "empty" option for non-required radio button groups, if it doesn't
      // already exist.
      if (
        $type == 'radios'
        && !array_key_exists('', $group[$field_name]['#options'])
        && !array_key_exists(0, $group[$field_name]['#options'])
        && empty($field['required'])
      )  {
        $group[$field_name]['#options'] =
          ['' => $this->t('- None -')] + $group[$field_name]['#options'];
      }
      if ($type == 'select') {
        if ($field['type'] == 'Multi-Select') {
          $group[$field_name]['#multiple'] = TRUE;
        }
        if (isset($field['empty_label'])) {
          $group[$field_name]['#empty_option'] = $field['empty_label'];
        }
        elseif (!empty($field['required'])) {
          $group[$field_name]['#empty_option'] = $this->t('- Select -');
        }
        else {
          $group[$field_name]['#empty_option'] = $this->t('- None -');
        }
      }

      // Disable field if requested.
      if (!empty($field['disabled'])) {
        $group[$field_name]['#disabled'] = TRUE;
      }

      // Extract dependencies for later processing via #states.
      if (!empty($field['dependencies'])) {
        foreach ($field['dependencies'] as $dependency) {
          $dependencies[$field['name']][$dependency['dependent_field']] = $dependency;
        }
      }

      // Set #return_value for single Radios for later processing.
      if ($type == 'radio' && $field_name != $field['name']) {
        $group[$field_name]['#return_value'] = $field_name;
        $group[$field_name]['#parents'][] = $field['name'];
        $group[$field_name]['#zebra_context'] = $field['name'];
      }

      // Make the field's visibility and necessity depend on the "confirm" field
      // if it exists.
      $this->addConfirmDependencies($field, $field_name, $group);

      // Display prefix/suffix content.
      $this->addPrefixSuffix($field, $field_name, $group);

      if (($field['confirm_required'] ?? FALSE) === TRUE) {
        $group[$field_name] = [
          '#type' => 'confirm',
          '#element' => $group[$field_name],
        ];
      }

      // Add "Add more" wrapper for additional participants.
      if (
        $this->event->is_multiple_registrations
        && $field_name == 'additional_participants'
      ) {
        $group[$field_name]['#prefix'] = '<div id="additional-participants-wrapper">'
        . ($group[$field_name]['#prefix'] ?? '');
        $group[$field_name]['#suffix'] = ($group[$field_name]['#suffix'] ?? '')
        . '</div>';
        $group[$field_name]['actions'] = [
          '#type' => 'actions',
        ];
        if (($form_state->get('additional_participants_count') ?? 0) < $this->event->max_additional_participants) {
          $group[$field_name]['actions']['add_participant'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add participant'),
            '#submit' => [[$this, 'additionalParticipantAdd']],
            '#ajax' => [
              'callback' => [$this, 'additionalParticipantsCallback'],
              'wrapper' => 'additional-participants-wrapper',
            ],
            '#limit_validation_errors' => [],
          ];
        }
        if (($form_state->get('additional_participants_count') ?? 0) > 0) {
          $group[$field_name]['actions']['remove_participant'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove participant'),
            '#submit' => [[$this, 'additionalParticipantRemove']],
            '#ajax' => [
              'callback' => [$this, 'additionalParticipantsCallback'],
              'wrapper' => 'additional-participants-wrapper',
            ],
            '#limit_validation_errors' => [],
          ];
        }
      }
    }

    // Collapse fieldsets with more than 10 children.
    foreach ($form_state->get('fields') as $field_name => $field) {
      // Build hierarchy and retrieve the parent fieldset (or the form itself).
      $group_parents = $this->groupParents($field_name);
      $field_exists = NULL;
      $group = &NestedArray::getValue($form, $group_parents, $field_exists);
      if ($field_exists) {
        $type = EventUtils::fieldTypes()[$field['type']];
        if (
          array_key_exists($field_name, $group)
          && in_array($type, ['details', 'fieldset'])
        ) {
          $group[$field_name]['#open'] = count(
              Element::getVisibleChildren($group[$field_name])
            ) < 10;
        }
      }
    }

    // Apply dependencies via #ajax.
    if (!empty($dependencies)) {
      foreach ($dependencies as $field_name => $field_dependencies) {
        // Register an Ajax callback for the onChange event on the field.
        $field_group_parents = $this->groupParents($field_name);
        $field_exists = NULL;
        $field_group = &NestedArray::getValue($form, $field_group_parents, $field_exists);
        if ($field_exists) {
          $field_group[$field_name]['#civiremote_event_dependencies'] = $field_dependencies;
          $field_group[$field_name]['#ajax'] = [
            'callback' => '::dependencyAjaxCallback',
            'event' => 'change',
            'effect' => 'fade',
          ];
          $field_group[$field_name]['#limit_validation_errors'] = [
            [$field_name]
          ];
          $field_group[$field_name]['#submit'] = [[$this, 'submitForm']];

          // Process dependent fields.
          foreach ($field_dependencies as $dependent_field_name => $dependency) {
            // Wrap dependent fields with a wrapper element.
            $dependent_group_parents = $this->groupParents($field_name);
            $dependent_group = &NestedArray::getValue($form, $dependent_group_parents);
            if (empty($dependent_group[$dependent_field_name]['#prefix'])) {
              $dependent_group[$dependent_field_name]['#prefix'] = '';
            }
            $dependent_group[$dependent_field_name]['#prefix'] =
              '<div id="dependency-wrapper-' . $dependent_field_name . '">'
              . $dependent_group[$dependent_field_name]['#prefix'];
            if (empty($dependent_group[$dependent_field_name]['#suffix'])) {
              $dependent_group[$dependent_field_name]['#suffix'] = '';
            }
            $dependent_group[$dependent_field_name]['#suffix'] .= '</div>';
          }

          // Initially apply dependencies on dependent fields.
          $this->applyDependencies($field_group[$field_name], $form, $form_state);
        }
      }
    }

    // Add event form footer text.
    if (!empty($this->event->footer_text)) {
      $form['footer_text'] = [
        '#type' => 'container',
        '#weight' => self::highestWeight($form) + 1,
        [
          '#markup' => $this->event->footer_text,
        ],
      ];
      $this->addConfirmDependencies([], 'footer_text', $form);
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
    $is_invitation_rejected = array_key_exists('confirm', $this->fields)
      && empty($form_state->getValue('confirm'));
    // Set confirmation page title.
    if (
      !$is_invitation_rejected
      && !empty($this->event->confirm_title)
    ) {
      $form['#title'] = $this->event->confirm_title;
    }

    // Add confirmation text.
    if (
    !$is_invitation_rejected
    && !empty($this->event->confirm_text)
    ) {
      $form[] = [
        '#markup' => $this->event->confirm_text,
      ];
    }

    // Show summary.

    $fields = $form_state->get('fields');
    foreach ($fields as $field_name => $field) {
      // Build hierarchy and retrieve the parent fieldset (or the form itself).
      $group_parents = $this->groupParents($field_name);
      $group = &NestedArray::getValue($form, $group_parents);

      // Build the field.
      $value = $form_state->getValue($field['name']);
      $type = EventUtils::fieldType($field, 'confirm');

      // Unset $value when the value does not belong to the field.
      if ($field_name != $field['name'] && $value != $field_name) {
        $value = NULL;
      }

      // Display fields only when either
      // - there is a submitted value or empty values are to be displayed.
      // - they are a "fieldset" or "value" type element
      // - they are dependent on the "confirm" field and its value is 1
      // - they are not dependent on the "confirm" field (i.e. there is none)
      // The "confirm" field itself shall only be displayed when its value is 0.
      if (
        ($field_name == 'confirm' && $value == 0)
        || (
          $field_name != 'confirm'
          && (
            !empty($value)
            || !empty($field['summary_show_empty'])
          )
          && (
            !array_key_exists('confirm', $this->fields)
            || $form_state->getValue('confirm') == 1
          )
        )
        || in_array($type, ['details', 'fieldset', 'value'])
      ) {
        $group[$field_name] = [
          '#type' => (in_array($type, ['details', 'fieldset', 'value']) ? $type : 'item'),
          '#name' => $field['name'],
        ];
        if (!empty($field['label'])) {
          $group[$field_name]['#title'] = $field['label'];
        }
        if (!empty($field['weight'])) {
          $group[$field_name]['#weight'] = $field['weight'];
        }
        // TODO: Is #multiple being evaluated for #type = item?
        if ($field['type'] == 'Multi-Select') {
          $group[$field_name]['#multiple'] = TRUE;
        }

        // Set value.
        if (isset($value)) {
          $group[$field_name]['#value'] = $value;

          // Set markup.
          switch ($type) {
            case 'date';
              try {
                $group[$field_name]['#markup'] = Drupal::service('date.formatter')
                  ->format(strtotime($group[$field_name]['#value']));
              } catch (Exception $exception) {
                // There was no valid date value, do nothing.
              }
              break;
            case 'checkbox':
              $group[$field_name]['#markup'] = $value ? $this->t('Yes') : $this->t('No');
              break;
            case 'radio':
              $group[$field_name]['#markup'] = $value == $field_name ? $this->t('Yes') : $this->t('No');
              break;
            case 'value':
              break;
            default:
              if (!empty($field['options'])) {
                if (is_array($value)) {
                  // Display multiple field values as item lists.
                  unset($group[$field_name]['#value']);
                  $group[$field_name]['items'] = [
                    '#theme' => 'item_list',
                    '#items' => array_intersect_key($field['options'], array_flip($value)),
                  ];
                }
                else {
                  $group[$field_name]['#markup'] = $field['options'][$value];
                }
              }
              else {
                $group[$field_name]['#markup'] = $value;
              }
              break;
          }
        }

        // Display prefix/suffix content.
        $this->addPrefixSuffix($field, $field_name, $group);
      }
    }

    // Remove empty fieldsets (starting with last field to cater for nested
    // fieldsets).
    foreach (array_reverse($fields, TRUE) as $field_name => $field) {
      // Build hierarchy and retrieve the parent fieldset (or the form itself).
      $group_parents = $this->groupParents($field_name);
      $group = &NestedArray::getValue($form, $group_parents);
      $type = EventUtils::fieldTypes()[$field['type']];
      if (
        array_key_exists($field_name, $group)
        && in_array($type, ['details', 'fieldset'])
      ) {
        if (empty(Element::getVisibleChildren($group[$field_name]))) {
          unset($group[$field_name]);
        }
        else {
          $group[$field_name]['#open'] = count(
              Element::getVisibleChildren($group[$field_name])
            ) < 10;
        }
      }
    }

    // Add confirmation footer text.
    if (
      !$is_invitation_rejected
      && !empty($this->event->confirm_footer_text)
    ) {
      $form[] = [
        '#markup' => $this->event->confirm_footer_text,
        '#weight' => self::highestWeight($form) + 1,
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
   * Callback for both ajax-enabled buttons (remove, add) for additional
   * participants.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A render array.
   */
  public function additionalParticipantsCallback(array &$form, FormStateInterface $form_state) {
    $group_parents = $this->groupParents('additional_participants');
    $group = &NestedArray::getValue($form, $group_parents);
    return $group['additional_participants'];
  }

  /**
   * Submit handler for the "Add Participant" button.
   *
   * Increases the additional participant count and initiates a form rebuild.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function additionalParticipantAdd(array &$form, FormStateInterface $form_state) {
    $form_state->set('additional_participants_count', ($form_state->get('additional_participants_count') ?? 0) + 1);

    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove Participant" button.
   *
   * Decreases the additional participant count and initiates a form rebuild.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public function additionalParticipantRemove(array &$form, FormStateInterface $form_state) {
    $form_state->set('additional_participants_count', $form_state->get('additional_participants_count') - 1);

    $form_state->setRebuild();
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
      return EventUtils::fieldTypes()[$field['type']] == 'radio';
    });
    $radio_field_names = array_map(function($field) {
      return $field['name'];
    }, $radio_fields);
    foreach ($values as $field_name => &$value) {
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

      // Filter checkboxes field type values (exclude unselected values).
      if (EventUtils::fieldType($this->fields[$field_name]) == 'checkboxes') {
        $value = array_filter($value);
      }

      // Use CiviCRM date format for date fields.
      if (!empty($value) && EventUtils::fieldTypes()[$this->fields[$field_name]['type']] == 'date') {
        $value = date_create($value)->format('Ymd');
      }
      // Use CiviCRM date format for datetime fields.
      if (!empty($value) && EventUtils::fieldTypes()[$this->fields[$field_name]['type']] == 'datetime') {
        /* @var DrupalDateTime $value */
        $value = $value->format('YmdHis');
      }
    }
    $values += $new_values;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = &$form_state->getTriggeringElement();

    // Do not validate backward navigation.
    if (
      isset($form['actions']['submit'])
      && $triggering_element['#value'] == $form['actions']['submit']['#value']
    ) {
      if ($form_state->get('step') == array_search('confirm', $form_state->get('steps'))) {
        $values = $form_state->get('values');
      }
      else {
        $form_state->cleanValues();
        $values = $form_state->getValues();
      }
      $this->preprocessValues($values);
      try {
        $result = $this->cmrf->validateEventRegistration(
          $this->event->id,
          $this->profile,
          $this->remote_token,
          $this->context,
          $values
        );

        // Show messages returned by the API.
        if (!empty($result['status_messages'])) {
          foreach ($result['status_messages'] as $message) {
            if ($message['severity'] == 'error') {
              if (!empty($message['reference'])) {
                $form_state->setErrorByName(
                  $message['reference'],
                  $this->fields[$message['reference']]['label'] . ': ' . $message['message']
                );
              }
              else {
                $form_state->set('error', TRUE);
                Drupal::messenger()->addMessage(
                  $message['message'],
                  MessengerInterface::TYPE_ERROR
                );
                $form_state->setRebuild();
              }
            }
            else {
              Utils::setMessages([$message]);
            }
          }

          if (!empty($form_state->getErrors())) {
            $form_state->set('step', array_search('form', $form_state->get('steps')));
          }
        }
      }
      catch (Exception $exception) {
        $form_state->set('error', TRUE);
        Drupal::messenger()->addMessage(
          t('Registration validation failed, please try again later.'),
          MessengerInterface::TYPE_ERROR
        );
        $form_state->setRebuild();
      }
    }

    // Clear errors for dependency triggers.
    // This is necessary, because:
    // - dependee fields have validation errors set to themselves only
    // - when the dependee field is required and it is set back to the "none"
    //   value, Drupal fails validation and would rebuild the cached form,
    //   including the dependent field with its current selection
    // - the user would be presented the dependee field without a selection, and
    //   the dependent field with values belonging to the previously selected
    //   dependee field value
    // Clearing errors is harmless in this case, because the form is not being
    // submitted with falsy values during the Ajax call.
    elseif (
      !empty($triggering_element['#civiremote_event_dependencies'])
      && !empty($triggering_element['#limit_validation_errors'])
      && !empty($errors = $form_state->getErrors())
    ) {
      $form_state->clearErrors();
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
            $values,
            TRUE
          );

          // Advance to "Thank you" step (when this is no invitation or it has
          // been confirmed).
          if (
            !array_key_exists('confirm', $this->fields)
            || !empty($values['confirm'])
          ) {
            $form_state->set(
              'step',
              array_search('thankyou', $form_state->get('steps'))
            );
            $form_state->setRebuild();
          }
          else {
            // Redirect to target from configuration.
            $config = Drupal::config('civiremote_event.settings');
            /* @var Url $url */
            $url = Drupal::service('path.validator')
              ->getUrlIfValid($config->get('form_redirect_route'));
            $form_state->setRedirect(
              $url->getRouteName(),
              $url->getRouteParameters(),
              $url->getOptions()
            );
          }
        }
        catch (Exception $exception) {
          $form_state->set('error', TRUE);
          $form_state->setValues($values);
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
   * Ajax callback for applying field dependencies.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse | NULL
   *   An Ajax response object, or NULL if the callback does not apply.
   */
  public function dependencyAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Build an Ajax response object.
    $response = new AjaxResponse();

    // Build Ajax Commands for applying dependencies.
    $trigger = $form_state->getTriggeringElement();
    $dependent_fields = $this->getDependencies($trigger, $form, $form_state);
    foreach ($dependent_fields as $dependent_field_name => $dependent_field) {
      // Render the dependent field and build an Ajax command for
      // replacing it.
      $renderer = Drupal::service('renderer');
      $renderedField = $renderer->render($dependent_field);

      // Add the command for replacing the dependent field with the
      // updated markup to the Ajax response object.
      $response->addCommand(new ReplaceCommand(
        '#dependency-wrapper-' . $dependent_field_name,
        $renderedField
      ));
    }

    return $response;
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

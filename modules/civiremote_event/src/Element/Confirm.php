<?php
/*
 * Copyright (C) 2023 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Drupal\civiremote_event\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for double-input of values.
 *
 * Formats as a pair of fields, which do not validate unless the two entered
 * values match.
 *
 * Properties:
 * - #field: The render array of the form field to confirm.
 * - #confirm_title: The title of the confirmation field (optional).
 *
 * @FormElement("confirm")
 */
final class Confirm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#field' => [],
      '#confirm_title' => NULL,
      '#input' => TRUE,
      '#markup' => '',
      '#process' => [
        [static::class, 'processConfirm'],
      ],
      '#theme_wrappers' => [
        'form_element',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state): array {
    if ($input === FALSE) {
      return $element['#default_value'] = [
        'value1' => $element['#field']['#default_value'] ?? NULL,
        'value2' => $element['#field']['#default_value'] ?? NULL,
      ];
    }

    /** @phpstan-var array<string, mixed> $input */
    return [
      'value1' => $input['value1'] ?? $element['#field']['#default_value'] ?? NULL,
      'value2' => $input['value2'] ?? $element['#field']['#default_value'] ?? NULL,
    ];
  }

  /**
   * Expand a confirm field into two fields.
   */
  public static function processConfirm(array &$element, FormStateInterface $formState, array &$form): array {
    if (isset($element['#field']['#name'])) {
      $element['#name'] = $element['#field']['#name'];
      unset($element['#field']['#name']);
    }

    $element['value1'] = [
      '#value' => $element['#value']['value1'],
    ] + $element['#field'];

    $element['value2'] = [
      '#title' => $element['#confirm_title'] ?? t('@fieldTitle (Confirm)', ['@fieldTitle' => $element['#field']['#title']]),
      '#value' => $element['#value']['value2'],
    ] + $element['#field'];

    $element['#element_validate'] = [
      [static::class, 'validateConfirm'],
    ];
    $element['#tree'] = TRUE;

    return $element;
  }

  public static function validateConfirm(array &$element, FormStateInterface $formState, array &$form): array {
    $value1 = $element['value1']['#value'];
    $value2 = $element['value2']['#value'];

    if ($value1 !== $value2) {
      $formState->setError($element['value2'], t('The confirmation does not match.'));
    }

    // Field must be converted from a two-element array into a single
    // value regardless of validation results.
    $formState->setValueForElement($element['value1'], NULL);
    $formState->setValueForElement($element['value2'], NULL);
    $formState->setValueForElement($element, $value1);

    return $element;
  }

}

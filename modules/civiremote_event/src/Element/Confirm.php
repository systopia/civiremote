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

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for double-input of values.
 *
 * Formats as a pair of fields, which do not validate unless the two entered
 * values match.
 *
 * Properties:
 * - #element: The render array of the input form element to confirm.
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
      '#element' => [],
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
   *
   * This implementation calls the value callbacks of the wrapped input
   * elements. It tries to resemble would have been done on a single field by
   * \Drupal\Core\Form\FormBuilder::handleInputElement(). There are so-called
   * "safe" value callbacks which are called even if the CSRF token is invalid.
   * As this method is only called on valid CSRF token this applies to the value
   * callbacks of the two fields this element wraps as well.
   *
   * @see \Drupal\Core\Form\FormBuilder::handleInputElement()
   */
  public static function valueCallback(&$element, $input, FormStateInterface $formState): array {
    if (array_key_exists('#value', $element['#element'])) {
      return [
        'value1' => $element['#element']['#value'],
        'value2' => $element['#element']['#value'],
      ];
    }

    $valueCallback = self::getValueCallback($element['#element']);

    if ($input === FALSE) {
      $value = $valueCallback($element['#element'], FALSE, $formState);
      if (NULL !== $value || !empty($element['#element']['#has_garbage_value'])) {
        return [
          'value1' => $value,
          'value2' => $value,
        ];
      }

      return $element['#default_value'] = [
        'value1' => $element['#element']['#default_value'] ?? '',
        'value2' => $element['#element']['#default_value'] ?? '',
      ];
    }

    /** @phpstan-var array<string, mixed> $input */
    return [
      'value1' => $valueCallback($element['#element'], $input['value1'] ?? NULL, $formState) ?? $input['value1'] ?? NULL,
      'value2' => $valueCallback($element['#element'], $input['value2'] ?? NULL, $formState) ?? $input['value2'] ?? NULL,
    ];
  }

  /**
   * Expand a confirm field into two fields.
   */
  public static function processConfirm(array &$element, FormStateInterface $formState, array &$form): array {
    if (isset($element['#element']['#name'])) {
      $element['#name'] = $element['#element']['#name'];
      unset($element['#element']['#name']);
    }

    $element['value1'] = [
      '#value' => $element['#value']['value1'],
    ] + $element['#element'];

    $element['value2'] = [
      '#title' => $element['#confirm_title'] ?? t('@fieldTitle (Confirm)', ['@fieldTitle' => $element['#element']['#title']]),
      '#value' => $element['#value']['value2'],
    ] + $element['#element'];

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

  /**
   * @return callable The value callback for the given form element.
   */
  private static function getValueCallback(array $element): callable {
    if (!isset($element['#type']) || !is_string($element['#type'])) {
      throw new \InvalidArgumentException('#type is not available on the form element to confirm');
    }

    /** @var \Drupal\Core\Render\ElementInfoManager $elementInfoManager */
    $elementInfoManager = \Drupal::service('plugin.manager.element_info');
    $info = $elementInfoManager->getInfo($element['#type']);
    if (!isset($info['#value_callback'])) {
      throw new \InvalidArgumentException(sprintf('"%s" is not a valid form element type', $element['#type']));
    }

    return $element['#value_callback'] ?? $info['#value_callback'];
  }

}

<?php

/*
 * Copyright (C) 2024 SYSTOPIA GmbH
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

namespace Drupal\civiremote_entity\Form\Control;

use Assert\Assertion;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civiremote_entity\CiviCRMPage\CiviCRMUrlStorageInterface;
use Drupal\civiremote_entity\Form\Control\Callbacks\FileValueCallback;
use Drupal\json_forms\Form\AbstractConcreteFormArrayFactory;
use Drupal\json_forms\Form\Control\ObjectArrayFactory;
use Drupal\json_forms\Form\Control\Util\BasicFormPropertiesFactory;
use Drupal\json_forms\Form\FormArrayFactoryInterface;
use Drupal\json_forms\JsonForms\Definition\Control\ControlDefinition;
use Drupal\json_forms\JsonForms\Definition\DefinitionInterface;

class FileArrayFactory extends AbstractConcreteFormArrayFactory {

  public static function getPriority(): int {
    return ObjectArrayFactory::getPriority() + 1;
  }

  private CiviCRMUrlStorageInterface $civiCRMUrlManager;

  public function __construct(CiviCRMUrlStorageInterface $civiCRMUrlManager) {
    $this->civiCRMUrlManager = $civiCRMUrlManager;
  }

  /**
   * {@inheritDoc}
   */
  public function createFormArray(
    DefinitionInterface $definition,
    FormStateInterface $formState,
    FormArrayFactoryInterface $formArrayFactory
  ): array {
    Assertion::isInstanceOf($definition, ControlDefinition::class);
    /** @var \Drupal\json_forms\JsonForms\Definition\Control\ControlDefinition $definition */

    $form = [
      'file' => [
        '#type' => 'file',
        '#value_callback' => FileValueCallback::class . '::convert',
      ] + BasicFormPropertiesFactory::createFieldProperties($definition, $formState),
    ];

    if (($form['file']['#default_value'] ?? NULL) instanceof \stdClass
      && is_string($form['file']['#default_value']->url ?? NULL)
      && is_string($form['file']['#default_value']->filename ?? NULL)
    ) {
      $url = $form['file']['#default_value']->url;
      $filename = $form['file']['#default_value']->filename;

      $form['file']['#required'] = FALSE;

      $form['link'] = [
        '#type' => 'link',
        '#title' => $filename,
        '#url' => $this->civiCRMUrlManager->addRemoteUrl($url, $filename),
        '#attributes' => ['target' => '_blank'],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      if (isset($form['file']['#states'])) {
        $form['link']['#states'] = $form['file']['#states'];
      }
    }

    return $form;
  }

  public function supportsDefinition(DefinitionInterface $definition): bool {
    return $definition instanceof ControlDefinition
      && 'object' === $definition->getType()
      && 'file' === $definition->getControlFormat();
  }

}

<?php

/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Drupal\civiremote_entity\Form\Control;

use Assert\Assertion;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civiremote_entity\CiviCRMPage\CiviCRMUrlStorageInterface;
use Drupal\json_forms\Form\AbstractConcreteFormArrayFactory;
use Drupal\json_forms\Form\Control\Rule\StatesArrayFactory;
use Drupal\json_forms\Form\FormArrayFactoryInterface;
use Drupal\json_forms\JsonForms\Definition\DefinitionInterface;

class InternalLinkArrayFactory extends AbstractConcreteFormArrayFactory {

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
    $label = $definition->getKeywordValue('label');
    Assertion::string($label);
    $url = $definition->getKeywordValue('url');
    Assertion::string($url);
    $description = $definition->getKeywordValue('description');
    Assertion::nullOrString($description);
    $filename = $definition->getKeywordValue('filename');
    Assertion::nullOrString($filename);

    if (NULL !== $description) {
      $description = ' - ' . $description;
    }

    $form = [
      '#type' => 'container',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      'link' => [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $this->civiCRMUrlManager->addRemoteUrl($url, $filename),
        '#attributes' => ['target' => '_blank'],
      ],
      'description' => ['#plain_text' => $description],
    ];

    if (NULL !== $definition->getRule()) {
      $statesArrayFactory = new StatesArrayFactory();
      $form['#states'] = $statesArrayFactory->createStatesArray($definition->getRule());
    }

    return $form;
  }

  public function supportsDefinition(DefinitionInterface $definition): bool {
    return 'InternalLink' === $definition->getType();
  }

}

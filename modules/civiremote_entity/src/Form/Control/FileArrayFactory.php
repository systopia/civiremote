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
use Drupal\civiremote_entity\CiviCRMPage\CiviCRMUrlStorageInterface;
use Drupal\civiremote_entity\Form\Control\Callbacks\FileCallbacks;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileStorageInterface;
use Drupal\json_forms\Form\AbstractConcreteFormArrayFactory;
use Drupal\json_forms\Form\Control\ObjectArrayFactory;
use Drupal\json_forms\Form\Control\Util\BasicFormPropertiesFactory;
use Drupal\json_forms\Form\FormArrayFactoryInterface;
use Drupal\json_forms\Form\Util\FormCallbackRegistrator;
use Drupal\json_forms\JsonForms\Definition\Control\ControlDefinition;
use Drupal\json_forms\JsonForms\Definition\DefinitionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FileArrayFactory extends AbstractConcreteFormArrayFactory {

  public function __construct(
    CiviCRMUrlStorageInterface $civiCRMUrlManager,
    AccountInterface $currentUser,
    FileStorageInterface $fileStorage,
    SessionInterface $session
  ) {
    $this->civiCRMUrlManager = $civiCRMUrlManager;
    $this->currentUser = $currentUser;
    $this->fileStorage = $fileStorage;
    $this->session = $session;
  }

  public static function getPriority(): int {
    return ObjectArrayFactory::getPriority() + 1;
  }

  private CiviCRMUrlStorageInterface $civiCRMUrlManager;

  private AccountInterface $currentUser;

  private FileStorageInterface $fileStorage;

  private SessionInterface $session;

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
      '#type' => 'managed_file',
      '#upload_location' => 'private://civiremote_entity/upload/',
      '#attached' => [
        'library' => [
          'file/drupal.file',
          'civiremote_entity/file-field',
        ],
      ],
    ] + BasicFormPropertiesFactory::createFieldProperties($definition, $formState);

    // @phpstan-ignore-next-line
    $form['#attributes']['class'][] = 'civiremote-entity-file';
    /** @var list<int|string> $elementKey */
    $elementKey = $form['#parents'];

    // If the default value was fetched from the temporary values, it should
    // be an array. If it was fetched from the field definition, it should be
    // an \stdClass.
    if (is_array($form['#default_value'] ?? NULL)) {
      $form['#default_value'] = (object) $form['#default_value'];
    }

    if (($form['#default_value'] ?? NULL) instanceof \stdClass
      && is_string($form['#default_value']->url ?? NULL)
      && is_string($form['#default_value']->filename ?? NULL)
    ) {
      // Use the file ID from submit values. (On first AJAX request the form
      // state isn't cached.)
      $input = $formState->getUserInput();
      // @phpstan-ignore offsetAccess.nonOffsetAccessible
      $initialFileId = NestedArray::getValue($input, $elementKey)['fids']
        ?? $this->getFileIdForDefaultValue($form['#default_value']);
      $formState->set(array_merge($elementKey, ['default_value']), $form['#default_value']);
      $formState->set(array_merge($elementKey, ['initial_file_id']), $initialFileId);
      $form['#default_value'] = [$initialFileId];
    }
    else {
      unset($form['#default_value']);
    }

    FormCallbackRegistrator::registerPreSchemaValidationCallback(
      $formState,
      $definition->getFullScope(),
      [FileCallbacks::class, 'convertValue'],
      $elementKey,
    );

    return $form;
  }

  public function supportsDefinition(DefinitionInterface $definition): bool {
    return $definition instanceof ControlDefinition
      && 'object' === $definition->getType()
      && 'file' === $definition->getControlFormat();
  }

  private function getFileIdForDefaultValue(\stdClass $defaultValue): string {
    // Stored as "non-permanent", i.e. is going to be removed by Drupal Cron.
    $file = $this->fileStorage->create([
      'uri' => $this->civiCRMUrlManager->addRemoteUrl($defaultValue->url, $defaultValue->filename)
        ->setAbsolute()->toString(),
      'filename' => $defaultValue->filename,
      'filemime' => $defaultValue->mimeType ?? 'application/octet-stream',
      'filesize' => $defaultValue->filesize ?? NULL,
      'uid' => $this->currentUser->id(),
    ]);
    $this->fileStorage->save($file);

    if ($this->currentUser->isAnonymous()) {
      /** @var array<int, string> $allowedTempFiles */
      $allowedTempFiles = $this->session->get('anonymous_allowed_file_ids', []);
      $allowedTempFiles[$file->id()] = $file->id();
      $this->session->set('anonymous_allowed_file_ids', $allowedTempFiles);
    }

    return (string) $file->id();
  }

}

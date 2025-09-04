<?php

/*
 * Copyright (C) 2025 SYSTOPIA GmbH
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

namespace Drupal\civiremote_entity\Form\Control\Callbacks;

use Assert\Assertion;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

final class FileCallbacks {

  /**
   * Converts the value set by the managed_file form element at the given key.
   *
   * The file ID will be converted to a URI.
   *
   * @phpstan-param array<int|string> $elementKey
   */
  public static function convertValue(FormStateInterface $formState, string $callbackKey, array $elementKey): void {
    /** @phpstan-var array<string|int> $fileIds */
    $fileIds = $formState->getValue($elementKey, []);
    if ([] === $fileIds) {
      // No file.
      $formState->unsetValue($elementKey);

      return;
    }

    $fileId = (string) $fileIds[0];

    if ($formState->get(array_merge($elementKey, ['initial_file_id'])) === $fileId) {
      // File not changed.
      $formState->setValue($elementKey, $formState->get(array_merge($elementKey, ['default_value'])));

      return;
    }

    // New file.
    $file = File::load($fileId);
    if (NULL === $file) {
      // Should not happen.
      $formState->setValue($elementKey, NULL);
      return;
    }

    assert(NULL !== $file->getFileUri());
    Assertion::readable($file->getFileUri());
    $content = file_get_contents($file->getFileUri());
    assert(FALSE !== $content);

    $formState->setValue($elementKey, (object) [
      'filename' => $file->getFilename(),
      'content' => base64_encode($content),
    ]);
  }

}

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

namespace Drupal\civiremote_entity\Form\Control\Callbacks;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileValueCallback {

  /**
   * @param array<int|string, mixed> $element
   * @param mixed $input
   *
   * @return mixed
   */
  public static function convert(array $element, $input, FormStateInterface $formState) {
    // @todo It's currently not possible to remove an existing file.
    $uploadedFile = self::getUploadedFile($element, $input);
    if (NULL === $uploadedFile) {
      $value = $element['#default_value'] ?? NULL;
      if (NULL === $value) {
        // Prevent empty string as value. Drupal sets an empty string in this
        // case if no value is set in the form state.
        $formState->setValueForElement($element, NULL);
      }
    }
    else {
      $value = [
        'filename' => $uploadedFile->getClientOriginalName(),
        'content' => base64_encode(self::getFileContent($uploadedFile)),
      ];

      unlink($uploadedFile->getRealPath());
    }

    return $value;
  }

  /**
   * Method for Drupal 9.5/Symfony 4.4 compatibility.
   *
   * Once Drupal 10 is minimum requirement this can be replaced by a call of
   * UploadedFile::getContent().
   */
  private static function getFileContent(UploadedFile $file): string {
    $content = file_get_contents($file->getPathname());

    if (FALSE === $content) {
      throw new \RuntimeException(sprintf('Could not get the content of the file "%s".', $file->getPathname()));
    }

    return $content;
  }

  /**
   * @param array<int|string, mixed> $element
   * @param mixed $input
   */
  private static function getUploadedFile(array $element, $input): ?UploadedFile {
    if (FALSE === $input) {
      return NULL;
    }

    /** @phpstan-var list<string> $parents */
    $parents = $element['#parents'];
    $elementName = array_shift($parents);
    /** @var array<string, \Symfony\Component\HttpFoundation\File\UploadedFile> $uploadedFiles */
    $uploadedFiles = \Drupal::request()->files->get('files', []);

    return $uploadedFiles[$elementName] ?? NULL;
  }

}

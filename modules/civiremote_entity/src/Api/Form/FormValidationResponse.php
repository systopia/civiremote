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

namespace Drupal\civiremote_entity\Api\Form;

class FormValidationResponse {

  /**
   * @phpstan-var array<string, non-empty-array<string>>
   */
  private array $errors;

  private bool $valid;

  /**
   * @param bool $valid
   * @param array<string, non-empty-array<string>> $errors
   */
  public function __construct(bool $valid, array $errors) {
    $this->errors = $errors;
    $this->valid = $valid;
  }

  /**
   * @phpstan-param array{valid: bool, errors?: array<string, non-empty-array<string>>} $value
   *
   * @return self
   */
  public static function fromApiResultValue(array $value): self {
    return new self($value['valid'], $value['errors'] ?? []);
  }

  /**
   * @return array<string, non-empty-array<string>>
   */
  public function getErrors(): array {
    return $this->errors;
  }

  public function isValid(): bool {
    return $this->valid;
  }

}

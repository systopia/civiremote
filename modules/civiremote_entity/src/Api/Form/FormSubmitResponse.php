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

class FormSubmitResponse {

  /**
   * @var array<int|string, mixed>
   */
  private array $response;

  /**
   * @param array<int|string, mixed> $value
   */
  public static function fromApiResultValue(array $value): self {
    return new self($value);
  }

  /**
   * @param array<int|string, mixed> $response
   */
  protected function __construct(array $response) {
    $this->response = $response;
  }

  public function getEntityId(): ?int {
    // @phpstan-ignore return.type
    return $this->get('entityId');
  }

  public function getMessage(): ?string {
    // @phpstan-ignore return.type
    return $this->get('message');
  }

  public function get(int|string $key, mixed $default = NULL): mixed {
    return $this->response[$key] ?? $default;
  }

  public function has(int|string $key): bool {
    return array_key_exists($key, $this->response);
  }

}

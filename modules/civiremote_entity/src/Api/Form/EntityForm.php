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

use Drupal\json_forms\Form\Util\JsonConverter;

class EntityForm {

  private \stdClass $jsonSchema;

  private \stdClass $uiSchema;

  /**
   * @phpstan-param array{jsonSchema: array<string, mixed>, uiSchema: array<string, mixed>} $value
   */
  public static function fromApiResultValue(array $value): self {
    $jsonSchema = JsonConverter::toStdClass($value['jsonSchema']);
    $uiSchema = JsonConverter::toStdClass($value['uiSchema']);

    return new self($jsonSchema, $uiSchema);
  }

  /**
   * @param \stdClass $jsonSchema
   * @param \stdClass $uiSchema
   */
  public function __construct(\stdClass $jsonSchema, \stdClass $uiSchema) {
    $this->jsonSchema = $jsonSchema;
    $this->uiSchema = $uiSchema;
  }

  public function getJsonSchema(): \stdClass {
    return $this->jsonSchema;
  }

  public function getUiSchema(): \stdClass {
    return $this->uiSchema;
  }

}

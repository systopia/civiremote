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

namespace Drupal\civiremote_entity\Api\Exception;

use CMRF\Core\Call;

final class ApiCallFailedException extends \RuntimeException implements ExceptionInterface {

  private Call $call;

  public static function fromCall(Call $call): self {
    /** @phpstan-var array{error_message: string, error_code: int|string} $reply */
    $reply = $call->getReply();

    return new self($call, $reply['error_message'], (int) $reply['error_code']);
  }

  public function __construct(Call $call, string $message = '', int $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->call = $call;
  }

  public function getCall(): Call {
    return $this->call;
  }

}

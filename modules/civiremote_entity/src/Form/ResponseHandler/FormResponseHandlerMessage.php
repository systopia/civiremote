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

namespace Drupal\civiremote_entity\Form\ResponseHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\civiremote_entity\Api\Form\FormSubmitResponse;
use Symfony\Component\HttpFoundation\Request;

class FormResponseHandlerMessage implements FormResponseHandlerInterface {

  use StringTranslationTrait;

  protected MessengerInterface $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  public function handleSubmitResponse(
    Request $request,
    FormSubmitResponse $submitResponse,
    FormStateInterface $formState
  ): void {
    if (NULL !== $submitResponse->getMessage()) {
      $this->messenger->addMessage($submitResponse->getMessage());
    }
  }

}

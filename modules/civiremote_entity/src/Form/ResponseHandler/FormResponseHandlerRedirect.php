<?php

/*
 * Copyright (C) 2025 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Drupal\civiremote_entity\Form\ResponseHandler;

use Drupal\civiremote_entity\Api\Form\FormSubmitResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirect to entity update page after entity creation, if possible.
 */
final class FormResponseHandlerRedirect implements FormResponseHandlerInterface {

  public function handleSubmitResponse(
    Request $request,
    FormSubmitResponse $submitResponse,
    FormStateInterface $formState
  ): void {
    if (NULL !== $submitResponse->getEntityId()) {
      $currentUrl = Url::createFromRequest($request);
      $path = $currentUrl->getInternalPath();
      if (str_contains($path, '/add/')) {
        $path = '/' . str_replace('/add/', '/' . $submitResponse->getEntityId() . '/update/', $path);
        $url = Url::fromUserInput($path);
        if ($url->isRouted()) {
          $formState->setRedirectUrl($url);
        }
      }
    }
  }

}

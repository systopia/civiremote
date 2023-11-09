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

namespace Drupal\civiremote_entity\Form\RequestHandler;

use Assert\Assertion;
use Drupal\civiremote_entity\Api\AbstractEntityApi;
use Drupal\civiremote_entity\Api\Form\EntityForm;
use Drupal\civiremote_entity\Api\Form\FormSubmitResponse;
use Drupal\civiremote_entity\Api\Form\FormValidationResponse;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\Request;

class EntityUpdateFormRequestHandler implements FormRequestHandlerInterface {

  protected AbstractEntityApi $entityApi;

  public function __construct(AbstractEntityApi $entityApi) {
    $this->entityApi = $entityApi;
  }

  public function getForm(Request $request): EntityForm {
    $routeMatch = RouteMatch::createFromRequest($request);
    $profile = $routeMatch->getParameter('profile');
    Assertion::string($profile);
    $id = $routeMatch->getParameter('id');
    Assertion::integerish($id);
    $id = (int) $id;

    return $this->entityApi->getUpdateForm($profile, $id);
  }

  public function validateForm(Request $request, array $data): FormValidationResponse {
    $routeMatch = RouteMatch::createFromRequest($request);
    $profile = $routeMatch->getParameter('profile');
    Assertion::string($profile);
    $id = $routeMatch->getParameter('id');
    Assertion::integerish($id);
    $id = (int) $id;

    return $this->entityApi->validateUpdateForm($profile, $id, $data);
  }

  public function submitForm(Request $request, array $data): FormSubmitResponse {
    $routeMatch = RouteMatch::createFromRequest($request);
    $profile = $routeMatch->getParameter('profile');
    Assertion::string($profile);
    $id = $routeMatch->getParameter('id');
    Assertion::integerish($id);
    $id = (int) $id;

    return $this->entityApi->submitUpdateForm($profile, $id, $data);
  }

}

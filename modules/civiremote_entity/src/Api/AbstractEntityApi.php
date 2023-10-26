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

declare(strict_types = 1);

namespace Drupal\civiremote_entity\Api;

use Drupal\civiremote_entity\Access\RemoteContactIdProviderInterface;
use Drupal\civiremote_entity\Api\Form\EntityForm;
use Drupal\civiremote_entity\Api\Form\FormSubmitResponse;
use Drupal\civiremote_entity\Api\Form\FormValidationResponse;

abstract class AbstractEntityApi {

  protected CiviCRMApiClientInterface $client;

  protected RemoteContactIdProviderInterface $remoteContactIdProvider;

  public function __construct(
    CiviCRMApiClientInterface $client,
    RemoteContactIdProviderInterface $remoteContactIdProvider
  ) {
    $this->client = $client;
    $this->remoteContactIdProvider = $remoteContactIdProvider;
  }

  /**
   * @phpstan-param array<int|string, mixed> $arguments JSON serializable.
   */
  public function getCreateForm(string $profile, array $arguments = []): EntityForm {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'getUpdateForm', [
      'profile' => $profile,
      'arguments' => $arguments,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return EntityForm::fromApiResultValue($result['values']);
  }

  /**
   * @phpstan-param array<int|string, mixed> $data JSON serializable.
   * @phpstan-param array<int|string, mixed> $arguments JSON serializable.
   */
  public function submitCreateForm(string $profile, array $data, array $arguments = []): FormSubmitResponse {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'submitUpdateForm', [
      'profile' => $profile,
      'data' => $data,
      'arguments' => $arguments,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return FormSubmitResponse::fromApiResultValue($result['values']);
  }

  /**
   * @phpstan-param array<int|string, mixed> $data JSON serializable.
   * @phpstan-param array<int|string, mixed> $arguments JSON serializable.
   */
  public function validateCreateForm(string $profile, array $data, array $arguments = []): FormValidationResponse {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'validateUpdateForm', [
      'profile' => $profile,
      'data' => $data,
      'arguments' => $arguments,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return FormValidationResponse::fromApiResultValue($result['values']);
  }

  public function getUpdateForm(string $profile, int $id): EntityForm {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'getUpdateForm', [
      'profile' => $profile,
      'id' => $id,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return EntityForm::fromApiResultValue($result['values']);
  }

  /**
   * @phpstan-param array<int|string, mixed> $data JSON serializable.
   */
  public function submitUpdateForm(string $profile, int $id, array $data): FormSubmitResponse {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'submitUpdateForm', [
      'profile' => $profile,
      'id' => $id,
      'data' => $data,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return FormSubmitResponse::fromApiResultValue($result['values']);
  }

  /**
   * @phpstan-param array<int|string, mixed> $data JSON serializable.
   */
  public function validateUpdateForm(string $profile, int $id, array $data): FormValidationResponse {
    $result = $this->client->executeV4($this->getRemoteEntityName(), 'validateUpdateForm', [
      'profile' => $profile,
      'id' => $id,
      'data' => $data,
      'remoteContactId' => $this->remoteContactIdProvider->getRemoteContactId(),
    ]);

    return FormValidationResponse::fromApiResultValue($result['values']);
  }

  abstract protected function getRemoteEntityName(): string;

}

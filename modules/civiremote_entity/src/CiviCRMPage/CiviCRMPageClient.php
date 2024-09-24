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

namespace Drupal\civiremote_entity\CiviCRMPage;

use Assert\Assertion;
use CMRF\Core\Core;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\civiremote_entity\Access\RemoteContactIdProviderInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @codeCoverageIgnore
 */
final class CiviCRMPageClient implements CiviCRMPageClientInterface {

  private const DEFAULT_CONNECT_TIMEOUT = 3.0;

  private const DEFAULT_TIMEOUT = 7.0;

  private string $apiKey;

  private ClientInterface $httpClient;

  private RemoteContactIdProviderInterface $remoteContactIdProvider;

  private string $siteKey;

  public static function create(
    Core $cmrfCore,
    ImmutableConfig $config,
    string $connectorConfigKey,
    ClientInterface $httpClient,
    RemoteContactIdProviderInterface $remoteContactIdProvider
  ): CiviCRMPageClientInterface {
    $connectorId = $config->get($connectorConfigKey);
    Assertion::string($connectorId);

    $profile = $cmrfCore->getConnectionProfile($connectorId);
    Assertion::string($profile['api_key'] ?? NULL);
    Assertion::string($profile['site_key'] ?? NULL);

    return new self($profile['api_key'], $httpClient, $remoteContactIdProvider, $profile['site_key']);
  }

  public function __construct(
    string $apiKey,
    ClientInterface $httpClient,
    RemoteContactIdProviderInterface $remoteContactIdProvider,
    string $siteKey
  ) {
    $this->apiKey = $apiKey;
    $this->httpClient = $httpClient;
    $this->remoteContactIdProvider = $remoteContactIdProvider;
    $this->siteKey = $siteKey;
  }

  /**
   * {@inheritDoc}
   */
  public function request(string $method, string $uri, array $options = []): ResponseInterface {
    // @phpstan-ignore-next-line
    $options['headers'] = array_merge($options['headers'] ?? [], $this->buildHeaders());
    $options['connect_timeout'] ??= self::DEFAULT_CONNECT_TIMEOUT;
    $options['timeout'] ??= self::DEFAULT_TIMEOUT;
    $options['http_errors'] ??= FALSE;

    return $this->httpClient->request($method, $uri, $options);
  }

  /**
   * @phpstan-return array<string, string>
   */
  private function buildHeaders(): array {
    return [
      'X-Civi-Auth' => 'Bearer ' . $this->apiKey,
      'X-Civi-Key' => $this->siteKey,
      'X-Civi-Remote-Contact-Id' => $this->remoteContactIdProvider->getRemoteContactIdOrNull() ?? '',
    ];
  }

}

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

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @codeCoverageIgnore
 */
class CiviCRMPageProxy implements CiviCRMPageProxyInterface {

  private const RETURNED_HEADERS = [
    'Content-Type',
    'Content-Length',
    'Content-Disposition',
  ];

  private CiviCRMPageClientInterface $client;

  private LoggerInterface $logger;

  public function __construct(CiviCRMPageClientInterface $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function get(string $uri): Response {
    try {
      $remoteResponse = $this->client->request('GET', $uri);
    }
    catch (GuzzleException $e) {
      $this->logger->error(
        'Loading "%uri" from CiviCRM failed: %message',
        [
          '%uri' => $uri,
          '%message' => $e->getMessage(),
        ]
      );

      throw new ServiceUnavailableHttpException(NULL, '', $e, $e->getCode());
    }

    if (Response::HTTP_NOT_FOUND === $remoteResponse->getStatusCode()) {
      throw new NotFoundHttpException();
    }

    if (Response::HTTP_UNAUTHORIZED === $remoteResponse->getStatusCode()) {
      $this->logger->error('Authentication at CiviCRM failed', [
        'uri' => $uri,
      ]);

      throw new ServiceUnavailableHttpException();
    }

    if (Response::HTTP_FORBIDDEN === $remoteResponse->getStatusCode()) {
      throw new AccessDeniedHttpException();
    }

    if (Response::HTTP_OK === $remoteResponse->getStatusCode()) {
      return new StreamedResponse(
        function () use ($remoteResponse) {
          $body = $remoteResponse->getBody();
          while (!$body->eof()) {
            echo $body->read(1024);
          }
        },
        Response::HTTP_OK,
        $this->buildResponseHeaders($remoteResponse),
      );
    }

    $this->logger->error(
      'Unexpected response while loading "%uri" from CiviCRM',
      [
        '%uri' => $uri,
        'statusCode' => $remoteResponse->getStatusCode(),
        'reasonPhrase' => $remoteResponse->getReasonPhrase(),
      ]
    );

    throw new ServiceUnavailableHttpException();
  }

  /**
   * @phpstan-return array<string, array<string>>
   */
  private function buildResponseHeaders(ResponseInterface $remoteResponse): array {
    $headers = [];
    foreach (self::RETURNED_HEADERS as $headerName) {
      if ($remoteResponse->hasHeader($headerName)) {
        $headers[$headerName] = $remoteResponse->getHeader($headerName);
      }
    }

    return $headers;
  }

}

<?php

/*
 * This file is part of songnguxyz/oauth-mediawiki.
 *
 * Copyright (c) 2024 songnguxyz.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 */

namespace Songnguxyz\OAuthMediaWiki\OAuth2;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class MediaWikiProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    // Matches /rest.php or /rest.php/<path> endpoints
    private const REST_PATTERN = '~/rest\\.php(?:/.*)?$~i';
    // Matches /api.php endpoints
    private const API_PATTERN = '~/api\\.php$~i';
    private const DISCOVERY_TIMEOUT = 5;
    private const DISCOVERY_MAX_BODY_LENGTH = 100000;
    private const DISCOVERY_CHUNK_SIZE = 8192;
    private const SCRIPT_PATH_MAX_LENGTH = 256;
    private const ERROR_STATUS_THRESHOLD = 400;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string|null
     */
    protected $userAgent;

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (!empty($options['userAgent'])) {
            $this->userAgent = $options['userAgent'];
        }

        if (isset($options['baseUrl'])) {
            $discoveredBaseUrl = $this->discoverScriptPathBaseUrl($options['baseUrl']);
            $this->baseUrl = $this->normalizeBaseUrl($discoveredBaseUrl ?? $options['baseUrl']);
        }
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->baseUrl . '/oauth2/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->baseUrl . '/oauth2/access_token';
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->baseUrl . '/oauth2/resource/profile';
    }

    /**
     * Returns the default scopes used by this provider.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array|string $data
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            $message = isset($data['error_description']) ? $data['error_description'] : (isset($data['error']) ? $data['error'] : 'Unknown error');
            throw new IdentityProviderException(
                $message,
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generates a resource owner object from a successful resource owner details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return MediaWikiResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        // Fetch block information from MediaWiki API
        $blockInfo = $this->fetchBlockInfo($token);
        
        // Merge block information into the response
        if ($blockInfo !== null) {
            $response = array_merge($response, $blockInfo);
        }
        
        return new MediaWikiResourceOwner($response);
    }

    /**
     * Attempt to discover the wiki's script path from the provided URL.
     *
     * Performs a bounded fetch of the supplied URL, scans the response for the MediaWiki
     * `wgScriptPath` configuration value, and returns null on network errors, malformed URLs,
     * missing matches, or when the URL already targets a script endpoint.
     *
     * @param string $baseUrl
     * @return string|null
     */
    protected function discoverScriptPathBaseUrl(string $baseUrl): ?string
    {
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '' || preg_match(self::REST_PATTERN, $baseUrl) || preg_match(self::API_PATTERN, $baseUrl)) {
            return null;
        }

        $requestUrl = $baseUrl;

        if (!preg_match('~^https?://~i', $requestUrl)) {
            $requestUrl = 'https://' . ltrim($requestUrl, '/');
        }

        try {
            $requestOptions = [
                'http_errors' => false,
                'timeout' => self::DISCOVERY_TIMEOUT,
            ];

            if ($this->userAgent !== null) {
                $requestOptions['headers']['User-Agent'] = $this->userAgent;
            }

            $response = $this->getHttpClient()->request('GET', $requestUrl, $requestOptions);

            if ($response->getStatusCode() >= self::ERROR_STATUS_THRESHOLD) {
                return null;
            }

            $bodyStream = $response->getBody();
            $body = '';
            $bodyLength = 0;

            $pattern = sprintf('~"wgScriptPath"\\s*:\\s*"((?:[^"\\\\]|\\\\.){1,%d})"~i', (int) self::SCRIPT_PATH_MAX_LENGTH);

            while (!$bodyStream->eof() && $bodyLength < self::DISCOVERY_MAX_BODY_LENGTH) {
                $chunk = $bodyStream->read(min(self::DISCOVERY_MAX_BODY_LENGTH - $bodyLength, self::DISCOVERY_CHUNK_SIZE));
                if ($chunk === '') {
                    break;
                }

                $body .= $chunk;
                $bodyLength += strlen($chunk);

                if (preg_match($pattern, $body, $matches)) {
                    $scriptPath = json_decode('"' . $matches[1] . '"');

                    if (!is_string($scriptPath)) {
                        return null;
                    }

                    $parsedUrl = parse_url($requestUrl);

                    if ($parsedUrl === false || !isset($parsedUrl['host']) || trim($parsedUrl['host']) === '') {
                        return null;
                    }

                    $scheme = $parsedUrl['scheme'] ?? 'https';
                    if (trim($scheme) === '') {
                        $scheme = 'https';
                    }
                    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

                    $normalizedScriptPath = trim($scriptPath, '/');
                    $scriptSuffix = $normalizedScriptPath === '' ? '' : '/' . $normalizedScriptPath;

                    return rtrim($scheme . '://' . $parsedUrl['host'] . $port . $scriptSuffix, '/');
                }
            }
        } catch (GuzzleException $e) {
            return null;
        }

        return null;
    }

    protected function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');

        if ($baseUrl === '') {
            return $baseUrl;
        }

        foreach ([self::REST_PATTERN, self::API_PATTERN] as $pattern) {
            if (preg_match($pattern, $baseUrl)) {
                return preg_replace($pattern, '/rest.php', $baseUrl);
            }
        }

        return $baseUrl . '/rest.php';
    }
    
    /**
     * Fetch block information for the authenticated user from MediaWiki API.
     *
     * @param AccessToken $token
     * @return array|null
     */
    protected function fetchBlockInfo(AccessToken $token)
    {
        try {
            // Construct the MediaWiki API URL for userinfo query
            // Parse the base URL to properly construct the API endpoint
            $parsedUrl = parse_url($this->baseUrl);
            
            if ($parsedUrl === false) {
                return null;
            }
            
            // Build the base path
            $path = $parsedUrl['path'] ?? '';
            
            // Replace /rest.php with /api.php if present, otherwise append /api.php
            if (strpos($path, '/rest.php') !== false) {
                $path = str_replace('/rest.php', '/api.php', $path);
            } else {
                $path = rtrim($path, '/') . '/api.php';
            }
            
            // Reconstruct the URL
            $apiUrl = ($parsedUrl['scheme'] ?? 'https') . '://' 
                    . ($parsedUrl['host'] ?? '') 
                    . ($parsedUrl['port'] ? ':' . $parsedUrl['port'] : '')
                    . $path;
            
            $url = $apiUrl . '?' . http_build_query([
                'action' => 'query',
                'meta' => 'userinfo',
                'uiprop' => 'blockinfo',
                'format' => 'json',
            ]);
            
            $request = $this->getAuthenticatedRequest('GET', $url, $token);
            $response = $this->getParsedResponse($request);
            
            // Extract block information from the response
            if (isset($response['query']['userinfo'])) {
                $userinfo = $response['query']['userinfo'];
                
                return [
                    'blocked' => isset($userinfo['blockid']),
                    'blockexpiry' => $userinfo['blockexpiry'] ?? null,
                    'blockreason' => $userinfo['blockreason'] ?? null,
                ];
            }
        } catch (IdentityProviderException $e) {
            // Authentication or API errors
            // Silently fail to allow authentication to continue
            // Security note: Failed block checks may allow blocked users to authenticate
            // In production, consider logging: error_log('MediaWiki block check failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Other errors
            // Silently fail to allow authentication to continue
            // Security note: Failed block checks may allow blocked users to authenticate
            // In production, consider logging: error_log('MediaWiki block check error: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Returns the default headers used by this provider.
     *
     * @return array
     */
    protected function getDefaultHeaders()
    {
        $headers = parent::getDefaultHeaders();

        if ($this->userAgent !== null) {
            $headers['User-Agent'] = $this->userAgent;
        }

        return $headers;
    }
}

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
use Psr\Http\Message\ResponseInterface;

class MediaWikiProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (isset($options['baseUrl'])) {
            $this->baseUrl = rtrim($options['baseUrl'], '/');
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
        return new MediaWikiResourceOwner($response);
    }
}

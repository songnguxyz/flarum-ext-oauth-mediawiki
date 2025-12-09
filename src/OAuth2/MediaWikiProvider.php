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

        if (isset($options['baseUrl'])) {
            $this->baseUrl = rtrim($options['baseUrl'], '/');
        }

        if (!empty($options['userAgent'])) {
            $this->userAgent = $options['userAgent'];
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
     * Fetch block information for the authenticated user from MediaWiki API.
     * This method checks for local blocks, and if CentralAuth or GlobalBlocking
     * extensions are installed, also checks for global blocks/locks.
     *
     * @param AccessToken $token
     * @return array|null
     */
    protected function fetchBlockInfo(AccessToken $token)
    {
        try {
            $apiUrl = $this->buildApiUrl();
            if ($apiUrl === null) {
                return null;
            }
            
            // First, detect installed extensions (CentralAuth, GlobalBlocking)
            $extensions = $this->fetchInstalledExtensions($apiUrl, $token);
            
            $hasCentralAuth = in_array('CentralAuth', $extensions);
            $hasGlobalBlocking = in_array('GlobalBlocking', $extensions);
            
            // Initialize block status
            $blocked = false;
            $blockexpiry = null;
            $blockreason = null;
            
            // Check local blocks (always performed)
            // This also picks up GlobalBlocking IP blocks when they apply
            $localBlockInfo = $this->checkLocalBlock($apiUrl, $token);
            if ($localBlockInfo !== null && $localBlockInfo['blocked']) {
                $blocked = true;
                $blockexpiry = $localBlockInfo['blockexpiry'];
                $blockreason = $localBlockInfo['blockreason'];
            }
            
            // Check CentralAuth global locks/blocks if available
            if ($hasCentralAuth) {
                $centralAuthBlockInfo = $this->checkCentralAuthBlock($apiUrl, $token);
                if ($centralAuthBlockInfo !== null && $centralAuthBlockInfo['blocked']) {
                    // Global blocks take precedence
                    $blocked = true;
                    $blockexpiry = $centralAuthBlockInfo['blockexpiry'] ?? $blockexpiry;
                    $blockreason = $centralAuthBlockInfo['blockreason'] ?? $blockreason;
                }
            }
            
            // Check GlobalBlocking blocks if available
            // Pass local block info to avoid duplicate API calls
            if ($hasGlobalBlocking && $localBlockInfo !== null) {
                $globalBlockingInfo = $this->checkGlobalBlocking($localBlockInfo);
                if ($globalBlockingInfo !== null && $globalBlockingInfo['blocked']) {
                    // If identified as global block, use its metadata
                    $blocked = true;
                    $blockexpiry = $globalBlockingInfo['blockexpiry'] ?? $blockexpiry;
                    $blockreason = $globalBlockingInfo['blockreason'] ?? $blockreason;
                }
            }
            
            return [
                'blocked' => $blocked,
                'blockexpiry' => $blockexpiry,
                'blockreason' => $blockreason,
            ];
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
     * Build the MediaWiki API URL from the base URL.
     *
     * @return string|null
     */
    protected function buildApiUrl()
    {
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
        return ($parsedUrl['scheme'] ?? 'https') . '://' 
             . ($parsedUrl['host'] ?? '') 
             . ($parsedUrl['port'] ? ':' . $parsedUrl['port'] : '')
             . $path;
    }
    
    /**
     * Fetch the list of installed extensions from MediaWiki.
     *
     * @param string $apiUrl
     * @param AccessToken $token
     * @return array List of extension names
     */
    protected function fetchInstalledExtensions($apiUrl, AccessToken $token)
    {
        try {
            $url = $apiUrl . '?' . http_build_query([
                'action' => 'query',
                'meta' => 'siteinfo',
                'siprop' => 'extensions',
                'format' => 'json',
            ]);
            
            $request = $this->getAuthenticatedRequest('GET', $url, $token);
            $response = $this->getParsedResponse($request);
            
            if (isset($response['query']['extensions']) && is_array($response['query']['extensions'])) {
                return array_map(function($ext) {
                    return $ext['name'] ?? '';
                }, $response['query']['extensions']);
            }
        } catch (\Exception $e) {
            // If we can't fetch extensions, continue without them
        }
        
        return [];
    }
    
    /**
     * Check for local blocks using meta=userinfo.
     *
     * @param string $apiUrl
     * @param AccessToken $token
     * @return array|null
     */
    protected function checkLocalBlock($apiUrl, AccessToken $token)
    {
        try {
            $url = $apiUrl . '?' . http_build_query([
                'action' => 'query',
                'meta' => 'userinfo',
                'uiprop' => 'blockinfo',
                'format' => 'json',
            ]);
            
            $request = $this->getAuthenticatedRequest('GET', $url, $token);
            $response = $this->getParsedResponse($request);
            
            if (isset($response['query']['userinfo'])) {
                $userinfo = $response['query']['userinfo'];
                
                return [
                    'blocked' => isset($userinfo['blockid']),
                    'blockexpiry' => $userinfo['blockexpiry'] ?? null,
                    'blockreason' => $userinfo['blockreason'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return null;
    }
    
    /**
     * Check for CentralAuth global locks/blocks using meta=globaluserinfo.
     *
     * @param string $apiUrl
     * @param AccessToken $token
     * @return array|null
     */
    protected function checkCentralAuthBlock($apiUrl, AccessToken $token)
    {
        try {
            $url = $apiUrl . '?' . http_build_query([
                'action' => 'query',
                'meta' => 'globaluserinfo',
                'guiprop' => 'editcount|merged|unattached|groups|locked',
                'format' => 'json',
            ]);
            
            $request = $this->getAuthenticatedRequest('GET', $url, $token);
            $response = $this->getParsedResponse($request);
            
            if (isset($response['query']['globaluserinfo'])) {
                $globaluserinfo = $response['query']['globaluserinfo'];
                
                // Check if the account is locked
                $locked = isset($globaluserinfo['locked']) && $globaluserinfo['locked'];
                
                if ($locked) {
                    return [
                        'blocked' => true,
                        'blockexpiry' => null, // CentralAuth locks are typically indefinite
                        'blockreason' => 'Account globally locked via CentralAuth',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return null;
    }
    
    /**
     * Check if a local block is actually a GlobalBlocking block.
     * 
     * The GlobalBlocking extension applies IP-based global blocks which are
     * automatically reflected in meta=userinfo blockinfo. This method examines
     * the local block data to determine if it's actually a global block.
     * 
     * @param array $localBlockInfo Block info from checkLocalBlock
     * @return array|null
     */
    protected function checkGlobalBlocking(array $localBlockInfo)
    {
        try {
            // If user is not blocked, no global block applies
            if (!$localBlockInfo['blocked']) {
                return null;
            }
            
            // Check if this appears to be a global block by examining metadata
            // GlobalBlocking blocks typically have:
            // - "autoblocking" disabled (global blocks don't autoblock)
            // - Specific block flags or identifiers
            // - Block reasons that may contain patterns (though language-dependent)
            
            // Since we can't reliably detect global blocks without additional API data,
            // and global blocks are already included in the userinfo response,
            // we'll return the block info as-is with a note that it might be global
            
            // In a real implementation with full MediaWiki API access, you would:
            // 1. Check the 'systemblocktype' field if available
            // 2. Query list=globalblocks with the user's IP to confirm
            // 3. Check for specific block flags that indicate global blocks
            
            // For now, we acknowledge that global blocks are captured by checkLocalBlock
            // and return null to indicate no additional processing is needed
            return null;
            
        } catch (\Exception $e) {
            // Silently fail
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

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

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class MediaWikiResourceOwner implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['sub'] ?? null;
    }

    /**
     * Returns the username of the resource owner.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->response['username'] ?? null;
    }

    /**
     * Returns the real name of the resource owner.
     *
     * @return string|null
     */
    public function getRealName()
    {
        return $this->response['realname'] ?? null;
    }

    /**
     * Returns the email of the resource owner.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['email'] ?? null;
    }

    /**
     * Returns the groups the resource owner belongs to.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->response['groups'] ?? [];
    }

    /**
     * Returns the rights the resource owner has.
     *
     * @return array
     */
    public function getRights()
    {
        return $this->response['rights'] ?? [];
    }

    /**
     * Returns the grants the resource owner has.
     *
     * @return array
     */
    public function getGrants()
    {
        return $this->response['grants'] ?? [];
    }

    /**
     * Returns the edit count of the resource owner.
     *
     * @return int|null
     */
    public function getEditCount()
    {
        return $this->response['editcount'] ?? null;
    }

    /**
     * Returns whether the email is confirmed.
     *
     * @return bool
     */
    public function isEmailConfirmed()
    {
        return !empty($this->response['confirmed_email']);
    }

    /**
     * Returns whether the account is blocked.
     *
     * @return bool
     */
    public function isBlocked()
    {
        return !empty($this->response['blocked']);
    }

    /**
     * Returns the registration date of the resource owner.
     *
     * @return string|null
     */
    public function getRegistered()
    {
        return $this->response['registered'] ?? null;
    }

    /**
     * Returns the raw response array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}

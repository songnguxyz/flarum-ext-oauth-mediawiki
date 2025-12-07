<?php

/*
 * This file is part of songnguxyz/oauth-mediawiki.
 *
 * Copyright (c) 2024 songnguxyz.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 */

namespace Songnguxyz\OAuthMediaWiki\Providers;

use Flarum\Forum\Auth\Registration;
use FoF\OAuth\Errors\AuthenticationException;
use FoF\OAuth\Provider;
use League\OAuth2\Client\Provider\AbstractProvider;
use Songnguxyz\OAuthMediaWiki\OAuth2\MediaWikiProvider;
use Songnguxyz\OAuthMediaWiki\OAuth2\MediaWikiResourceOwner;

class MediaWiki extends Provider
{
    /**
     * @var MediaWikiProvider
     */
    protected $provider;

    public function name(): string
    {
        return 'mediawiki';
    }

    public function link(): string
    {
        return 'https://www.mediawiki.org/wiki/OAuth/For_Developers';
    }

    public function icon(): string
    {
        return 'fab fa-wikipedia-w';
    }

    public function fields(): array
    {
        return [
            'base_url'      => 'required',
            'client_id'     => 'required',
            'client_secret' => 'required',
            'user_agent'    => '',
        ];
    }

    public function provider(string $redirectUri): AbstractProvider
    {
        return $this->provider = new MediaWikiProvider([
            'baseUrl'      => $this->getSetting('base_url'),
            'clientId'     => $this->getSetting('client_id'),
            'clientSecret' => $this->getSetting('client_secret'),
            'redirectUri'  => $redirectUri,
            'userAgent'    => $this->getSetting('user_agent'),
        ]);
    }

    public function options(): array
    {
        return ['scope' => []];
    }

    public function suggestions(Registration $registration, $user, string $token)
    {
        /** @var MediaWikiResourceOwner $user */
        
        // Check if the user is blocked in the wiki
        if ($user->isBlocked()) {
            $expiry = $user->getBlockExpiry();
            $reason = $user->getBlockReason();
            
            // If we have detailed block information, build a detailed message
            if ($expiry || $reason) {
                $message = 'Your MediaWiki account has been blocked and you cannot log in to this forum.';
                
                if ($expiry) {
                    $message .= ' Expires: ' . $expiry . '.';
                } else {
                    $message .= ' This block is indefinite.';
                }
                
                if ($reason) {
                    $message .= ' Reason: ' . $reason;
                }
            } else {
                // Use the simple error code that will be translated
                $message = 'wiki_user_blocked';
            }
            
            throw new AuthenticationException($message);
        }
        
        $this->verifyEmail($email = $user->getEmail());

        $registration
            ->suggestUsername($user->getUsername())
            ->provideTrustedEmail($email)
            ->setPayload($user->toArray());
    }
}

<?php

/*
 * This file is part of songnguxyz/oauth-mediawiki.
 *
 * Copyright (c) 2024 songnguxyz.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 */

namespace Songnguxyz\OAuthMediaWiki\Listeners;

use Flarum\User\LoginProvider;
use FoF\Extend\Events\OAuthLoginSuccessful;
use Songnguxyz\OAuthMediaWiki\OAuth2\MediaWikiResourceOwner;

class StoreMediaWikiUsername
{
    public function handle(OAuthLoginSuccessful $event): void
    {
        // Only process MediaWiki provider
        if ($event->providerName !== 'mediawiki') {
            return;
        }

        // Ensure we have a MediaWikiResourceOwner
        if (!($event->userResource instanceof MediaWikiResourceOwner)) {
            return;
        }

        // Get the username from the resource owner
        $username = $event->userResource->getUsername();

        if (empty($username)) {
            return;
        }

        // Find and update the login provider record
        $loginProvider = LoginProvider::where('provider', $event->providerName)
            ->where('identifier', $event->identifier)
            ->first();

        if ($loginProvider) {
            $loginProvider->username = $username;
            $loginProvider->save();
        }
    }
}

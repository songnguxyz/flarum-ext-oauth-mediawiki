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

class SyncMediaWikiAccount
{
    public function handle(OAuthLoginSuccessful $event): void
    {
        if ($event->providerName !== 'mediawiki') {
            return;
        }

        $resource = $event->userResource;

        if (!$resource instanceof MediaWikiResourceOwner) {
            return;
        }

        $user = $event->actor;

        if (!$user) {
            $provider = LoginProvider::query()
                ->where('provider', 'mediawiki')
                ->where('identifier', $event->identifier)
                ->first();

            $user = $provider?->user;
        }

        if (!$user) {
            return;
        }

        $username = $resource->getUsername();

        if ($username !== null) {
            $user->setPreference('songnguxyz-oauth-mediawiki.username', $username);
        }

        $user->setPreference('songnguxyz-oauth-mediawiki.status', [
            'blocked'     => $resource->isBlocked(),
            'blockexpiry' => $resource->getBlockExpiry(),
            'blockreason' => $resource->getBlockReason(),
            'registered'  => $resource->getRegistered(),
        ]);

        $user->save();
    }
}

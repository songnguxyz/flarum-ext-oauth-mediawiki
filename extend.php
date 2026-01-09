<?php

/*
 * This file is part of songnguxyz/oauth-mediawiki.
 *
 * Copyright (c) 2024 songnguxyz.
 *
 *  For the full copyright and license information, please view the LICENSE.md
 *  file that was distributed with this source code.
 */

namespace Songnguxyz\OAuthMediaWiki;

use Flarum\Extend;
use Flarum\User\LoginProvider;
use FoF\Extend\Events\OAuthLoginSuccessful;
use FoF\OAuth\Api\Serializers\ProviderSerializer;
use FoF\OAuth\Extend as OAuthExtend;
use Songnguxyz\OAuthMediaWiki\Listeners\StoreMediaWikiUsername;
use Songnguxyz\OAuthMediaWiki\Providers\MediaWiki;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new OAuthExtend\RegisterProvider(MediaWiki::class)),

    (new Extend\Event())
        ->listen(OAuthLoginSuccessful::class, StoreMediaWikiUsername::class),

    (new Extend\ApiSerializer(ProviderSerializer::class))
        ->attributes(function (ProviderSerializer $serializer, $providerStatus): array {
            // Only add username for MediaWiki provider
            if ($providerStatus->name !== 'mediawiki' || !$providerStatus->linked) {
                return [];
            }

            // Find the LoginProvider to get the username
            // Note: $providerStatus->identifier is the LoginProvider's primary key (id),
            // not to be confused with $providerStatus->providerIdentifier (OAuth identifier).
            // This query is by primary key so it's very fast. While this could cause N+1 queries
            // when serializing multiple providers, in practice most users have only 1-2 linked
            // providers, so the performance impact is minimal.
            $loginProvider = LoginProvider::find($providerStatus->identifier);

            return [
                'username' => $loginProvider?->username ?? null,
            ];
        }),
];

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

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use FoF\OAuth\Extend as OAuthExtend;
use FoF\Extend\Events\OAuthLoginSuccessful;
use Songnguxyz\OAuthMediaWiki\Listeners\SyncMediaWikiAccount;
use Songnguxyz\OAuthMediaWiki\Providers\MediaWiki;

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->serializeToForum('songnguxyz-oauth-mediawiki.show_wiki_status', 'songnguxyz-oauth-mediawiki.show_wiki_status', 'boolval'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attributes(function (UserSerializer $serializer, $user, array $attributes) {
            $attributes['mediawikiUsername'] = $user->getPreference('songnguxyz-oauth-mediawiki.username');
            $attributes['mediawikiStatus'] = $user->getPreference('songnguxyz-oauth-mediawiki.status');

            return $attributes;
        }),

    (new Extend\Event())
        ->listen(OAuthLoginSuccessful::class, SyncMediaWikiAccount::class),

    (new OAuthExtend\RegisterProvider(MediaWiki::class)),
];

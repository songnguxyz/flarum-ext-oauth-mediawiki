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
use FoF\OAuth\Extend as OAuthExtend;
use Songnguxyz\OAuthMediaWiki\Providers\MediaWiki;

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new OAuthExtend\RegisterProvider(MediaWiki::class)),
];

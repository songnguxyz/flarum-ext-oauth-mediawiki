# Log In With MediaWiki
![Log In With MediaWiki](https://flarum.org/extension/songnguxyz/oauth-mediawiki/open-graph-image)
![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/songnguxyz/oauth-mediawiki.svg)](https://packagist.org/packages/songnguxyz/oauth-mediawiki) [![Total Downloads](https://img.shields.io/packagist/dt/songnguxyz/oauth-mediawiki.svg)](https://packagist.org/packages/songnguxyz/oauth-mediawiki)

Log in to your Flarum forum with MediaWiki OAuth2. An addon for [FoF OAuth](https://github.com/friendsofflarum/oauth).

This extension enables users to authenticate with any MediaWiki installation that supports OAuth2 (MediaWiki 1.35+, with the [OAuth](https://mediawiki.org/wiki/Extension:OAuth) extension installed).

## Installation

Install with composer:

```sh
composer require songnguxyz/oauth-mediawiki
php flarum migrate
php flarum cache:clear
```

## Updating

```sh
composer update songnguxyz/oauth-mediawiki
php flarum cache:clear
```

## Setup

1) Enable the [OAuth extension](https://www.mediawiki.org/wiki/Extension:OAuth) on your MediaWiki installation. See [MediaWiki OAuth documentation](https://www.mediawiki.org/wiki/OAuth/For_Developers) for details.

2) Register an OAuth2 consumer on your MediaWiki:
   - Go to `Special:OAuthConsumerRegistration/propose` on your wiki
   - Select "**OAuth 2.0**" as the OAuth protocol version (`Propose an OAuth 2.0 client`)
   - Fill in the required details, including the callback URL from your Flarum OAuth settings
   - Request the necessary grants (at minimum: `mwoauth-authonly` for authentication)
   - Note down the **Client Key** and **Client Secret** provided

3) In your Flarum admin panel, enable this extension and configure:
   - **MediaWiki Base URL**: Your MediaWiki REST API base URL (e.g., `https://yourwiki.example.com/w/rest.php`). This is the URL where the OAuth2 endpoints are available.
   - **Client Key**: The client Key from step 2
   - **Client Secret**: The client secret from step 2

4) Log in with MediaWiki!

## Links

- [Packagist](https://packagist.org/packages/songnguxyz/oauth-mediawiki)
- [GitHub](https://github.com/songnguxyz/flarum-ext-oauth-mediawiki)
- [MediaWiki OAuth Documentation](https://www.mediawiki.org/wiki/OAuth/For_Developers)
- [Help translate the extension!](https://hosted.weblate.org/projects/flarum-ext-oauth-mediawiki/)

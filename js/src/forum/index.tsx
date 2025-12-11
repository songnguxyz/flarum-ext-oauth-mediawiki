import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ProviderInfo from 'flarum/extensions/fof-oauth/forum/components/ProviderInfo';
import LabelValue from 'flarum/common/components/LabelValue';

app.initializers.add('songnguxyz/oauth-mediawiki', () => {
  // Extend the ProviderInfo component to add MediaWiki username display
  extend(ProviderInfo.prototype, 'providerInfoItems', function (items) {
    const provider = this.attrs.provider;

    // Only add username field for MediaWiki provider
    if (provider.name() !== 'mediawiki' || !provider.linked()) {
      return;
    }

    // Check if username attribute exists
    const username = provider.data.attributes?.username;

    if (username) {
      // Add username field to the provider info items
      items.add(
        'mediawiki-username',
        <LabelValue
          label={app.translator.trans('songnguxyz-oauth-mediawiki.forum.user.settings.linked-account.username-label')}
          value={username}
        />,
        85 // Priority between identification (80) and lastLogin (90)
      );
    }
  });
});

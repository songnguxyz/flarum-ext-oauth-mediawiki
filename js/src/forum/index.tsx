import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import UserCard from 'flarum/forum/components/UserCard';
import LabelValue from 'flarum/common/components/LabelValue';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import { components } from '@fof-oauth';

const { LinkStatus, ProviderInfo } = components;

app.initializers.add('songnguxyz/oauth-mediawiki/forum', () => {
  override(LinkStatus.prototype, 'statusView', function () {
    const provider = this.attrs.provider;

    return <ProviderInfo provider={provider} user={this.attrs.user} />;
  });

  extend(ProviderInfo.prototype, 'providerInfoItems', function (items: ItemList<Mithril.Children>, provider: any) {
    if (provider.name() !== 'mediawiki') return;

    const user = this.attrs.user as User | undefined;
    const username = user?.attribute<string>('mediawikiUsername');

    if (username) {
      items.add(
        'identification',
        <LabelValue
          label={app.translator.trans('songnguxyz-oauth-mediawiki.forum.linked_accounts.username_label')}
          value={username}
        />,
        85
      );
    }
  });

  extend(UserCard.prototype, 'infoItems', function (items: ItemList<Mithril.Children>) {
    const user = this.attrs.user;
    const editCount = user.attribute<number | undefined>('mediawikiEditCount');
    const showStatus = app.forum.attribute<boolean>('songnguxyz-oauth-mediawiki.show_wiki_status');

    if (!showStatus || editCount === undefined || editCount === null) return;

    const username = user.attribute<string | undefined>('mediawikiUsername');

    const label = app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.edit_count.label', {
      username: username || app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.edit_count.unknown_username'),
    });

    items.add(
      'mediawiki-editcount',
      <LabelValue label={label} value={editCount.toLocaleString()} />,
      50
    );
  });
});

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
    const status = user.attribute<any>('mediawikiStatus');
    const showStatus = app.forum.attribute<boolean>('songnguxyz-oauth-mediawiki.show_wiki_status');

    if (!showStatus || !status) return;

    const username = user.attribute<string | undefined>('mediawikiUsername');

    const parts: string[] = [];

    if (status.blocked) {
      parts.push(app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.blocked'));

      if (status.blockexpiry) {
        parts.push(app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.blocked_until', { time: status.blockexpiry }));
      }

      if (status.blockreason) {
        parts.push(app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.block_reason', { reason: status.blockreason }));
      }
    } else {
      parts.push(app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.active'));
    }

    const label = app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.label', {
      username: username || app.translator.trans('songnguxyz-oauth-mediawiki.forum.profile.status.unknown_username'),
    });

    items.add(
      'mediawiki-status',
      <LabelValue label={label} value={parts.join(' ')} />,
      50
    );
  });
});

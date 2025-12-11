import app from 'flarum/admin/app';
import { ConfigureWithOAuthPage } from '@fof-oauth';

app.initializers.add('songnguxyz/oauth-mediawiki', () => {
  app.extensionData.for('songnguxyz-oauth-mediawiki').registerPage(ConfigureWithOAuthPage);
  app.extensionData
    .for('songnguxyz-oauth-mediawiki')
    .registerSetting({
      setting: 'songnguxyz-oauth-mediawiki.show_wiki_status',
      label: app.translator.trans('songnguxyz-oauth-mediawiki.admin.settings.show_wiki_status'),
      type: 'boolean',
    });
});

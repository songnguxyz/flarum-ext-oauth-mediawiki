import app from 'flarum/admin/app';
import { ConfigureWithOAuthPage } from '@fof-oauth';

app.initializers.add('songnguxyz/oauth-mediawiki', () => {
  app.extensionData.for('songnguxyz-oauth-mediawiki').registerPage(ConfigureWithOAuthPage);
});

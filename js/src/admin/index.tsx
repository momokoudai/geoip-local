import app from 'flarum/admin/app';
import GeoipLocalSettingsPage from './components/GeoipLocalSettingsPage';

export { default as extend } from './extend';

app.initializers.add('momokoudai-geoip-local', () => {
  app.extensionData
    .for('momokoudai-geoip-local')
    .registerPage(GeoipLocalSettingsPage);
});

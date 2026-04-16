import app from 'flarum/admin/app';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';

app.initializers.add('momokoudai-geoip-local', () => {
  app.extensionData.for('momokoudai-geoip-local')
    .registerSetting({
      setting: 'momokoudai-geoip-local.driver',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.driver_label'),
      type: 'select',
      options: {
        'maxmind-mmdb': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_maxmind_mmdb'),
        'dbip-mmdb': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_dbip_mmdb'),
        'ip2location-bin': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_ip2location_bin'),
      },
      default: 'maxmind-mmdb',
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.db_path',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_label'),
      help: app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_help'),
      type: 'text',
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.maxmind.account_id',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_account_id_label'),
      type: 'text',
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.maxmind.license_key',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_license_key_label'),
      type: 'text',
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.auto_update.custom_url',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_label'),
      help: app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_help'),
      type: 'text',
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.auto_update.enabled',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_enabled_label'),
      type: 'boolean',
      default: false,
    })
    .registerSetting({
      setting: 'momokoudai-geoip-local.auto_update.frequency_hours',
      label: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_label'),
      help: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_help'),
      type: 'number',
      default: 24,
    })
    .registerPage(GeoipLocalSettingsPage);
});

class GeoipLocalSettingsPage {
  driver: Stream<string>;
  dbPath: Stream<string>;
  maxmindAccountId: Stream<string>;
  maxmindLicenseKey: Stream<string>;
  autoUpdateEnabled: Stream<boolean>;
  autoUpdateFrequency: Stream<number>;
  customUrl: Stream<string>;
  isUpdating: Stream<boolean>;

  oninit() {
    this.driver = Stream(app.data.settings['momokoudai-geoip-local.driver'] || 'maxmind-mmdb');
    this.dbPath = Stream(app.data.settings['momokoudai-geoip-local.db_path'] || '');
    this.maxmindAccountId = Stream(app.data.settings['momokoudai-geoip-local.maxmind.account_id'] || '');
    this.maxmindLicenseKey = Stream(app.data.settings['momokoudai-geoip-local.maxmind.license_key'] || '');
    this.autoUpdateEnabled = Stream(app.data.settings['momokoudai-geoip-local.auto_update.enabled'] === '1');
    this.autoUpdateFrequency = Stream(parseInt(app.data.settings['momokoudai-geoip-local.auto_update.frequency_hours'] || '24'));
    this.customUrl = Stream(app.data.settings['momokoudai-geoip-local.auto_update.custom_url'] || '');
    this.isUpdating = Stream(false);
  }

  view() {
    const isMaxMind = this.driver() === 'maxmind-mmdb';

    return (
      <div className="container">
        <h2>{app.translator.trans('momokoudai-geoip-local.admin.settings.title')}</h2>

        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.driver_label')}</label>
            <select className="FormControl" onchange={(e: Event) => this.driver((e.target as HTMLSelectElement).value)}>
              <option value="maxmind-mmdb" selected={this.driver() === 'maxmind-mmdb'}>
                {app.translator.trans('momokoudai-geoip-local.admin.settings.driver_maxmind_mmdb')}
              </option>
              <option value="dbip-mmdb" selected={this.driver() === 'dbip-mmdb'}>
                {app.translator.trans('momokoudai-geoip-local.admin.settings.driver_dbip_mmdb')}
              </option>
              <option value="ip2location-bin" selected={this.driver() === 'ip2location-bin'}>
                {app.translator.trans('momokoudai-geoip-local.admin.settings.driver_ip2location_bin')}
              </option>
            </select>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_label')}</label>
            <input
              className="FormControl"
              type="text"
              value={this.dbPath()}
              oninput={(e: InputEvent) => this.dbPath((e.target as HTMLInputElement).value)}
            />
            <div className="helpText">{app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_help')}</div>
          </div>

          {isMaxMind && (
            <>
              <div className="Form-group">
                <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_account_id_label')}</label>
                <input
                  className="FormControl"
                  type="text"
                  value={this.maxmindAccountId()}
                  oninput={(e: InputEvent) => this.maxmindAccountId((e.target as HTMLInputElement).value)}
                />
              </div>

              <div className="Form-group">
                <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_license_key_label')}</label>
                <input
                  className="FormControl"
                  type="password"
                  value={this.maxmindLicenseKey()}
                  oninput={(e: InputEvent) => this.maxmindLicenseKey((e.target as HTMLInputElement).value)}
                />
              </div>
            </>
          )}

          <div className="Form-group">
            <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_label')}</label>
            <input
              className="FormControl"
              type="text"
              value={this.customUrl()}
              oninput={(e: InputEvent) => this.customUrl((e.target as HTMLInputElement).value)}
              placeholder="https://example.com/database.mmdb"
            />
            <div className="helpText">{app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_help')}</div>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.manual_update_label')}</label>
            <button
              className="Button"
              disabled={this.isUpdating()}
              onclick={() => this.manualUpdate()}
            >
              {this.isUpdating()
                ? app.translator.trans('momokoudai-geoip-local.admin.settings.updating')
                : app.translator.trans('momokoudai-geoip-local.admin.settings.manual_update_button')}
            </button>
            <div className="helpText">{app.translator.trans('momokoudai-geoip-local.admin.settings.manual_update_help')}</div>
          </div>

          <div className="Form-group">
            <label>
              <input
                type="checkbox"
                checked={this.autoUpdateEnabled()}
                onchange={(e: Event) => this.autoUpdateEnabled((e.target as HTMLInputElement).checked)}
              />{' '}
              {app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_enabled_label')}
            </label>
          </div>

          {this.autoUpdateEnabled() && (
            <div className="Form-group">
              <label>{app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_label')}</label>
              <input
                className="FormControl"
                type="number"
                min="1"
                max="336"
                value={this.autoUpdateFrequency()}
                oninput={(e: InputEvent) => this.autoUpdateFrequency(parseInt((e.target as HTMLInputElement).value))}
              />
              <div className="helpText">{app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_help')}</div>
            </div>
          )}

          <div className="Form-group">
            <button className="Button Button--primary" onclick={() => this.saveSettings()}>
              {app.translator.trans('core.admin.settings.submit_button')}
            </button>
          </div>
        </div>
      </div>
    );
  }

  async saveSettings() {
    try {
      await app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/settings',
        body: {
          'momokoudai-geoip-local.driver': this.driver(),
          'momokoudai-geoip-local.db_path': this.dbPath(),
          'momokoudai-geoip-local.maxmind.account_id': this.maxmindAccountId(),
          'momokoudai-geoip-local.maxmind.license_key': this.maxmindLicenseKey(),
          'momokoudai-geoip-local.auto_update.enabled': this.autoUpdateEnabled() ? '1' : '0',
          'momokoudai-geoip-local.auto_update.frequency_hours': String(this.autoUpdateFrequency()),
          'momokoudai-geoip-local.auto_update.custom_url': this.customUrl(),
        },
      });

      app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
    } catch (error) {
      console.error('Save failed:', error);
      app.alerts.show({ type: 'error' }, app.translator.trans('core.lib.error.generic_message'));
    }
  }

  async manualUpdate() {
    this.isUpdating(true);
    try {
      const result = await app.request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/geoip-local/update-db',
        body: { force: true },
      });

      if (result.ok) {
        app.alerts.show({ type: 'success' }, app.translator.trans('momokoudai-geoip-local.admin.settings.update_success'));
      } else {
        app.alerts.show({ type: 'error' }, result.error || app.translator.trans('momokoudai-geoip-local.admin.settings.update_failed'));
      }
    } catch (error: any) {
      console.error('Update failed:', error);
      const message = error.response?.errors?.[0]?.detail || app.translator.trans('momokoudai-geoip-local.admin.settings.update_error');
      app.alerts.show({ type: 'error' }, message);
    } finally {
      this.isUpdating(false);
    }
  }
}

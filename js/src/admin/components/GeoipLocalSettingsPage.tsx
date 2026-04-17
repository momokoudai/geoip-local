import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import app from 'flarum/admin/app';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import Stream from 'flarum/common/utils/Stream';

export default class GeoipLocalSettingsPage extends ExtensionPage {
  isUpdating: Stream<boolean> = Stream(false);

  content() {
    return (
      <div className="GeoipLocalSettingsPage">
        <div className="container">
          <div className="Form">
            {this.settingsItems().toArray()}
            <div className="Form-group">{this.submitButton()}</div>
          </div>
        </div>
      </div>
    );
  }

  settingsItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const driver = this.setting('momokoudai-geoip-local.driver')();
    const customUrl = this.setting('momokoudai-geoip-local.auto_update.custom_url')();

    items.add(
      'general',
      <div className="Section">
        <h3>{app.translator.trans('momokoudai-geoip-local.admin.settings.general_heading')}</h3>
        {this.generalItems().toArray()}
      </div>
    );

    if (driver === 'maxmind-mmdb' && !customUrl) {
      items.add(
        'maxmind',
        <div className="Section">
          <h3>{app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_heading')}</h3>
          {this.maxmindItems().toArray()}
        </div>
      );
    }

    items.add(
      'manual-update',
      <div className="Section">
        <h3>{app.translator.trans('momokoudai-geoip-local.admin.settings.manual_update_heading')}</h3>
        {this.manualUpdateItems().toArray()}
      </div>
    );

    items.add(
      'auto-update',
      <div className="Section">
        <h3>{app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_heading')}</h3>
        {this.autoUpdateItems().toArray()}
      </div>
    );

    return items;
  }

  generalItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'driver',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.driver',
        type: 'select',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.driver_label'),
        options: {
          'maxmind-mmdb': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_maxmind_mmdb'),
          'dbip-mmdb': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_dbip_mmdb'),
          'ip2location-bin': app.translator.trans('momokoudai-geoip-local.admin.settings.driver_ip2location_bin'),
        },
        default: 'maxmind-mmdb',
      })
    );

    items.add(
      'db-path',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.db_path',
        type: 'string',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_label'),
        help: app.translator.trans('momokoudai-geoip-local.admin.settings.db_path_help'),
      })
    );

    items.add(
      'custom-url',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.auto_update.custom_url',
        type: 'string',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_label'),
        help: app.translator.trans('momokoudai-geoip-local.admin.settings.custom_url_help'),
      })
    );

    return items;
  }

  maxmindItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'account-id',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.maxmind.account_id',
        type: 'string',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_account_id_label'),
      })
    );

    items.add(
      'license-key',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.maxmind.license_key',
        type: 'string',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.maxmind_license_key_label'),
      })
    );

    return items;
  }

  autoUpdateItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'enabled',
      this.buildSettingComponent({
        setting: 'momokoudai-geoip-local.auto_update.enabled',
        type: 'boolean',
        label: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_enabled_label'),
        default: false,
      })
    );

    if (this.setting('momokoudai-geoip-local.auto_update.enabled')()) {
      items.add(
        'frequency',
        this.buildSettingComponent({
          setting: 'momokoudai-geoip-local.auto_update.frequency_hours',
          type: 'number',
          label: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_label'),
          help: app.translator.trans('momokoudai-geoip-local.admin.settings.auto_update_frequency_help'),
          default: 24,
        })
      );
    }

    return items;
  }

  manualUpdateItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'update-button',
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
    );

    return items;
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

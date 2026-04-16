import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import ItemList from 'flarum/common/utils/ItemList';
import Tooltip from 'flarum/common/components/Tooltip';
import getFlagEmojiUrl from './getFlagEmojiUrl';

const SPECIAL: Record<string, { zh: string; en: string }> = {
  HK: { zh: '中国香港', en: 'Hong Kong, China' },
  MO: { zh: '中国澳门', en: 'Macau, China' },
  TW: { zh: '中国台湾', en: 'Taiwan, China' },
};

function isZh() {
  const l = (app.translator.getLocale?.() || app.translator.locale || '').toLowerCase();
  return l.startsWith('zh');
}

export default function overrideFoFGeoipFlagsForCnRegions() {
  extend(CommentPost.prototype, 'headerItems', function (items: ItemList) {
    if (!app.forum.attribute('fof-geoip.showFlag')) return;

    // FoF GeoIP attaches ipInfo relationship.
    // @ts-ignore
    const ipInfo = this.attrs?.post?.ipInfo?.();
    const code = ipInfo?.countryCode?.();
    if (!code || !SPECIAL[code]) return;

    const url = getFlagEmojiUrl('CN');
    if (!url) return;

    const label = isZh() ? SPECIAL[code].zh : SPECIAL[code].en;

    // Replace FoF's country flag item (key: 'country').
    // @ts-ignore
    items.remove?.('country');

    items.add(
      'country',
      <Tooltip text={label}>
        <img src={url} alt={label} height="16" loading="lazy" />
      </Tooltip>,
      100
    );
  });
}


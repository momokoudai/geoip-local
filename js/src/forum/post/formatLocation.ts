import app from 'flarum/forum/app';

type GeoipLocalPayload = {
  countryCode?: string | null;
  flagCountryCode?: string | null;
  subdivisionCode?: string | null; // ISO 3166-2, e.g. CN-SD
  specialCnRegion?: 'hong_kong' | 'macau' | 'taiwan' | null;
};

const CN_SPECIAL_ZH: Record<string, string> = {
  hong_kong: '中国香港',
  macau: '中国澳门',
  taiwan: '中国台湾',
};

const CN_SPECIAL_EN: Record<string, string> = {
  hong_kong: 'Hong Kong, China',
  macau: 'Macau, China',
  taiwan: 'Taiwan, China',
};

const CN_PROVINCES: Record<string, { zh: string; en: string }> = {
  'CN-AH': { zh: '安徽', en: 'Anhui' },
  'CN-BJ': { zh: '北京', en: 'Beijing' },
  'CN-CQ': { zh: '重庆', en: 'Chongqing' },
  'CN-FJ': { zh: '福建', en: 'Fujian' },
  'CN-GD': { zh: '广东', en: 'Guangdong' },
  'CN-GS': { zh: '甘肃', en: 'Gansu' },
  'CN-GX': { zh: '广西', en: 'Guangxi' },
  'CN-GZ': { zh: '贵州', en: 'Guizhou' },
  'CN-HA': { zh: '河南', en: 'Henan' },
  'CN-HB': { zh: '湖北', en: 'Hubei' },
  'CN-HE': { zh: '河北', en: 'Hebei' },
  'CN-HI': { zh: '海南', en: 'Hainan' },
  'CN-HL': { zh: '黑龙江', en: 'Heilongjiang' },
  'CN-HN': { zh: '湖南', en: 'Hunan' },
  'CN-JL': { zh: '吉林', en: 'Jilin' },
  'CN-JS': { zh: '江苏', en: 'Jiangsu' },
  'CN-JX': { zh: '江西', en: 'Jiangxi' },
  'CN-LN': { zh: '辽宁', en: 'Liaoning' },
  'CN-NM': { zh: '内蒙古', en: 'Inner Mongolia' },
  'CN-NX': { zh: '宁夏', en: 'Ningxia' },
  'CN-QH': { zh: '青海', en: 'Qinghai' },
  'CN-SC': { zh: '四川', en: 'Sichuan' },
  'CN-SD': { zh: '山东', en: 'Shandong' },
  'CN-SH': { zh: '上海', en: 'Shanghai' },
  'CN-SN': { zh: '陕西', en: 'Shaanxi' },
  'CN-SX': { zh: '山西', en: 'Shanxi' },
  'CN-TJ': { zh: '天津', en: 'Tianjin' },
  'CN-XJ': { zh: '新疆', en: 'Xinjiang' },
  'CN-XZ': { zh: '西藏', en: 'Tibet' },
  'CN-YN': { zh: '云南', en: 'Yunnan' },
  'CN-ZJ': { zh: '浙江', en: 'Zhejiang' },
};

function localeIsZh() {
  const l = (app.translator.getLocale?.() || app.translator.locale || '').toLowerCase();
  return l.startsWith('zh');
}

function countryName(countryCode: string): string | null {
  try {
    // Intl.DisplayNames is supported in modern browsers; fallback to code.
    // @ts-ignore
    const dn = new Intl.DisplayNames([app.translator.getLocale?.() || app.translator.locale || 'en'], { type: 'region' });
    // @ts-ignore
    return dn.of(countryCode) || countryCode;
  } catch {
    return countryCode;
  }
}

export default function formatLocation(payload: GeoipLocalPayload | null | undefined): { text: string | null; tooltip?: string | null } {
  if (!payload) return { text: null };

  const isZh = localeIsZh();

  // HK/MO/TW special required rendering.
  if (payload.specialCnRegion) {
    return { text: isZh ? CN_SPECIAL_ZH[payload.specialCnRegion] : CN_SPECIAL_EN[payload.specialCnRegion] };
  }

  const cc = payload.countryCode || payload.flagCountryCode;
  if (!cc) return { text: null };

  // Mainland China: show province short name; English as "Xxx, China".
  if (cc === 'CN' && payload.subdivisionCode && CN_PROVINCES[payload.subdivisionCode]) {
    const p = CN_PROVINCES[payload.subdivisionCode];
    if (isZh) return { text: p.zh };
    return { text: `${p.en}, China` };
  }

  // Foreign: show country name.
  return { text: countryName(cc) };
}


<?php

namespace momokoudai\GeoipLocal\Support;

final class ChinaProvinceMap
{
    /**
     * ISO 3166-2:CN subdivision code => [zh, en]
     * We keep English as "Xxx Province" / municipality name, so the UI can
     * render "Shandong Province, China" as requested.
     */
    public const MAP = [
        'CN-AH' => ['安徽', 'Anhui'],
        'CN-BJ' => ['北京', 'Beijing'],
        'CN-CQ' => ['重庆', 'Chongqing'],
        'CN-FJ' => ['福建', 'Fujian'],
        'CN-GD' => ['广东', 'Guangdong'],
        'CN-GS' => ['甘肃', 'Gansu'],
        'CN-GX' => ['广西', 'Guangxi'],
        'CN-GZ' => ['贵州', 'Guizhou'],
        'CN-HA' => ['河南', 'Henan'],
        'CN-HB' => ['湖北', 'Hubei'],
        'CN-HE' => ['河北', 'Hebei'],
        'CN-HI' => ['海南', 'Hainan'],
        'CN-HK' => ['香港', 'Hong Kong'],
        'CN-HL' => ['黑龙江', 'Heilongjiang'],
        'CN-HN' => ['湖南', 'Hunan'],
        'CN-JL' => ['吉林', 'Jilin'],
        'CN-JS' => ['江苏', 'Jiangsu'],
        'CN-JX' => ['江西', 'Jiangxi'],
        'CN-LN' => ['辽宁', 'Liaoning'],
        'CN-MO' => ['澳门', 'Macau'],
        'CN-NM' => ['内蒙古', 'Inner Mongolia'],
        'CN-NX' => ['宁夏', 'Ningxia'],
        'CN-QH' => ['青海', 'Qinghai'],
        'CN-SC' => ['四川', 'Sichuan'],
        'CN-SD' => ['山东', 'Shandong'],
        'CN-SH' => ['上海', 'Shanghai'],
        'CN-SN' => ['陕西', 'Shaanxi'],
        'CN-SX' => ['山西', 'Shanxi'],
        'CN-TJ' => ['天津', 'Tianjin'],
        'CN-TW' => ['台湾', 'Taiwan'],
        'CN-XJ' => ['新疆', 'Xinjiang'],
        'CN-XZ' => ['西藏', 'Tibet'],
        'CN-YN' => ['云南', 'Yunnan'],
        'CN-ZJ' => ['浙江', 'Zhejiang'],
    ];

    public static function exists(string $subdivisionCode): bool
    {
        return array_key_exists($subdivisionCode, self::MAP);
    }

    public static function zh(string $subdivisionCode): ?string
    {
        return self::MAP[$subdivisionCode][0] ?? null;
    }

    public static function en(string $subdivisionCode): ?string
    {
        return self::MAP[$subdivisionCode][1] ?? null;
    }
}


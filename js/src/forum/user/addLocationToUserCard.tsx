import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserCard from 'flarum/forum/components/UserCard';
import formatLocation from '../post/formatLocation';

export default function addLocationToUserCard() {
  extend(UserCard.prototype, 'infoItems', function (items) {
    // @ts-ignore
    const post = this.attrs?.post;
    const ipInfo = post?.ipInfo?.();
    const postUser = post?.user?.();

    // 复用 fof/geoip 的权限逻辑
    if (!((ipInfo && postUser && postUser.showIPCountry()) || app.session.user?.canSeeCountry?.())) return;

    const geoipLocal = post?.geoipLocal?.();
    const { text } = formatLocation(geoipLocal);
    if (!text) return;

    items.add('geoipLocalLocation', <span className="GeoipLocal-Location GeoipLocal-Location--usercard">{text}</span>, 50);
  });
}
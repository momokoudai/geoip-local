import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostMeta from 'flarum/forum/components/PostMeta';
import formatLocation from './formatLocation';

export default function addLocationToPostMeta() {
  extend(PostMeta.prototype, 'items', function (items) {
    // @ts-ignore
    const post = this.attrs.post;
    const ipInfo = post.ipInfo?.();
    const postUser = post.user?.();

    // 复用 fof/geoip 的权限逻辑
    if (!((ipInfo && postUser && postUser.showIPCountry()) || app.session.user?.canSeeCountry?.())) return;

    const geoipLocal = post?.geoipLocal?.();
    const { text } = formatLocation(geoipLocal);
    if (!text) return;

    items.add(
      'geoipLocalLocation',
      <span className="GeoipLocal-Location GeoipLocal-Location--meta">{text}</span>,
      5
    );
  });
}
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import ItemList from 'flarum/common/utils/ItemList';
import formatLocation from './formatLocation';

export default function addLocationToPostHeader() {
  extend(CommentPost.prototype, 'headerItems', function (items: ItemList) {
    // @ts-ignore
    const post = this.attrs?.post;
    const ipInfo = post.ipInfo?.();
    const postUser = post.user?.();

    // 复用 fof/geoip 的权限逻辑：检查 showIPCountry 或 canSeeCountry
    if (!((ipInfo && postUser && postUser.showIPCountry()) || app.session.user?.canSeeCountry?.())) return;

    const geoipLocal = post?.geoipLocal?.();
    const { text } = formatLocation(geoipLocal);
    if (!text) return;

    items.add(
      'geoipLocalLocation',
      <span className="GeoipLocal-Location GeoipLocal-Location--header">
        <span className="GeoipLocal-Location__badge">{text}</span>
      </span>,
      65
    );
  });
}

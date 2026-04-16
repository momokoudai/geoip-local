import { extend } from 'flarum/common/extend';
import PostUser from 'flarum/forum/components/PostUser';
import UserCard from 'flarum/forum/components/UserCard';

type VnodeLike = any;

function walk(vnode: VnodeLike, fn: (v: VnodeLike) => void) {
  if (!vnode) return;
  fn(vnode);
  const children = vnode.children;
  if (Array.isArray(children)) {
    children.forEach((c) => walk(c, fn));
  } else if (children) {
    walk(children, fn);
  }
}

export default function ensurePostPassedToUserCard() {
  extend(PostUser.prototype, 'view', function (vdom: VnodeLike) {
    // @ts-ignore
    const post = this.attrs?.post;
    if (!post) return vdom;

    walk(vdom, (node) => {
      if (node?.tag === UserCard) {
        node.attrs = node.attrs || {};
        node.attrs.post = post;
      }
    });

    return vdom;
  });
}


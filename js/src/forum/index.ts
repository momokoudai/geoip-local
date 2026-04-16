import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';

import addLocationToPostMeta from './post/addLocationToPostMeta';
import addLocationToPostHeader from './post/addLocationToPostHeader';
import addLocationToUserCard from './user/addLocationToUserCard';
import ensurePostPassedToUserCard from './user/ensurePostPassedToUserCard';
import overrideFoFGeoipFlagsForCnRegions from './fof/overrideFoFGeoipFlagsForCnRegions';

app.initializers.add('momokoudai/geoip-local', () => {
  // Ensure Post model can read geoipLocal attribute.
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (Post.prototype as any).geoipLocal = function () {
    return this.attribute('geoipLocal') as any;
  };

  addLocationToPostMeta();
  addLocationToPostHeader();
  ensurePostPassedToUserCard();
  addLocationToUserCard();
  overrideFoFGeoipFlagsForCnRegions();
});

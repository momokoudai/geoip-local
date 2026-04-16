const config = require('flarum-webpack-config');

module.exports = config({
  // Tell webpack to treat fof/geoip as external (provided by Flarum at runtime)
  externals: {
    'fof/geoip/common/util/getFlagEmojiUrl': 'fof/geoip/common/util/getFlagEmojiUrl',
  },
});

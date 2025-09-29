const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");

webpackConfig.entry = {
  ...webpackConfig.entry,
  admin: "./src/admin.js",
};

module.exports = webpackConfig;

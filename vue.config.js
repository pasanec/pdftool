module.exports = {
  chainWebpack: config => {
    config.module
      .rule('ts')
      .test(/\.tsx?$/)
      .use('babel-loader')
      .loader('babel-loader');
  }
};

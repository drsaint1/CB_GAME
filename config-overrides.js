module.exports = function override(config) {
  // Locate source-map-loader and exclude node_modules
  config.module.rules = config.module.rules.map((rule) => {
    if (
      rule.use &&
      rule.use.find(
        (loader) => loader.loader && loader.loader.includes("source-map-loader")
      )
    ) {
      return {
        ...rule,
        exclude: [/node_modules/],
      };
    }
    return rule;
  });

  // Add fallbacks for missing polyfills
  config.resolve = {
    ...config.resolve,
    fallback: {
      crypto: require.resolve("crypto-browserify"),
      stream: require.resolve("stream-browserify"),
      https: require.resolve("https-browserify"),
      zlib: require.resolve("browserify-zlib"),
      http: require.resolve("stream-http"),
    },
  };

  return config;
};

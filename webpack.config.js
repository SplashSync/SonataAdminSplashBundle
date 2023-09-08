var Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Clean Build Directory
    .cleanupOutputBeforeBuild()
    // Automatically Inject jQuery
    .autoProvidejQuery()
    // directory where compiled assets will be stored
    .setOutputPath('src/Resources/public/js/')
    // public path used by the web server to access the output path
    .setPublicPath('/bundles/splashadmin/js')
    // Splash Sonata Admin Assets
    .addEntry('admin', './src/Resources/javascript/admin.js')
    .setManifestKeyPrefix("Splash")
    .disableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();

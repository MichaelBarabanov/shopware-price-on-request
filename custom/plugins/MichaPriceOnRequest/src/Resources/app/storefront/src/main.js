import MichaPriceOnRequestPlugin from './example-plugin/example-plugin.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('MichaPriceOnRequest', MichaPriceOnRequestPlugin, '[data-micha-price-on-request]');
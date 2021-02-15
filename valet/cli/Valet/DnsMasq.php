<?php

namespace Valet;

class DnsMasq
{
    public $brew, $cli, $files, $configuration;

    public $dnsmasqMasterConfigFile = BREW_PREFIX . '/etc/dnsmasq.conf';
    public $dnsmasqSystemConfDir    = BREW_PREFIX . '/etc/dnsmasq.d';

    /**
     * Create a new DnsMasq instance.
     */
    public function __construct(Brew $brew, CommandLine $cli, Filesystem $files, Configuration $configuration)
    {
        $this->cli           = $cli;
        $this->brew          = $brew;
        $this->files         = $files;
        $this->configuration = $configuration;
    }

    /**
     * Install and configure DnsMasq.
     *
     * @return void
     */
    public function install($tld = 'test')
    {
        // For DnsMasq, we enable its feature of loading *.conf from /usr/local/etc/dnsmasq.d/
        // and then we put a valet config file in there to point to the user's home .config/valet/dnsmasq.d
        // This allows Valet to make changes to our own files without needing to modify the core dnsmasq configs
        $this->ensureUsingDnsmasqDForConfigs();

        $this->createDnsmasqTldConfigFile($tld);

        info('Valet is configured to serve for TLD [.' . $tld . ']');
    }

    /**
     * Forcefully uninstall dnsmasq.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->brew->stopService('dnsmasq');
        $this->brew->uninstallFormula('dnsmasq');
        $this->cli->run('rm -rf ' . BREW_PREFIX . '/etc/dnsmasq.d/dnsmasq-valet.conf');
        $tld = $this->configuration->read()['tld'];
        $this->files->unlink($this->resolverPath . '/' . $tld);
    }

    /**
     * Tell Homebrew to restart dnsmasq
     *
     * @return void
     */
    public function restart()
    {
        $this->brew->restartService('dnsmasq');
    }

    /**
     * Ensure the DnsMasq configuration primary config is set to read custom configs
     *
     * @return void
     */
    public function ensureUsingDnsmasqDForConfigs()
    {
        info('Updating Dnsmasq configuration...');

        $this->files->ensureDirExists(VALET_HOME_PATH . '/dnsmasq.d', user());
        $this->files->ensureDirExists(VALET_HOME_PATH . '/dnsmasq-internal.d', user());
    }

    /**
     * Create the TLD-specific dnsmasq config file
     * @param  string  $tld
     * @return void
     */
    public function createDnsmasqTldConfigFile($tld)
    {
        $tldConfigFile         = $this->dnsmasqUserConfigDir() . 'tld-' . $tld . '.conf';
        $tldInternalConfigFile = $this->dnsmasqInternalConfigDir() . 'tld-' . $tld . '.conf';

        $this->files->putAsUser($tldConfigFile, 'address=/.' . $tld . '/127.0.0.1' . PHP_EOL);
        $this->files->putAsUser($tldInternalConfigFile, 'address=/.' . $tld . '/' . BUTLER_WEBSERVER_IP . PHP_EOL);
    }

    /**
     * Update the TLD/domain resolved by DnsMasq.
     *
     * @param  string  $oldTld
     * @param  string  $newTld
     * @return void
     */
    public function updateTld($oldTld, $newTld)
    {
        $this->files->unlink($this->resolverPath . '/' . $oldTld);
        $this->files->unlink($this->dnsmasqUserConfigDir() . 'tld-' . $oldTld . '.conf');
        $this->files->unlink($this->dnsmasqInternalConfigDir() . 'tld-' . $oldTld . '.conf');

        $this->install($newTld);
    }

    /**
     * Get the custom configuration path.
     *
     * @return string
     */
    public function dnsmasqUserConfigDir()
    {
        return VALET_HOME_PATH . '/dnsmasq.d/';
    }

    public function dnsmasqInternalConfigDir()
    {
        return VALET_HOME_PATH . '/dnsmasq-internal.d/';
    }
}

#!/usr/bin/env php
<?php

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} else {
    require getenv('HOME') . '/.composer/vendor/autoload.php';
}

use function Valet\info;
use function Valet\output;
use function Valet\table;
use function Valet\warning;
use Illuminate\Container\Container;
use Silly\Application;

/**
 * Relocate config dir to ~/.config/valet/ if found in old location.
 */
if (is_dir(VALET_LEGACY_HOME_PATH) && !is_dir(VALET_HOME_PATH)) {
    Configuration::createConfigurationDirectory();
}

/**
 * Create the application.
 */
Container::setInstance(new Container);

$version = '0.0.5';

$app = new Application('Laravel Valet (Butler)', $version);

/**
 * Prune missing directories and symbolic links on every command.
 */
if (is_dir(VALET_HOME_PATH)) {
    Configuration::prune();

    Site::pruneLinks();
}

/**
 * Install Valet and any required services.
 */
$app->command('install', function () {
    DnsMasq::install(Configuration::read()['tld']);

    output(PHP_EOL . '<info>Valet installed successfully!</info>');
})->descriptions('Install the Valet services');

/**
 * Most commands are available only if valet is installed.
 */
if (is_dir(VALET_HOME_PATH)) {
    /**
     * Upgrade helper: ensure the tld config exists
     */
    if (empty(Configuration::read()['tld'])) {
        Configuration::writeBaseConfiguration();
    }

    /**
     * Get or set the TLD currently being used by Valet.
     */
    $app->command('tld [tld]', function ($tld = null) {
        if ($tld === null) {
            return output(Configuration::read()['tld']);
        }

        DnsMasq::updateTld(
            $oldTld = Configuration::read()['tld'], $tld = trim($tld, '.')
        );

        Configuration::updateKey('tld', $tld);

        Site::resecureForNewTld($oldTld, $tld);

        info('Your Valet TLD has been updated to [' . $tld . '].');
    }, ['domain'])->descriptions('Get or set the TLD used for Valet sites.');

    /**
     * Add the current working directory to the paths configuration.
     */
    $app->command('park [path]', function ($path = null) {
        Configuration::addPath($path ?: getcwd());

        info(($path === null ? "This" : "The [{$path}]") . " directory has been added to Valet's paths.");
    })->descriptions('Register the current working (or specified) directory with Valet');

    /**
     * Get all the current sites within paths parked with 'park {path}'
     */
    $app->command('parked', function () {
        $parked = Site::parked();

        table(['Site', 'SSL', 'URL', 'Path'], $parked->all());
    })->descriptions('Display all the current sites within parked paths');

    /**
     * Remove the current working directory from the paths configuration.
     */
    $app->command('forget [path]', function ($path = null) {
        Configuration::removePath($path ?: getcwd());

        info(($path === null ? "This" : "The [{$path}]") . " directory has been removed from Valet's paths.");
    }, ['unpark'])->descriptions('Remove the current working (or specified) directory from Valet\'s list of paths');

    /**
     * Register a symbolic link with Valet.
     */
    $app->command('link [name] [--secure]', function ($name, $secure) {
        $linkPath = Site::link(getcwd(), $name = $name ?: basename(getcwd()));

        info('A [' . $name . '] symbolic link has been created in [' . $linkPath . '].');

        if ($secure) {
            $this->runCommand('secure ' . $name);
        }
    })->descriptions('Link the current working directory to Valet');

    /**
     * Display all of the registered symbolic links.
     */
    $app->command('links', function () {
        $links = Site::links();

        table(['Site', 'SSL', 'URL', 'Path'], $links->all());
    })->descriptions('Display all of the registered Valet links');

    /**
     * Unlink a link from the Valet links directory.
     */
    $app->command('unlink [name]', function ($name) {
        info('The [' . Site::unlink($name) . '] symbolic link has been removed.');
    })->descriptions('Remove the specified Valet link');

    /**
     * Secure the given domain with a trusted TLS certificate.
     */
    $app->command('secure [domain]', function ($domain = null) {
        $url = ($domain ?: Site::host(getcwd())) . '.' . Configuration::read()['tld'];

        Site::secure($url);

        info('The [' . $url . '] site has been secured with a fresh TLS certificate.');
    })->descriptions('Secure the given domain with a trusted TLS certificate');

    /**
     * Stop serving the given domain over HTTPS and remove the trusted TLS certificate.
     */
    $app->command('unsecure [domain]', function ($domain = null) {
        $url = ($domain ?: Site::host(getcwd())) . '.' . Configuration::read()['tld'];

        Site::unsecure($url);

        info('The [' . $url . '] site will now serve traffic over HTTP.');
    })->descriptions('Stop serving the given domain over HTTPS and remove the trusted TLS certificate');

    /**
     * Create an Nginx proxy config for the specified domain
     */
    $app->command('proxy domain host', function ($domain, $host) {

        Site::proxyCreate($domain, $host);

    })->descriptions('Create an Nginx proxy site for the specified host. Useful for docker, mailhog etc.');

    /**
     * Delete an Nginx proxy config
     */
    $app->command('unproxy domain', function ($domain) {

        Site::proxyDelete($domain);

    })->descriptions('Delete an Nginx proxy config.');

    /**
     * Display all of the sites that are proxies.
     */
    $app->command('proxies', function () {
        $proxies = Site::proxies();

        table(['Site', 'SSL', 'URL', 'Host'], $proxies->all());
    })->descriptions('Display all of the proxy sites');

    /**
     * Determine which Valet driver the current directory is using.
     */
    $app->command('which', function () {
        require __DIR__ . '/drivers/require.php';

        $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

        if ($driver) {
            info('This site is served by [' . get_class($driver) . '].');
        } else {
            warning('Valet could not determine which driver to use for this site.');
        }
    })->descriptions('Determine which Valet driver serves the current working directory');

    /**
     * Display all of the registered paths.
     */
    $app->command('paths', function () {
        $paths = Configuration::read()['paths'];

        if (count($paths) > 0) {
            output(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            info('No paths have been registered.');
        }
    })->descriptions('Get all of the paths registered with Valet');

    /**
     * Generate a publicly accessible URL for your project.
     */
    $app->command('share', function () {
        warning("It looks like you are running `cli/valet.php` directly, please use the `valet` script in the project root instead.");
    })->descriptions('Generate a publicly accessible URL for your project');

    /**
     * Echo the currently tunneled URL.
     */
    // $app->command('fetch-share-url [domain]', function ($domain = null) {
    //     output(Ngrok::currentTunnelUrl($domain ?: Site::host(getcwd()) . '.' . Configuration::read()['tld']));
    // })->descriptions('Get the URL to the current Ngrok tunnel');

    /**
     * Start the daemon services.
     */
    $app->command('start', function () {

    })->descriptions('Start the Butler services');

    /**
     * Restart the daemon services.
     */
    $app->command('restart', function () {

    })->descriptions('Restart the Butler services');

    /**
     * Reload the daemon services.
     */
    $app->command('reload', function () {

    })->descriptions('Reload the Butler services (Use this after .env change)');

    /**
     * Stop the daemon services.
     */
    $app->command('stop', function () {

    })->descriptions('Stop the Butler services');

    /**
     * Determine if this is the latest release of Valet.
     */
    $app->command('on-latest-version', function () use ($version) {
        if (Valet::onLatestVersion($version)) {
            output('Yes');
        } else {
            output(sprintf('Your version of Valet (%s) is not the latest version available.', $version));
            output('Upgrade instructions can be found in the docs: https://laravel.com/docs/valet#upgrading-valet');
        }
    })->descriptions('Determine if this is the latest version of Valet');

    /**
     * Install the sudoers.d entries so password is no longer required.
     */
    $app->command('trust [--off]', function () {

    })->descriptions('Add sudoers files for Brew and Valet to make Valet commands run without passwords', [
        '--off' => 'Remove the sudoers files so normal sudo password prompts are required.',
    ]);

    /**
     * Configure or display the directory-listing setting.
     */
    $app->command('directory-listing [status]', function ($status = null) {
        $key    = 'directory-listing';
        $config = Configuration::read();

        if (in_array($status, ['on', 'off'])) {
            $config[$key] = $status;
            Configuration::write($config);
            return output('Directory listing setting is now: ' . $status);
        }

        $current = isset($config[$key]) ? $config[$key] : 'off';
        output('Directory listing is ' . $current);
    })->descriptions('Determine directory-listing behavior. Default is off, which means a 404 will display.', [
        'status' => 'on or off. (default=off) will show a 404 page; [on] will display a listing if project folder exists but requested URI not found',
    ]);

    /**
     * Output diagnostics to aid in debugging Valet.
     */
    $app->command('diagnose [-p|--print] [--plain]', function ($print, $plain) {
        info('Running diagnostics... (this may take a while)');

        Diagnose::run($print, $plain);

        info('Diagnostics output has been copied to your clipboard.');
    })->descriptions('Output diagnostics to aid in debugging Valet.', [
        '--print' => 'print diagnostics output while running',
        '--plain' => 'format clipboard output as plain text',
    ]);
}

/**
 * Load all of the Valet extensions.
 */
foreach (Valet::extensions() as $extension) {
    include $extension;
}

/**
 * Run the application.
 */
$app->run();

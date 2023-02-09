<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

$config = [
    'basedir' => dirname(__DIR__),
    'nightly' => in_array('--nightly', $argv),
    'lsr'     => in_array('--lsr', $argv),
    'nc25'    => in_array('--nc25', $argv)
];

function getBaseVersion() {
    global $config;
    $packageJson = json_decode(file_get_contents($config['basedir'].'/package.json'), true);

    return $packageJson['version'];
}

function getBuildNumber() {
    global $argv;
    $key = array_search('--build', $argv);
    if($key === false || !isset($argv[ $key + 1 ])) {
        return null;
    }

    return $argv[ $key + 1 ];
}

function getFullVersion() {
    global $config;
    $parts = explode('.', getBaseVersion());

    if($config['lsr']) {
        $parts[2] = ($config['nc25'] ? '1':'3').$parts[2];
    } else {
        $parts[2] = ($config['nc25'] ? '2':'4').$parts[2];
    }

    $version = implode('.', $parts);

    if($config['nightly']) {
        $build = getBuildNumber();

        if(empty($build)) {
            echo "Invalid build number";
            exit(1);
        }

        $version .= '-build'.$build;
    }

    return $version;
}

function updateInfoXml() {
    global $config;

    $appInfoPath = $config['basedir'].'/src/appinfo/info.xml';
    $xml         = simplexml_load_file($appInfoPath);

    $version    = $xml->xpath('version')[0];
    $version[0] = getFullVersion();

    if($config['nc25']) {
        $ncMaxVersion    = $xml->xpath('dependencies/nextcloud')[0];
        $ncMaxVersion['min-version'] = '26';
        $ncMaxVersion['max-version'] = '25';
    }

    $xml->asXML($appInfoPath);
}

function updateChangelog() {
    global $config;

    $base = getBaseVersion();
    $real = getFullVersion();

    $changelogPath       = $config['basedir'].'/CHANGELOG.md';
    $changelog = file_get_contents($changelogPath);
    $changelog = str_replace("## {$base}", "## {$real}", $changelog);

    file_put_contents($changelogPath, $changelog);
}

updateInfoXml();

if(!$config['nightly']) {
    updateChangelog();
}
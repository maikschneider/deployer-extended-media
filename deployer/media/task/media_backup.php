<?php

namespace Deployer;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-backup
 */

use SourceBroker\DeployerExtendedMedia\Utility\ConsoleUtility;

task('media:backup', function () {
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode');
    if (empty($dumpCode)) {
        if (empty(get('argument_stage'))) {
            $list = [];
            if (testLocally('[ -e {{deploy_path}}/releases ]')) {
                $list = explode("\n", runLocally('cd releases && ls -t -1 -d */'));
                $list = array_map(function ($release) {
                    return basename(rtrim(trim($release), '/'));
                }, $list);
            }
        } else {
            $list = get('releases_list');
        }
        $list = array_filter($list, function ($release) {
            return preg_match('/^[\d\.]+$/', $release);
        });
        $dumpCodeRealese = '';
        if (count($list) > 0) {
            $currentRelease = (int)max($list);
            $dumpCodeRealese = '_for_release_' . $currentRelease;
        }
        $dumpCode = 'backup' . $dumpCodeRealese . '_' . md5(microtime(true) . rand(0, 10000));
    }
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    if (empty(get('argument_stage'))) {
        runLocally('{{local/bin/deployer}} media:export ' . $options . ' ' . $verbosity);
        runLocally('{{local/bin/deployer}} media:compress ' . $options . ' ' . $verbosity);
        runLocally('{{local/bin/deployer}} media:dumpclean' . $verbosity);
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} media:export ' . $options . ' ' . $verbosity);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} media:compress ' . $options . ' ' . $verbosity);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} media:dumpclean' . $verbosity);
    }
})->desc('Do backup of media files (export and compress)');

<?php

namespace Deployer;

use SourceBroker\DeployerExtendedMedia\Utility\ConsoleUtility;

task('media:compress', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (empty(get('argument_stage'))) {
        $mediaDumpName = runLocally('ls -1t ' . get('media_storage_path_local') . '| grep ' . $dumpCode . ' | head -n 1');
        $mediaAbsolutePathName = get('media_storage_path_local') . '/' . $mediaDumpName;

        runLocally('{{local/bin/tar}} -czvf ' . $mediaAbsolutePathName . '.tar ' . $mediaAbsolutePathName);

        runLocally('rm -rf ' . $mediaAbsolutePathName);

    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} media:compress ' . $options . ' ' . $verbosity);
    }
})->desc('Compress media export with given dumpcode');

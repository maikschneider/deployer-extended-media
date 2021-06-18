<?php

namespace Deployer;

use SourceBroker\DeployerExtendedMedia\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedMedia\Utility\FileUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-dumpclean
 */
task('media:dumpclean', function () {
    if (empty(get('argument_stage'))) {
        $files = explode("\n", runLocally('ls -1t ' . get('media_storage_path_local')));
        $dumpsStorage = [];
        natsort($files);
        foreach (array_reverse($files) as $file) {
            $dumpcode = $instance = null;
            foreach (explode('#', $file) as $metaPart) {
                if (strpos($metaPart, 'server') === 0) {
                    $instanceParts = explode('=', $metaPart);
                    $instance = isset($instanceParts[1]) ? $instanceParts[1] : null;
                }
                if (strpos($metaPart, 'dumpcode') === 0) {
                    $dumpcodeParts = explode('=', $metaPart);
                    $dumpcode = isset($dumpcodeParts[1]) ? $dumpcodeParts[1] : null;
                }
            }
            if (empty($instance) || empty($dumpcode)) {
                writeln('Note: "server" or "dumpcode" can not be detected for file dump: "'
                    . (new FileUtility())->normalizeFolder(get('media_storage_path_local'))
                    . $file . '');
                writeln('Seems like this file was not created by deployer-extended-media or was created by previous version of deployer-extended-media. Please remove this file manually to get rid of this notice.');
                writeln('');
                continue;
            }
            $dumpsStorage[$instance][$dumpcode] = $dumpcode;
        }
        $mediaDumpCleanKeep = get('media_dumpclean_keep', 1);
        foreach ($dumpsStorage as $instance => $instanceDumps) {
            $instanceDumps = array_values($instanceDumps);
            if (is_array($mediaDumpCleanKeep)) {
                $mediaDumpCleanKeep = !empty($mediaDumpCleanKeep[$instance]) ? $mediaDumpCleanKeep[$instance] : (!empty($mediaDumpCleanKeep['*']) ? $mediaDumpCleanKeep['*'] : 1);
            }
            if (count($instanceDumps) > $mediaDumpCleanKeep) {
                $instanceDumpsCount = count($instanceDumps);
                for ($i = $mediaDumpCleanKeep; $i < $instanceDumpsCount; $i++) {
                    writeln('Removing old dump with code: ' . $instanceDumps[$i]);
                    runLocally('cd ' . escapeshellarg(get('media_storage_path_local'))
                        . ' && rm ' . '*dumpcode=' . $instanceDumps[$i] . '*');
                }
            }
        }
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath .
            ' && {{bin/php}} {{bin/deployer}} db:dumpclean ' . (new ConsoleUtility())->getVerbosityAsParameter());
    }
})->desc('Cleans the media dump storage');

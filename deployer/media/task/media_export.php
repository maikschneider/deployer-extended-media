<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use SourceBroker\DeployerExtendedMedia\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedMedia\Utility\FileUtility;

task('media:export', function () {
    if (!empty((new ConsoleUtility())->getOption('dumpcode'))) {
        $returnDumpCode = false;
        $dumpCode = (new ConsoleUtility())->getOption('dumpcode');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dumpCode)) {
            throw new GracefulShutdownException('dumpcode can be only a-z, A-Z, 0-9', 1582316535496);
        }
    } else {
        $returnDumpCode = true;
        $dumpCode = md5(microtime(true) . rand(0, 10000));
    }

    if (empty(get('argument_stage'))) {

        $fileUtility = new FileUtility();

        $folderParts = [
            'dateTime' => date('Y-m-d_H-i-s'),
            'dumpcode' => 'dumpcode=' . $fileUtility->normalizeFilename($dumpCode),
        ];

        $sourceDir = !empty($_ENV['IS_DDEV_PROJECT']) ? '.' : get('deploy_path') . '/' . (testLocally('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');

        $targetDir = get('media_storage_path_local') . '/' . implode('#', $folderParts) . '/';

        runLocally('[ -d ' . $targetDir . ' ] || mkdir -p ' . $targetDir);

        $script = <<<BASH
rsync {{media_rsync_flags}} --info=all0,name1 --dry-run --update {{media_rsync_options}}{{media_rsync_includes}}{{media_rsync_excludes}}{{media_rsync_filter}} $sourceDir/ $targetDir |
while read path; do
    if [ -d "{$sourceDir}/\$path" ]
    then
        echo "Creating directory \$path"
        mkdir -p "{$targetDir}/\$path"
    else
        echo "Copying file \$path"
        cp -L "{$sourceDir}/\$path" "{$targetDir}/\$path"
    fi
done
BASH;
        runLocally($script);
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} media:export ' . (input()->getOption('options') ? '--options=' . input()->getOption('options') : ''));
    }

    if ($returnDumpCode) {
        writeln(json_encode(['dumpCode' => $dumpCode]));
    }
})->desc('Export media to local directory');

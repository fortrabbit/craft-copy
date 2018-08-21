<?php
/**
 * Rename this file to craft-copy.php and place it in your /config folder
 */
use fortrabbit\Copy\models\StageConfig;

return [
    /**
     * Multi stage config
     */
    'stages' => [

        /**
         * Example config for your-test-app
         *
         * @see StageConfig
         */
        'your-test-app'    => new StageConfig([
            'localBranch' => 'testing'
        ]),

        /**
         * Example config for your-prod-app
         *
         * @see StageConfig
         */
        'your-prod-app'    => new StageConfig([
            'localBranch'   => 'prod',
            'remoteBranch'  => 'master',
            // executed on remote
            'postCodeUpCmd' => 'php craft cache/clear-all',
        ]),

        /**
         * Example config for some-test-server
         *
         * @see StageConfig
         */
        'some-test-server' => new StageConfig([
            'localBranch'  => 'testing',
            'remoteBranch' => 'master',
            'gitRemote'    => 'git@github.com:prefix/repo.git',
            'sshRemote'    => 'user@host.com',
        ]),
    ],

    /**
     * Alternative ssh upload command (usually no changes needed)
     * @see \fortrabbit\Copy\services\Ssh::UPLOAD_COMMAND
     */
    'sshUploadCommand'   => null,

    /**
     * Alternative ssh download command (usually no changes needed)
     * @see \fortrabbit\Copy\services\Ssh::DOWNLOAD_COMMAND
     */
    'sshDownloadCommand' => null,
];

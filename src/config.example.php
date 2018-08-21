<?php
/**
 * Rename this file to copy.php and place it in your /config folder
 */
return [
    /**
     * Multi stage config
     */
    'stages'             => [

        /**
         * Example config for your-test-app
         *
         * @see StageConfig
         */
        'your-test-app'    => [
            'sshRemoteUrl' => 'your-test-app@deploy.us1.frbit.com',
            'gitRemoteName'=> 'testing',
        ],

        /**
         * Example config for your-prod-app
         *
         * @see StageConfig
         */
        'your-prod-app'    => [
            'sshRemoteUrl' => 'your-prod-app@deploy.eu2.frbit.com',
            'gitRemoteName'=> 'production',
        ],

        /**
         * Example config for some-test-server
         *
         * @see StageConfig
         */
        'some-test-server' => [
            'sshRemoteUrl' => 'user@host.com',
        ],
    ],

    /**
     * Alternative ssh upload command (usually no changes needed)
     *
     * @see \fortrabbit\Copy\services\Ssh::UPLOAD_COMMAND
     */
    'sshUploadCommand'   => null,

    /**
     * Alternative ssh download command (usually no changes needed)
     *
     * @see \fortrabbit\Copy\services\Ssh::DOWNLOAD_COMMAND
     */
    'sshDownloadCommand' => null,
];

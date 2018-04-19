<?php
/**
 * Created by PhpStorm.
 * User: os
 * Date: 18.04.18
 * Time: 21:32
 */

namespace fortrabbit\Copy\commands;


use GitWrapper\GitException;
use GitWrapper\GitWrapper;
use ostark\Yii2ArtisanBridge\base\Action;

class CodeUpAction extends Action
{

    public function run()
    {
        $gitWrapper = new GitWrapper();
        $git = $gitWrapper->workingCopy(\Craft::getAlias('@root'));


        $localBranches = [];
        foreach (explode(PHP_EOL, trim($git->run('branch'))) as $branch) {
            $localBranches[trim(ltrim($branch ,'*'))] = $branch;
        };
        if (count($localBranches) > 1) {
            $branch = $this->choice('Select a local branch:', $localBranches, 'master');
            $git->checkout($branch);
        }

        if (!$remotes = $git->getRemotes()) {
            $this->errorBlock('No remotes configured.');
        }
        if (count($remotes) > 1) {
            foreach ($remotes as $name => $upstreams) {
                $remotes[$name] = $upstreams['push'];
            }
            $upstream = $this->choice('Select a remote', $remotes);
        } else {
            $upstream = array_keys($remotes)[0];
        }





        try {
            $this->section('git push');
            $git->push('proprod','master');
        }
        catch(GitException $e) {
            $this->errorBlock($e->getMessage());
        }


/*
        try {
            //$git->push();
        } catch (\Exception $exception) {
            $this->errorBlock($exception->getMessage());
        }
*/
        //var_dump($git->getRemotes());
        //var_dump($git->getBranches()->all(['verbose' => true]));
        /*foreach($git->getBranches()->remote() as $remote) {
            var_dump([
                $remote,
                $git->getRemoteUrl(explode("/", $remote)[0])
            ]);
        }*/
        //var_dump($git->fetchAll());


        //var_dump($git->isBehind());

        //var_dump($git->getDirectory());
        //var_dump($git->getRemoteUrl('proprod'));



        // Stream output of subsequent Git commands in real time to STDOUT and STDERR.
        //$gitWrapper->streamOutput();

        if ($git->hasChanges()) {
            var_dump(explode(PHP_EOL,trim($git->getStatus())));
            var_dump($git->add('.', ['verbose' => true]));
            var_dump($git->commit('testing GitWrapper'));

            //$this->line($git->getStatus());

        }



    }
}

<?php namespace Wireshell;

use Symfony\Component\Console\Input\InputInterface;

/**
 * PwUserTrait
 *
 * Reusable methods for both user and role creation
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

trait PwUserTrait
{
    /**
     * @param $name
     * @return \Page
     */
    public function createRole($name, $roleContainer)
    {
        $user = new \Page();
        $user->template = 'user';
        $user->setOutputFormatting(false);

        $user->parent = $roleContainer;
        $user->name = $name;
        $user->title = $name;
        return $user;
    }

    /**
     * @param InputInterface $input
     * @param $name
     * @return \Page
     */
    public function createUser(InputInterface $input, $name, $userContainer)
    {
        $user = new \Page();
        $user->template = 'user';
        $user->setOutputFormatting(false);

        $user->parent = $userContainer;
        $user->name = $name;
        $user->title = $name;

        $email = $input->getOption('email');
        if ($email) $user->email = $email;

        return $user;
    }
}
<?php namespace Wireshell;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PwUserTools
 *
 * Reusable methods for both user and role creation
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class PwUserTools extends PwConnector
{
    /**
     * @param $name
     * @return \Page
     */
    public function createRole($name, $roleContainer)
    {
        $user = new \Page();
        $user->template = 'role';
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

    /**
     * @param $user
     * @param $roles
     * @return mixed
     */
    public function attachRolesToUser($user, $roles, $output)
    {
        $editedUser = wire('users')->get($user);

        foreach ($roles as $role) {
            $this->checkIfRoleExists($role, $output);

            $editedUser->addRole($role);
            $editedUser->save();
        }

        return $editedUser;
    }

    /**
     * @param $role
     * @param $output
     * @return bool
     */
    private function checkIfRoleExists($role, $output)
    {
        if (wire("pages")->get("name={$role}") instanceof \NullPage) {
            $output->writeln("<comment>Role '{$role}' does not exist!</comment>");

            return false;
        }
    }
}

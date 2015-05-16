<?php namespace Wireshell\Helpers;

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
     * @param $userContainer
     * @param $pass
     * @return \Page
     */
    public function createUser(InputInterface $input, $name, $userContainer, $pass)
    {
        $user = new \Page();
        $user->template = 'user';
        $user->setOutputFormatting(false);

        $user->parent = $userContainer;
        $user->name = $name;
        $user->title = $name;

        if (!empty($pass)) $user->pass = $pass;

        $email = $input->getOption('email');
        if ($email) $user->email = $email;

        return $user;
    }

    /**
     * @param InputInterface $input
     * @param $name
     * @param $pass
     * @return \Page
     */
    public function updateUser(InputInterface $input, $name, $pass)
    {
        $user = wire('users')->get($name);
        $user->setOutputFormatting(false);

        if (!empty($input->getOption('newname'))) {
          $name = wire('sanitizer')->username($input->getOption('newname'));
          $user->name = $name;
          $user->title = $name;
        }

        if (!empty($pass)) $user->pass = $pass;

        if (!empty($input->getOption('email'))) {
          $email = wire('sanitizer')->email($input->getOption('email'));
          if ($email) $user->email = $email;
        }

        return $user;
    }

    /**
     * @param $user
     * @param $roles
     * @param $output
     * @param boolean $reset
     * @return mixed
     */
    public function attachRolesToUser($user, $roles, $output, $reset = false)
    {
        $editedUser = wire('users')->get($user);

        // remove existing roles
        if ($reset === true) {
            foreach ($editedUser->roles as $role) {
                $editedUser->removeRole($role->name);
            }
        }

        // add roles
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

<?php namespace Wireshell\Helpers;

use ProcessWire\Page;
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
class PwUserTools extends PwConnector {
    /**
     * @param $email
     * @param $name
     * @param $userContainer
     * @param $pass
     * @return Page
     */
    public function createUser($email, $name, $userContainer, $pass)
    {
        $user = new Page();
        $user->template = 'user';
        $user->setOutputFormatting(false);

        $user->parent = $userContainer;
        $user->name = $name;
        $user->title = $name;
        $user->pass = $pass;
        $user->email = $email;

        return $user;
    }

    /**
     * @param string $name
     * @param string $pass
     * @param string $email
     * @param string $newname
     * @return \Page
     */
    public function updateUser($name, $pass, $email, $newname) {
        $user = \ProcessWire\wire('users')->get($name);
        $user->setOutputFormatting(false);

        if (!empty($newname)) {
          $name = \ProcessWire\wire('sanitizer')->username($newname);
          $user->name = $name;
          $user->title = $name;
        }

        if (!empty($pass)) $user->pass = $pass;

        if (!empty($email)) {
          $email = \ProcessWire\wire('sanitizer')->email($email);
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
    public function attachRolesToUser($user, $roles, $output, $reset = false) {
        $editedUser = \ProcessWire\wire('users')->get($user);

        // remove roles which are not submitted
        foreach ($editedUser->roles as $role) {
            if (!in_array($role->name, $roles)) {
                var_dump('foo', $role->name);
                $editedUser->removeRole($role->name);
            }
        }
        $editedUser->save();

        // remove existing roles
        if ($reset === true) {
            foreach ($editedUser->roles as $role) {
                $editedUser->removeRole($role->name);
            }
        }

        // add roles
        foreach ($roles as $role) {
            if ($role === 'guest') continue;
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
    private function checkIfRoleExists($role, $output) {
        if (\ProcessWire\wire("pages")->get("name={$role}") instanceof \ProcessWire\NullPage) {
            $output->writeln("<comment>Role '{$role}' does not exist!</comment>");

            return false;
        }
    }

    /**
     * Get available roles
     *
     * @param string $rls
     * @return array
     */
    public function getAvailableRoles($rls) {
        $availableRoles = array();
        foreach (\ProcessWire\wire('roles') as $role) $availableRoles[] = $role->name;

        return $rls ? explode(",", $rls) : $availableRoles;
    }
}

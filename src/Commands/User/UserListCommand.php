<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;
use Wireshell\Helpers\WsTables as Tables;

/**
 * Class UserListCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <info@justonestep.de>
 */

class UserListCommand extends PwUserTools {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('user:list')
      ->setDescription('Lists ProcessWire users')
      ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Find user by role');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $users = $this->getUsers($input);
    $tools = new Tools($output);
    $tables = new Tables($output);
    $tools->writeBlockCommand($this->getName());

    if ($users->getTotal() > 0) {
      $content = $this->getUserData($users);
      $headers = array('Username', 'E-Mail', 'Superuser', 'Roles');

      $userTables = array($tables->buildTable($content, $headers));
      $tables->renderTables($userTables, false);
    }

    $tools->writeCount($users->getTotal());
  }

  /**
   * get users
   *
   * @param InputInterface $input
   */
  private function getUsers($input) {
    $role = $input->getOption('role');

    if ($role) {
      $users = \ProcessWire\wire('users')->find('roles=' . $input->getOption('role'))->sort('name');
    } else {
      $users = \ProcessWire\wire('users')->find('start=0')->sort('name');
    }

    return $users;
  }

  /**
   * get user data
   *
   * @param $users
   * @return array
   */
  private function getUserData($users) {
    $content = array();
    foreach ($users as $user) {
      $roles = array();
      foreach ($user->roles as $role) {
        $roles[] = $role->name;
      }

      $content[] = array(
        $user->name,
        $user->email,
        $user->isSuperuser() ? '✔' : '',
        implode(', ', $roles)
      );
    }

    return $content;
  }

}

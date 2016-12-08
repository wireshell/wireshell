<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class UserDeleteCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <info@justonestep.de>
 */

class UserDeleteCommand extends PwUserTools {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('user:delete')
      ->setDescription('Deletes ProcessWire users')
      ->addArgument('name', InputArgument::OPTIONAL, 'Name of user')
      ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Delete user(s) by role');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    parent::init($output, $input);
    parent::bootstrapProcessWire($output);
    $tools = new Tools($output);
    $tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'));

    // if argument role is provided, delete all users with specific role
    if ($role = $input->getOption('role')) {
      $users = \ProcessWire\wire('users')->find("roles=$role");

      foreach ($users as $user) {
        \ProcessWire\wire('users')->delete($user);
      }

      $output->writeln("<info>Deleted {$users->count()} users successfully!</info>");
    } else {
      // check name
      $availableUsers = array();
      $usersObj = \ProcessWire\wire('users')->find('start=0')->sort('name');
      foreach ($usersObj as $user) $availableUsers[] = $user->name;

      // filter out guest
      $guestIndex = array_search('guest', $availableUsers);
      if ($guestIndex) unset($availableUsers[$guestIndex]);

      $urs = $input->getArgument('name');
      $users = $urs ? explode(',', $urs) : null;

      $users = $tools->askChoice($users, 'Select all users which should be deleted', $availableUsers, 0, true);

      $tools->nl();
      foreach ($users as $name) {
        if (\ProcessWire\wire('users')->get($name) instanceof \ProcessWire\NullPage) {
          $tools->writeError("User '{$name}' doesn't exists.");
        } else {
          $user = \ProcessWire\wire('users')->get($name);
          \ProcessWire\wire('users')->delete($user);
          $tools->writeSuccess("User '{$name}' deleted successfully.");
        }
      }
    }

  }
}

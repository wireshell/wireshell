<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;

/**
 * Class UserDeleteCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <info@justonestep.de>
 */

class UserDeleteCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('user:delete')
            ->setDescription('Deletes ProcessWire users')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $users = explode(',', $input->getArgument('name'));
        foreach ($users as $name) {
          if (wire('users')->get($name) instanceof \NullPage) {
              $output->writeln("<error>User '{$name}' doesn't exists!</error>");
          } else {
            $user = wire('users')->get($name);
            wire('users')->delete($user);
            $output->writeln("<info>User '{$name}' deleted successfully!</info>");
          }
        }
    }


}



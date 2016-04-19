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
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'Delete user(s) by role');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        if ($role = $input->getOption('role')) {
            $users = \ProcessWire\wire('users')->find("roles=$role");

            foreach ($users as $user) {
                \ProcessWire\wire('users')->delete($user);
            }
            $output->writeln("<info>Deleted {$users->count()} users successfully!</info>");
        } else {
            $users = explode(',', $input->getArgument('name'));

            foreach ($users as $name) {
                if (\ProcessWire\wire('users')->get($name) instanceof \ProcessWire\NullPage) {
                    $output->writeln("<error>User '{$name}' doesn't exists!</error>");
                } else {
                    $user = \ProcessWire\wire('users')->get($name);
                    \ProcessWire\wire('users')->delete($user);
                    $output->writeln("<info>User '{$name}' deleted successfully!</info>");
                }
            }
        }

    }


}



<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;

/**
 * Class UserCreateCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class UserUpdateCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('user:update')
            ->setAliases(['u:u'])
            ->setDescription('Updates a ProcessWire user')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('newname', null, InputOption::VALUE_REQUIRED, 'Supply an user name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Supply an password')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Attach existing roles to user, comma separated');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $name = $input->getArgument('name');
        $roles = explode(",", $input->getOption('roles'));
        $pass = $input->getOption('password');

        if (wire("pages")->get("name={$name}") instanceof \NullPage) {

            $output->writeln("<error>User '{$name}' doesn't exists!</error>");
            exit(1);
        }

        $user = $this->updateUser($input, $name, $pass);
        $user->save();

        if ($input->getOption('roles')) {
            $this->attachRolesToUser($name, $roles, $output, true);
        }

        $output->writeln("<info>User '{$name}' updated successfully!</info>");
    }


}


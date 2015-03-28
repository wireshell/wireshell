<?php namespace Wireshell;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class CreateUserCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('create-user')
            ->setAliases(['c-u', 'user'])
            ->setDescription('Creates a ProcessWire user')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address')
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

        if (!wire("pages")->get("name={$name}") instanceof \NullPage) {

            $output->writeln("<error>User '{$name}' already exists!</error>");
            exit(1);
        }

        $user = $this->createUser($input, $name, $this->userContainer);

        $user->save();

        if ($input->getOption('roles')) {
            $this->attachRolesToUser($name, $roles, $output);
        }

        $output->writeln("<info>User '{$name}' created successfully! Please do not forget to set a password.</info>");

    }


}

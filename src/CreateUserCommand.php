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

class CreateUserCommand extends PwConnector
{
    use PwUserTrait;

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
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address');
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

        if (!wire("pages")->get("name={$name}") instanceof \NullPage) {

            $output->writeln("<error>User '{$name}' already exists!</error>");
            exit(1);
        }

        $user = $this->createUser($input, $name, $this->userContainer);

        $user->save();

        $output->writeln("<info>User '{$name}' created successfully!</info>");

    }


}

<?php namespace Wireshell\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwUserTools;

/**
 * Class RoleCreateCommand
 *
 * Creating ProcessWire user roles
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class RoleCreateCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('role:create')
            ->setAliases(['r:c'])
            ->setDescription('Creates a ProcessWire role')
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

        $name = $input->getArgument('name');

        if (!wire("pages")->get("name={$name}") instanceof \NullPage) {

            $output->writeln("<error>Role '{$name}' already exists!</error>");
            exit(1);
        }

        $user = $this->createRole($name, $this->roleContainer);

        $user->save();

        $output->writeln("<info>Role '{$name}' created successfully!</info>");

    }

}

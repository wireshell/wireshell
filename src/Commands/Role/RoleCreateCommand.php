<?php namespace Wireshell\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;

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
            ->setDescription('Creates a ProcessWire role')
            ->addArgument('name', InputArgument::REQUIRED, 'comma-separated list');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);
        $names = explode(',', preg_replace('/\s+/', '', $input->getArgument('name')));

        foreach ($names as $name) {
            if (!wire('roles')->get($name) instanceof \NullPage) {
                $output->writeln("<error>Role '{$name}' already exists!</error>");
                exit(1);
            }

            wire('roles')->add($name);
            $output->writeln("<info>Role '{$name}' created successfully!</info>");
        }
    }

}

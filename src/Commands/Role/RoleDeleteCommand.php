<?php namespace Wireshell\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;

/**
 * Class RoleDeleteCommand
 *
 * Deleting ProcessWire user roles
 *
 * @package Wireshell
 * @author Tabea David
 */
class RoleDeleteCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('role:delete')
            ->setDescription('Deletes ProcessWire role(s)')
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
        $roles = \ProcessWire\wire('roles');

        foreach ($names as $name) {
            if ($roles->get($name) instanceof \ProcessWire\NullPage) {
                $output->writeln("<error>Role '{$name}' does not exist.</error>");
                exit(1);
            }

            $roles->delete($roles->get($name));
            $output->writeln("<info>Role '{$name}' deleted successfully!</info>");
        }
    }

}


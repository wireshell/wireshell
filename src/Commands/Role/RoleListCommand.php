<?php namespace Wireshell\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class RoleListCommand
 *
 * List ProcessWire user roles
 *
 * @package Wireshell
 * @author Tabea David
 */
class RoleListCommand extends PwUserTools {

    /**
     * Configures the current command.
     */
    public function configure() {
        $this
            ->setName('role:list')
            ->setDescription('Lists ProcessWire role(s)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        $tools = new Tools($output);
        $tools->writeBlockCommand($this->getName());

        foreach (\ProcessWire\wire('roles') as $role) {
            $tools->writeInfo("  - {$role->name}");
        }
    }
}

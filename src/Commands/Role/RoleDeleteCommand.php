<?php namespace Wireshell\Commands\Role;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class RoleDeleteCommand
 *
 * Deleting ProcessWire user roles
 *
 * @package Wireshell
 * @author Tabea David
 */
class RoleDeleteCommand extends PwUserTools {

    /**
     * Configures the current command.
     */
    public function configure() {
        $this
            ->setName('role:delete')
            ->setDescription('Deletes ProcessWire role(s)')
            ->addArgument('roles', InputArgument::OPTIONAL, 'comma-separated list');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        $roles = \ProcessWire\wire('roles');

        $tools = new Tools($output);
        $tools
            ->setInput($input)
            ->setHelper($this->getHelper('question'));
        $tools->writeBlockCommand($this->getName());

        $options = $this->getAvailableRoles($input->getArgument('roles'));

        // superuser and guest may not be deleted
        unset($options[array_search('guest', $options)]);
        unset($options[array_search('superuser', $options)]);

        if (count($options) === 0) {
            $tools->writeError('There are no roles which can be deleted.');
            exit(1);
        }

        $names = $tools->askChoice(
            $input->getArgument('roles'),
            'Which roles should be deleted',
            $options,
            '0',
            true
        );

        if (!is_array($names)) explode(',', preg_replace('/\s+/', '', $names));

        foreach ($names as $name) {
            $tools->nl();
            if ($roles->get($name) instanceof \ProcessWire\NullPage) {
                $tools->writeError("Role '{$name}' does not exist.");
                exit(1);
            }

            $roles->delete($roles->get($name));
            $tools->writeSuccess("Role '{$name}' has been deleted successfully.");
        }
    }

}

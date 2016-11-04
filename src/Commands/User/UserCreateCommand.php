<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class UserCreateCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class UserCreateCommand extends PwUserTools {

    /**
     * Configures the current command.
     */
    public function configure() {
        $this
            ->setName('user:create')
            ->setDescription('Creates a ProcessWire user')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of user.')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Supply a password')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Attach existing roles to user, comma separated');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        $tools = new Tools($output);
        $tools
            ->setHelper($this->getHelper('question'))
            ->setInput($input);

        $tools->writeBlockCommand($this->getName());

        $name = $tools->ask($input->getArgument('name'), 'Username');
        $email = $tools->ask($input->getOption('email'), 'E-Mail-Address', null, false, null, 'email');
        $pass = $tools->ask($input->getOption('password'), 'Password', $tools->generatePassword(), true);

        while (!\ProcessWire\wire("pages")->get("name={$name}") instanceof \ProcessWire\NullPage) {
            $tools->writeError("User '{$name}' already exists, please choose another one");
            $name = $tools->ask($input->getArgument('name'), 'Username');
        }

        $user = $this->createUser($email, $name, $this->userContainer, $pass);
        $user->save();

        $availableRoles = array();
        foreach (\ProcessWire\wire('roles') as $role) $availableRoles[] = $role->name;

        $rls = $input->getOption('roles');
        $roles = $rls ? explode(",", $rls) : null;

        $roles = $tools->askChoice($roles, $availableRoles, 0, true);
        if ($roles) $this->attachRolesToUser($name, $roles, $output);

        if ($pass) {
            $tools->writeInfo("User '{$name}' created successfully!");
        } else {
            $tools->writeInfo("User '{$name}' created successfully! Please do not forget to set a password.");
        }
    }

}

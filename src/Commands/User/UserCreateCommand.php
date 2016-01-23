<?php namespace Wireshell\Commands\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwUserTools;

/**
 * Class UserCreateCommand
 *
 * Creating ProcessWire users
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class UserCreateCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Creates a ProcessWire user')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Supply an email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Supply a password')
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
        $email = $input->getOption('email');
        $pass = $input->getOption('password');
        $roles = explode(",", $input->getOption('roles'));

        $helper = $this->getHelper('question');
        if (!$email) {
            $question = new Question('Please enter a email address : ', 'email');
            $email = $helper->ask($input, $output, $question);
        }
        if (!$pass) {
            $question = new Question('Please enter a password : ', 'password');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $pass = $helper->ask($input, $output, $question);
        }

        if (!wire("pages")->get("name={$name}") instanceof \NullPage) {
            $output->writeln("<error>User '{$name}' already exists!</error>");
            exit(1);
        }

        $user = $this->createUser($email, $name, $this->userContainer, $pass);
        $user->save();

        if ($input->getOption('roles')) $this->attachRolesToUser($name, $roles, $output);

        if ($pass) {
          $output->writeln("<info>User '{$name}' created successfully!</info>");
        } else {
          $output->writeln("<info>User '{$name}' created successfully! Please do not forget to set a password.</info>");
        }
    }

}

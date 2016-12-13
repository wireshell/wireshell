<?php namespace Wireshell\Commands\Page;

use ProcessWire\Page;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class PageCreateCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David
 */
class PageCreateCommand extends PwConnector {

  static $supportedTypes = array('json');

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('page:create')
      ->setDescription('Creates a ProcessWire page')
      ->addArgument('name', InputArgument::OPTIONAL)
      ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Template')
      ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent Page')
      ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Title')
      ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Field data file (JSON)');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    parent::setOutput($output)::setInput($input)::bootstrapProcessWire();

    $this->tools = new Tools($output);
    $this->tools
      ->setHelper($this->getHelper('question'))
      ->setInput($input);

    $pages = \ProcessWire\wire('pages');
    $names = $this->tools->ask($input->getArgument('name'), 'Name(s) for new page(s)', null, false, null, 'required');
    $template = $this->getTemplate($input->getOption('template'));
    $parent = $this->getParent($input->getOption('parent'));

    foreach (explode(',', $names) as $name) {
      $sanitizedName = \ProcessWire\wire('sanitizer')->pageName($name);
      if (!\ProcessWire\wire('pages')->get($parent->url . $sanitizedName . '/') instanceof \ProcessWire\NullPage) {
        $this->writeError("The page name  '{$name}' is already taken.");
        continue;
      }

      // create page and populate it with the required details to save it a first time
      $p = new Page();
      $p->template = $template;
      $p->parent = $parent;
      $p->name = $sanitizedName; // give it a name used in the url for the page
      $p->title = $input->getOption('title') ? $input->getOption('title') : $name;

      // IMPORTANT: Save the page once, so that file-type fields can be added to it below!
      $p->save();

      if ($input->getOption('file')) $this->addFileData($input->getOption('file'), $p);

      $output->writeln("<info>Page `{$name}` has been successfully created.</info>");
    }
  }


  /**
   * Get template
   *
   * @param string $name
   */
  private function getTemplate($name) {
    $templateName = $this->tools->ask($name, 'Which template should be assigned', null, false, null, 'required');

    $template = \ProcessWire\wire('templates')->get($templateName);
    if (!$template) {
      $this->tools->writeError("Template '{$templateName}' doesn't exist.");
      exit(1);
    }

    if ($template->noParents) {
      $this->tools->writeError("Template '{$templateName}' is not allowed to be used for new pages.");
      exit(1);
    }

    $this->template = $template;

    return $templateName;
  }

  /**
   * Get parent
   *
   * @param $name
   */
  private function getParent($name) {
    $parent = '/';

    // parent page submitted and existing?
    if ($name) {
      if (!\ProcessWire\wire('pages')->get("/$name/") instanceof \ProcessWire\NullPage) {
        $parent = "/$name/";
      } elseif (!\ProcessWire\wire('pages')->get($name) instanceof \ProcessWire\NullPage) {
        $parent = (int)$name;
      }
    }

    $parentPage = \ProcessWire\wire('pages')->get($parent);
    $parentTemplate = $parentPage->template;

    // may pages using this template have children?
    if (!empty($parentTemplate->noChildren)) {
      $this->tools->writeError("The parent page '{$parent}' is not allowed to have children.");
      exit(1);
    }

    // allowed template(s) for parents
    if (
      is_array($this->template->parentTemplates)
      && !empty($this->template->parentTemplates)
      && !in_array($parentTemplate->id, $this->template->parentTemplates)
    ) {
      $this->tools->writeError("The parent page '{$parent}' is not allowed to be parent for this template.");
      exit(1);
    }

    // allowed template(s) for children
    if (
      is_array($parentTemplate->childTemplates)
      && !empty($parentTemplate->childTemplates)
      && !in_array($this->template->id, $parentTemplate->childTemplates)
    ) {
      $this->tools->writeError("This template '{$this->template}' is not allowed to be children of template '{$parentTemplate}'.");
      exit(1);
    }

    return $parentPage;
  }

  /**
   * Import field data, if a field data file is available
   *
   * @param string $dataFilePath
   * @param Page $p
   * @return array
   */
  protected function addFileData($dataFilePath, $p) {
    // check whether the file does exist
    if (!file_exists($dataFilePath)) {
      $this->tools->writeError("The file `$dataFilePath` does not exist.");
      exit(1);
    } else {
      // yeah, file exists - check file extension
      $ext = pathinfo($dataFilePath, PATHINFO_EXTENSION);

      if (!in_array($ext, self::$supportedTypes)) {
        $this->tools->writeError("The file extension `$ext` is currently not supported.");
        exit(1);
      }
    }

    // no valid content? empty file?
    $data = $ext === 'json' ? json_decode(file_get_contents($dataFilePath)) : null;
    if (!$data) {
      $this->tools->writeError("The file `$dataFilePath` does not contain valid `$ext`.");
      exit(1);
    }

    $this->addFieldContent($data, $ext, $p);
  }

  /**
   * Populate any non-required fields before the second save
   *
   * @param string $data file content
   * @param string $ext file extension
   * @param Page $p
   */
  protected function addFieldContent($data, $ext, $p) {
    if ($ext === 'json') {
      foreach ($data as $fieldname => $fieldval) {
        $fieldname = strtolower($fieldname);

        if ($p->$fieldname) {
          if ($fieldname === 'name') $fieldval = \ProcessWire\wire('sanitizer')->pageName($fieldval);
          $p->$fieldname = $fieldval;
        } else {
          $this->tools->writeComment("For the chosen template field `$fieldname` does not exist.");
        }
      }
    }

    // finally save the field data as well
    $p->save();
  }

}

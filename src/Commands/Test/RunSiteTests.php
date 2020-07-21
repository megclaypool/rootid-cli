<?php

namespace RootidCLI\Commands\Test;

use RootidCLI\Traits\SiteInfo;
use RootidCLI\Traits\SiteOperations;

class RunSiteTests extends \Robo\Tasks
{
  use SiteInfo;
  use SiteOperations;

  public function __construct()
  {
    $this->backstop_config = BASE_DIR . "/test-runners/backstop-config.js";
  }


  /**
   * Runs tests against the local version of the site.
   *
   * @command cypress:run
   *
   *
   * @usage --machine-token=<machine_token> Logs in a user granted the machine token <machine_token>.
   * @usage Logs in a user with a previously saved machine token.
   * @usage --email=<email> Logs in a user with a previously saved machine token belonging to <email>.
   *
   * @alias cypruss
   */
  public function runIntegrationTests()
  {
    $site_data = $this->getSite();

    $test_url = $this->getLocalSiteRoot($site_data);

    $_ENV['CYPRESS_BASE_URL'] = $test_url;

    //chdir('./.tests');

    //$tests = file_get_contents('elements.json');
    //$_ENV['TEST_ELEMENTS'] = $tests;

    //if($options['generate']) {
    //    $this->generateReferenceImages();
    //}

    $this->runCypressTests($test_url);
  }

  /**
   * Runs tests against the local version of the site.
   *
   * @command cypress:open
   *
   */
  public function openIntegrationTestResults()
  {
    $site_data = $this->getSite();
    $test_url = $this->getLocalSiteRoot($site_data);
    $_ENV['CYPRESS_BASE_URL'] = $test_url;
    $this->openCypressTestResults($test_url);
  }

  /**
   * Runs tests against the local version of the site.
   *
   * @command backstop
   *
   * @option env Specific site environment to sync from.
   * @option generate If set this will create references images as well as run a test.
   * @option test If set this allows you to use either a pantheon environment or a specific test url [default: local site]
   * @option ref If set this allows you to use either a pantheon environment or a specific reference url [default: live site]
   * @option debug If set this toggles the browser to run tests in non-headless, visible mode so you can watch
   *
   * @usage --machine-token=<machine_token> Logs in a user granted the machine token <machine_token>.
   * @usage Logs in a user with a previously saved machine token.
   * @usage --email=<email> Logs in a user with a previously saved machine token belonging to <email>.
   */
  public function runTests($options = ['env' => 'live', 'generate' => NULL, 'test' => NULL, 'ref' => NULL, 'debug' => NULL])
  {
    $site_data = $this->getSite();

    if ($options['ref'] !== NULL) {
      if (in_array($options['ref'], $this->getSiteEnvs($site_data))) {
        // TODO: Currently hard-coding http auth, but this should be configurable.
        $ref_url = "https://rootid:rootid@" . $options['ref'] . '-' . $site_data->name . ".pantheonsite.io";
      } else {
        if (strpos($options['ref'], "http") === 0) {
          $ref_url = $options['ref'];
        } else {
          $ref_url = "http://" . $options['ref'];
        }
      }
    } else {
      if ($options['env'] !== NULL && (in_array($options['env'], ['live', 'dev', 'test']) || in_array($options['env'], $this->getSiteEnvs($site_data)))) {
        // TODO: Currently hard-coding http auth, but this should be configurable.
        $ref_url = "https://rootid:rootid@" . $options['env'] . '-' . $site_data->name . ".pantheonsite.io";
      } else {
        $ref_url = "https://rootid:rootid@.live-" . $site_data->name . ".pantheonsite.io";
      }
    }

    if ($options['test'] !== NULL) {
      if (in_array($options['test'], $this->getSiteEnvs($site_data))) {
        // TODO: Currently hard-coding http auth, but this should be configurable.
        $test_url = "https://rootid:rootid@" . $options['test'] . '-' . $site_data->name . ".pantheonsite.io";
      } else {
        if (strpos($options['test'], "http") === 0) {
          $test_url = $options['test'];
        } else {
          $test_url = "http://" . $options['test'];
        }
      }
    } else {
      $test_url = $this->getLocalSiteRoot($site_data);
    }


    if ($options['debug']) {
      $_ENV['BACKSTOP_DEBUG'] = "debug";
    }

    // See /test-runners/backstop-config.js for when these get used.
    $_ENV['BACKSTOP_REF_URL'] = $ref_url;
    $_ENV['BACKSTOP_TEST_URL'] = $test_url;

    chdir('./.tests');

    $tests = file_get_contents('elements.json');
    $_ENV['TEST_ELEMENTS'] = $tests;

    // echo ("Reference: \n" . $ref_url . "\n");
    // echo ("Test: \n" . $test_url . "\n");

    if ($options['generate']) {
      $this->generateReferenceImages();
    }

    $this->runVisualRegressionTests();
  }

  private function generateReferenceImages()
  {
    $this->taskExec('backstop')->args('reference', '--config=' . $this->backstop_config)->run();
  }

  private function runVisualRegressionTests()
  {
    // Run the tests set up in the ROOT/.tests/elements.json file.
    $this->taskExec('backstop')->args('test', '--config=' . $this->backstop_config)->run();
    $this->taskExec('backstop')->args('openReport', '--config=' . $this->backstop_config)->run();
  }

  private function runCypressTests($base_path)
  {
    $this->taskExec('cypress')->args('run', '--env')->rawArg('BASE_PATH=' . $base_path)->run();
  }

  private function openCypressTestResults($test_url)
  {
    $this
      ->taskExec('cypress')
      ->args('open', '--env')
      ->rawArg('BASE_PATH=' . $test_url)
      ->rawArg('--project ./.tests')
      ->run();
  }
}

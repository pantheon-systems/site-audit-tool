<?php
namespace TestUtils;

trait DrushTestTrait
{
    use CliTestTrait;

    /**
     * @return string
     */
    public static function getDrush()
    {
        // TODO: Figure out how to manage this
        return __DIR__ . '/../../vendor/bin/drush';
    }

    /**
     * Invoke drush in via execute().
     *
     * @param command
      *   A defined drush command such as 'cron', 'status' or any of the available ones such as 'drush pm'.
      * @param args
      *   Command arguments.
      * @param $options
      *   An associative array containing options.
      * @param $site_specification
      *   A site alias or site specification. Include the '@' at start of a site alias.
      * @param $cd
      *   A directory to change into before executing.
      * @param $expected_return
      *   The expected exit code. Usually self::EXIT_ERROR or self::EXIT_SUCCESS.
      * @param $suffix
      *   Any code to append to the command. For example, redirection like 2>&1.
      * @param array $env
      *   Environment variables to pass along to the subprocess.
      * @return integer
      *   An exit code.
      */
    public function drush($command, array $args = [], array $options = [], $site_specification = null, $cd = null, $expected_return = 0 /*self::EXIT_SUCCESS */, $suffix = null, $env = [])
    {
        $global_option_list = ['simulate', 'root', 'uri', 'include', 'config', 'alias-path', 'ssh-options'];
        $cmd[] = self::getDrush();

        // Insert global options.
        foreach ($options as $key => $value) {
            if (in_array($key, $global_option_list)) {
                unset($options[$key]);
                if (!isset($value)) {
                    $cmd[] = "--$key";
                } else {
                    $cmd[] = "--$key=" . self::escapeshellarg($value);
                }
            }
        }

        $cmd[] = "--no-interaction";

        // Insert site specification and drush command.
        $cmd[] = empty($site_specification) ? null : self::escapeshellarg($site_specification);
        $cmd[] = $command;

        // Insert drush command arguments.
        foreach ($args as $arg) {
            $cmd[] = self::escapeshellarg($arg);
        }
        // insert drush command options
        foreach ($options as $key => $value) {
            if (!isset($value)) {
                $cmd[] = "--$key";
            } else {
                $cmd[] = "--$key=" . self::escapeshellarg($value);
            }
        }

        $cmd[] = $suffix;
        $exec = array_filter($cmd, 'strlen'); // Remove NULLs
        // Set sendmail_path to 'true' to disable any outgoing emails
        // that tests might cause Drupal to send.

        $cmd = implode(' ', $exec);
        $return = $this->execute($cmd, $expected_return, $cd, $env);

        // Save code coverage information.
        if (!empty($coverage_file)) {
            $data = unserialize(file_get_contents($coverage_file));
            unlink($coverage_file);
            // Save for appending after the test finishes.
            $this->coverage_data[] = $data;
        }

        return $return;
    }

    public function drushMajorVersion()
    {
        static $major;

        if (!isset($major)) {
            $this->drush('version', [], ['field' => 'drush-version']);
            $version = trim($this->getOutput());
            list($major) = explode('.', $version);
        }
        return (int)$major;
    }
}

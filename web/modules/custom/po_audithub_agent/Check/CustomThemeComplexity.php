<?php
/**
 * @file
 * Measure the complexity of custom theme code by getting a few metrics.
 * See ViewsComplexity.php for classnaming conventions to make this work.
 */

Use Drupal\Core\Extension\InfoParser;

class SiteAuditCheckPoaudithubCustomThemeComplexity extends \SiteAuditCheckAbstract {

  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('Custom theme complexity');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Check the complexity of custom theme.');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getResultFail().
   */
  public function getResultFail() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultInfo().
   * @todo: Format output for JSON, as it currently only handles Drush and HTML
   * output.
   */
  public function getResultInfo() {

    $default_theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    $theme_path = \Drupal::theme()->getActiveTheme()->getPath();
    
    $parser = new InfoParser;
    $info = $parser->parse($theme_path . '/' . $default_theme . '.info.yml'); // E.g. core/themes/bartik/bartik.info.yml

    $files = $this->countLinesInDir(drupal_realpath($theme_path));
    $css_files = preg_grep("/.*\.css$/", array_keys($files));
    $javascript_files = preg_grep("/.*\.js$/", array_keys($files));
    $custom_template_files = preg_grep("/.*\.html\.twig$/", array_keys($files));

    $output = dt(
      "Default theme: @theme (@machine_name)\n" .
      "Base theme: @base_theme\n" .
      "Number of template files: @tplphp\n" .
      "!tplphplist\n" .
      "CSS files: !css\n" .
      "JS files: !js\n" .
      "All files with line count: !files\n",
      array(
        '@theme' => $info['name'],
        '@machine_name' => $default_theme,
        '@base_theme' => $info['base theme'],
        '@tplphp' => count($custom_template_files),
        '!tplphplist' => $this->generateUl($custom_template_files), // need to refactor for non html
        '!css' => $this->generateUl($css_files), // need to refactor for non html
        '!js' => $this->generateUl($javascript_files), // need to refactor for non html
        '!files' => $this->generateUl($files),
      )
    );

    // Use HTML for line breaks if need to output HTML.
    if (drush_get_option('html')) {
      $output = str_replace("\n", "\n<br/>", $output);
    }
    return $output;
  }


  /**
   * Implements \SiteAudit\Check\Abstract\getResultPass().
   */
  public function getResultPass() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getResultWarn().
   */
  public function getResultWarn() {}

  /**
   * Implements \SiteAudit\Check\Abstract\getAction().
   */
  public function getAction() {}

  /**
   * Implements \SiteAudit\Check\Abstract\calculateScore().
   */
  public function calculateScore() {

    $this->registry['custom_theme'] = array();
    return parent::AUDIT_CHECK_SCORE_INFO;
  }


  /**
   * Recursively count number of lines for each file in a dir, as a proxy
   * measure for code complexity.
   * @param string $uri
   *   Relative path of dir (relative to Drupal root).
   * @return array
   *   Array of file line data array([file relative path] => [num of lines]).
   */
  private function countLinesInDir($uri) {
    $extensions = $this->getExtensions();

    $realPath = drupal_realpath($uri);

    $it = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($realPath)
    );

    $files = array();
    foreach ($it as $file) {
      if ($file->isDir()) {
        continue;
      }
      $parts = explode('.', $file->getFilename());
      $extension = end($parts);
      if (in_array($extension, $extensions)) {

        // Regex containing drupal root dir to search for and delete in the
        // absolute filepath.
        $searchRegex = '/^' . str_replace('/', '\/', DRUPAL_ROOT) . '/';

        // Convert absolute path to relative URI by deleting initial drupal root
        // part.
        $fileUri = preg_replace($searchRegex, '', $file->getPathname());

        $files[$fileUri] = count(file($file->getPathname()));
      }
    }
    return $files;
  }


  /**
   * The file extensions to be included in line count.
   * @return array 
   *   Array of file extensions.
   */
  private function getExtensions() {
    return array('php', 'js', 'css', 'twig');
  }


  /**
   * Generates an unordered list or flattened text version of a nested array.
   *
   * @param array $array
   *   Security Review results.
   * @param bool $html
   *   TRUE if the result should be rendered as HTML.
   * @param int $indentation
   *   The number of spaces; defaults to 6.
   *
   * @return string
   *   Formatted result.
   */
  private function generateUl($array, $html = TRUE, $indentation = 6) {
    $result = $html ? '<ul>' : '';
    foreach ($array as $key => $value) {
      $result .= $html ? '<li>' : PHP_EOL . str_repeat(' ', $indentation);
      $result .= $key . ': ';
      if (is_array($value)) {
        $result .= $this->generateUl($value, $html, $indentation + 2);
      }
      elseif (isset($value->name) && $value->name) {
        $result .= $value->name;
      }
      elseif ($value) {
        $result .= $value;
      }
      $result .= $html ? '</li>' : '';
    }
    $result .= $html ? '</ul>' : '';
    return $result;
  }
}
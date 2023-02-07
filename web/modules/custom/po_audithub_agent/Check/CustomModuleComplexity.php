<?php
/**
 * @file
 * Measure the complexity of custom module code by getting a few metrics.
 * See ViewsComplexity.php for classnaming conventions to make this work.
 */

Use Drupal\Core\Extension\InfoParser;

class SiteAuditCheckPOAuditHubCustomModuleComplexity extends \SiteAuditCheckAbstract {

  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('Custom module complexity');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Check the complexity of custom modules.');
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

    $total_lines_count = 0;

    // Returns an array of custom modules array([machine_name] => [relative path]).
    $customModules = get_custom_modules();

    $output = dt('No of custom modules: @modCount',
      array('@modCount' => count($customModules)));

    $output .= "\n\n";

    if (drush_get_option('html') == TRUE) {
      $output .= '<table class="table table-condensed">';
      $output .= '<tr>' .
        '<th>' . dt('Module, description, path') . '</th>' .
        (\Drupal::moduleHandler()->moduleExists('features') ? '<th>' . dt('Feature module') . '</th>' : '') .
        '<th>' . dt('Dependencies') . '</th>' .
        '<th>' . dt('No of code files (@extensions)', 
          array('@extensions' => implode(', ', $this->getExtensions()))) . '</th>' .
        '<th>' . dt('Total no of lines in code files') . '</th>' .
        '</tr>';
    }

    if (\Drupal::moduleHandler()->moduleExists('features')) {
      $feature_modules_list = features_get_features();
      $custom_feature_modules = array_intersect(
        array_keys($customModules),
        array_keys($feature_modules_list)
      );
    }

    foreach ($customModules as $modName => $modUri) {

      // Count the lines for each file in the module dir, as a proxy measure
      // for code complexity.
      $linesData = $this->countLinesInDir(drupal_realpath($modUri));

      $filesCount = count($linesData);
      $linesCount = array_sum($linesData);
      $total_lines_count += $linesCount;

      // Parse info file
      $parser = new InfoParser;
      $info = $parser->parse($modUri . '/' . $modName . '.info.yml');// E.g. modules/contrib/security_review/security_review.info.yml
      isset($info['dependencies']) ? $dependencies = implode(', ', $info['dependencies']) : $dependencies = '';

      // Check if this is a feature module
      if (\Drupal::moduleHandler()->moduleExists('features')) {
        $is_feature_module = in_array($modName, $custom_feature_modules) ? t('Yes') : '';
      }

      if (drush_get_option('html') == TRUE) {
        $output .= "<tr>" .
          "<td>$modName<br />$info[description]<br />$modUri</td>" .
          (\Drupal::moduleHandler()->moduleExists('features') ? "<td>$is_feature_module</td>" : "") .
          "<td>$dependencies</td>" .
          "<td>$filesCount</td>" .
          "<td>$linesCount</td>" .
          "</tr>";
      }
      else {
        $output .= dt(
          "Module: @modName\n" .
          "Description: @description\n" .
          "Path: @path\n" .
          (\Drupal::moduleHandler()->moduleExists('features') ? "Feature module: @is_feature_module" : "") .
          "Dependencies: @dependencies\n" .
          "No of code files (@extensions): @filesCount\n" .
          "Total no of lines in code files: @linesCount",
          array(
            '@modName' => $modName,
            '@description' => $info['description'],
            '@path' => $modUri,
            '@is_feature_module' => $is_feature_module,
            '@dependencies' => $dependencies,
            '@extensions' => implode(', ', $this->getExtensions()),
            '@filesCount' => $filesCount,
            '@linesCount' => $linesCount
          )
        );
        $output .= "\n\n";
      }
    }

    if (drush_get_option('html') == TRUE) {
      $output .= '</table>';
    }

    $output .= dt('Total custom modules lines count: @total', 
      array('@total' => $total_lines_count));

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

    $this->registry['custom_modules'] = array();
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
    return array('php', 'module', 'inc', 'js', 'css');
  }
}
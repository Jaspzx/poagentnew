<?php
/**
 * @file
 * Class for checking the complexity of the Views architecture on the site.
 *
 * The classname needs to start with SiteAuditCheck, then report name, then
 * check name. See \SiteAuditReportAbstract::__construct(), which guesses the
 * check classname based on the check name.
 *
 * Class instantiation in \SiteAuditReportAbstract::__construct() is done
 * without namespacing so namespaces should not be used.
 */

Use Drupal\views\Entity\View;

class SiteAuditCheckPOAuditHubViewsComplexity extends \SiteAuditCheckAbstract {

  /**
   * Implements \SiteAudit\Check\Abstract\getLabel().
   */
  public function getLabel() {
    return dt('Views complexity');
  }

  /**
   * Implements \SiteAudit\Check\Abstract\getDescription().
   */
  public function getDescription() {
    return dt('Check the complexity of the Views architecture.');
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

    $output = '';
    $displays_maximum = 0;
    $displays_maximum_view_names = array();
    $displays_sum = 0;

    $views = View::loadMultiple();

    foreach ($views as $view) {

      // View name.
      $output .= dt('View: @human_name (@machine_name)', array(
        '@human_name' => $view->label(),
        '@machine_name' => $view->id(),
      ));
      $output .= "\n";

      // No. of displays in the view.
      $displays_count = count($view->get('display'));
      $output .= dt('No. of displays: @count', array(
        '@count' => $displays_count));
      $output .= "\n";

      // Update maximum no. of displays.
      if ($displays_count > $displays_maximum) {
        $displays_maximum = $displays_count;
        $displays_maximum_view_names[] = $view->id();
      }

      // Sum all displays for average.
      $displays_sum += $displays_count;

      // Details of each display.
      foreach ($view->get('display') as $display_machine_name => $display_obj) {
        $output .= dt('Display: @human_name (@machine_name) | @display_plugin', array(
          '@human_name' => $display_obj['display_title'],
          '@machine_name' => $display_machine_name,
          '@display_plugin' => $display_obj['display_plugin'],
        ));
        $output .= "\n";
      }
      $output .= "\n";
    }

    // Views count
    $views_count = count($this->registry['views']);

    // Displays average
    $displays_average = $displays_sum / $views_count;

    // View summary.
    $output .= dt('Enabled Views count: @count', array(
      '@count' => $views_count));
    $output .= "\n";
    $output .= dt('Average displays per views: @average', array(
      '@average' => $displays_average));
    $output .= "\n";
    $output .= dt('Maximum number of displays: @maximum', array(
      '@maximum' => $displays_maximum));
    $output .= "\n";
    $output .= dt('Can be found in views: @names', array(
      '@names' => implode(', ', $displays_maximum_view_names)));
    $output .= "\n\n";

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

    $this->registry['views'] = array();

    $views = View::loadMultiple();

    foreach ($views as $key => $view) {
      if (! $view->disabled) {
        $this->registry['views'][] = $view->label();
      }
    }

    return parent::AUDIT_CHECK_SCORE_INFO;
  }
}


<?php

/**
* Implements hook_drush_command().
*/
function po_audithub_agent_drush_command() {
  $cmds = array();
  // Pixel Onion custom audit commands
  $cmds['audit-poaudithub'] = array(
    'description' => dt('Audit Views.'),
    'aliases' => array('poaudit'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    // 'options' => site_audit_common_options(),
    'checks' => array(
      // Actual classnames must start with 'SiteAuditCheck'
      array(
        'name' => 'ViewsComplexity',
        'location' => __DIR__ . '/Check/ViewsComplexity.php',
      ),
      array(
        'name' => 'CustomModuleComplexity',
        'location' => __DIR__ . '/Check/CustomModuleComplexity.php',
      ),
      array(
        'name' => 'CustomThemeComplexity',
        'location' => __DIR__ . '/Check/CustomThemeComplexity.php',
      ),
    ),
  );

  // Drupal update status commands
  $cmds['po-pm-updatestatus'] = array(
    'description' => dt('Get update status for all modules, equivalent to `drush pm-updatestatus`.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
    ),
    'aliases' => array('poups'),
  );

  // Site Audit commands
  $cmds['po-site-audit'] = array(
    'description' => dt('Perform site audit and generate a report.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
    ),
    'aliases' => array('posa'),
  );

  // Hacked! module commands
  $cmds['po-hacked-list-projects'] = array(
    'description' => dt('Get list of hacked modules, equivalent to `drush hacked-list-projects`. Requires `hacked` module.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
    ),
    'aliases' => array('pohlp'),
  );
  $cmds['po-hacked-details-diff'] = array(
    'description' => dt('For each hacked module, output a details and diff. Requires `hacked` module. 
        If this command is run by its own, it will first check if hacked-list-projects have been previously run. 
        If report cache is available, use the report cache to determine the list of hacked modules.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
    ),
    'aliases' => array('pohdd'),
  );

  // Security Review commands
  $cmds['po-secrev'] = array(
    'description' => dt('Do a security review. Requires `security_review` module.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
    ),
    'aliases' => array('posecrev'),
  );

  // Audit all components
  $cmds['po-all-mod'] = array(
    'description' => dt('Perform all steps of the Pixel Onion Audit. Requires all modules required by all commands.'),
    'options' => array(
      'output-dir' => dt('Output directory for report. Default is ./audit_results'),
      'project-name' => dt('Machine name of project (lowercase).'),
      'modules' => dt('Additional modules to check. By default custom modules and themes are already included without enlisting them in this option. Separate multiple values by comma.')
    ),
    'aliases' => array('poallmod'),
  );
  return $cmds;
}

/**
 * Command callback for drush audit_po_audithub_agent_views.
 */
function drush_po_audithub_agent_audit_poaudithub() {

  require_once __DIR__ . '/Report/Poaudithub.php';
  $report = new SiteAuditReportPoaudithub();
  $report->render();
}

/**
 * Callback for drush po site audit command.
 */
function drush_po_audithub_agent_po_site_audit() {

  // Check if Drush commands required for this function are available.
  $cmds_available = po_audithub_agent_check_drush_cmds_available(
    array('audit-all' => 'Site Audit (site_audit)'));

  if (! $cmds_available) {
    return;
  }

  // Invoke the Site Audit module to do all audits, with html output styled in bootstrap.
  $drush_result_html = drush_invoke_process('@self', 'audit-all', array(), array('--html', '--bootstrap', '--detail'));
  $drush_result_json = drush_invoke_process('@self', 'audit-all', array(), array('--json', '--detail'));

  // Write output to file.
  po_audithub_agent_create_report($drush_result_html['output'], po_audithub_agent_get_project_name() . '-audit-report.html');
  po_audithub_agent_create_report($drush_result_json['output'], po_audithub_agent_get_project_name() . '-audit-report.json');
}

/**
 * Callback for minor update status command.
 */
function drush_po_audithub_agent_po_pm_updatestatus() {

  // This Drush command is in Drush core hence is always available i.e. no need
  // to check if dependencies are installed.
  $drush_result = drush_invoke_process('@self', 'pm-updatestatus', array(), array('field-labels' => 1, 'format' => 'csv'));

  // Write output to file.
  po_audithub_agent_create_report($drush_result['output'], po_audithub_agent_get_project_name() . '-modules-update-summary.csv');
}

/**
 * Callback for generating hacked projects summary.
 */
function drush_po_audithub_agent_po_hacked_list_projects() {

  // Check if Drush commands required for this function are available.
  $cmds_available = po_audithub_agent_check_drush_cmds_available(
    array('hacked-list-projects' => 'Hacked (hacked)'));

  if (! $cmds_available) {
    return;
  }

  $drush_result = drush_invoke_process('@self', 'hacked-list-projects', array(), array('--force-rebuild'));

  // Write output to file.
  po_audithub_agent_create_report($drush_result['output'], po_audithub_agent_get_project_name() . '-hacked--summary.txt');
}

/**
 * Callback for generating individual module hacked details and diff.
 */
function drush_po_audithub_agent_po_hacked_details_diff() {

  // Check if Drush commands required for this function are available.
  $cmds_available = po_audithub_agent_check_drush_cmds_available(
    array(
      'hacked-list-projects' => 'Hacked (hacked)',
      'hacked-details' => 'Hacked (hacked)',
      'hacked-diff' => 'Hacked (hacked)',
    ));

  if (! $cmds_available) {
    return;
  }

  $drush_result = drush_invoke_process('@self', 'hacked-list-projects', array(), array('--force-rebuild'));
  $hacked_result = $drush_result['object'];

  // Run hacked-details and hacked-diff for individual hacked module.
  foreach ($hacked_result as $module) {
    // Check if the module is hacked.
    if ($module['status'] == 3) {
      $output = '';
      $drush_result = drush_invoke_process('@self', 'hacked-details', array($module['name']));
      $output .= $drush_result['output'];
      $drush_result = drush_invoke_process('@self', 'hacked-diff', array($module['name']));
      $output .= $drush_result['output'];
      // Write output to file.
      po_audithub_agent_create_report($output, po_audithub_agent_get_project_name() . '-hacked-' . $module['name'] . '.txt');
    }
  }
}

function drush_po_audithub_agent_po_secrev() {

  // Check if Drush commands required for this function are available.
  $cmds_available = po_audithub_agent_check_drush_cmds_available(
    array('security-review' => 'Security Review (security_review)'));

  if (! $cmds_available) {
    return;
  }

  $drush_result = drush_invoke_process('@self', 'security-review', array(), array('--results', '--store'));

  // Process secrev output from drush log.
  $output = dt("Please check `/admin/reports/security-review` for more details.\n\n");
  $output .= dt($drush_result['output']);
  $output .= print_r($drush_result['object'], TRUE);

  // Write output to file.
  po_audithub_agent_create_report($output, po_audithub_agent_get_project_name() . '-secrev.txt');
}

/**
 * Callback for all functions.
 */
function drush_po_audithub_agent_po_all_mod() {

  // Dependencies will be checked in each function before drush command
  // executed, no need to check here.

  drush_po_audithub_agent_po_hacked_list_projects();
  drush_po_audithub_agent_po_hacked_details_diff();
  drush_po_audithub_agent_po_pm_updatestatus();
  drush_po_audithub_agent_po_site_audit();
  drush_po_audithub_agent_po_secrev();
}

/**
 * Implements hook_drush_command_alter().
 * Custom report to audit-all.
 */
function po_audithub_agent_drush_command_alter(&$command) {
  if ($command['command'] == 'audit-all') {
    $command['reports'][] = array(
      'name' => 'Poaudithub',
      'location' => __DIR__ . '/Report/Poaudithub.php',
    );
  }
}

/**
 * Helper function to check if specified drush command is available for execution.
 * @param  array    $cmds_to_check  Array of commands as key, and Module of the command as value.
 * @return boolean
 */
function po_audithub_agent_check_drush_cmds_available(array $cmds_to_check) {
  $cmds_available = drush_get_commands();
  $all_cmds_available = TRUE;
  $err_msg = '';

  foreach ($cmds_to_check as $cmd => $module) {
    if (!isset($cmds_available[$cmd])) {
      $all_cmds_available = FALSE;
      $err_msg .= dt("@cmd is not available. Please install module @module\n",
        array('@cmd' => $cmd, '@module' => $module));
    }
  }

  if (!$all_cmds_available) {
    // drupal_set_message($err_msg, 'error');
    \Drupal::messenger()->addError($err_msg);
  }

  return $all_cmds_available;
}

/**
 * Dump the command output to file.
 * 
 * @param mixed $content 
 *   Command output to be written to file.
 * @param string $filename
 *   Filename and extension without path.
 * @return bool
 *   True when file has been successfully created.
 */
function po_audithub_agent_create_report($content, $filename) {
  $output_dir = po_audithub_agent_prepare_output_path();
  $output_filename = $filename;

  // Report full path and filename.
  $output_filepath = $output_dir . DIRECTORY_SEPARATOR . $output_filename;

  // Write to file.
  file_put_contents($output_filepath, $content);

  // Give indication that file has been successfully created.
  // drupal_set_message(dt('Generated report file @filepath', array('@filepath' => $output_filepath)));
  \Drupal::messenger()->addStatus('Generated report file @filepath', array('@filepath' => $output_filepath));
  return TRUE;
}

/**
 * Prepare the output folder
 * 
 * @return string
 *   Path without the directory separator at the end.
 */
function po_audithub_agent_prepare_output_path() {
  $output_dir = drush_get_option('output-dir', './../private/po_audit_files');
  $project_name = po_audithub_agent_get_project_name();

  $output_dir = po_audithub_agent_expand_tilde($output_dir);

  // Make dir if it doesn't exist.
  drush_mkdir($output_dir);

  // drupal_set_message(dt("Output dir: @output_dir\nProject name: @project_name",
  //   array(
  //     '@output_dir' => $output_dir,
  //     '@project_name' => $project_name,
  //   )
  // ));
  \Drupal::messenger()->addStatus("Output dir: @output_dir\nProject name: @project_name",
    array(
      '@output_dir' => $output_dir,
      '@project_name' => $project_name,
    )
  );

  return $output_dir;
}

/**
 * Get an array of custom module names and paths.
 *
 * Compare the list generated by update module and enabled modules.
 *
 * @param $options (array)
 *   See http://drushcommands.com/drush-7x/pm/pm-list. Defaults to non-core, enabled modules only.
 *
 * @return array
 *   Associative array of custom module machine names and their paths.
 */
function get_custom_modules() {
  // Get the list of non-core, enabled modules on the site - default.
  $enabled_modules = system_get_info('module');

  foreach ($enabled_modules as $key => $value) {
    $enabled_modules[$key] = $enabled_modules[$key]['name'];
  }

  module_load_include('inc', 'update', 'update.compare');
  module_load_include('module', 'update', 'update');
  $projects_list = update_calculate_project_data(update_get_available(TRUE));

  $modules = array();
  $project_error_status = array(
    UPDATE_NOT_CHECKED, 
    UPDATE_UNKNOWN, 
    UPDATE_NOT_FETCHED, 
    UPDATE_FETCH_PENDING,
  );
  foreach ($projects_list as $project_name => $project) {
    if (!in_array($project['status'], $project_error_status)) {
      foreach ($project['includes'] as $module_machine_name => $module_human_name) {
        $modules[$module_machine_name] = $module_human_name;
      }
    }
  }

  $custom_modules = array_diff_key($enabled_modules, $modules);
  
  // Not required for our list.
  unset($custom_modules['standard']);

  $custom_modules = get_project_paths($custom_modules);

  return $custom_modules;
}

/**
 * Get project paths.
 *
 * @param $modules (array)
 *  An array of name of modules. Ensure the names are in machine names.
 *
 * @return array
 *   Associative array of module machine names and their paths.
 */
function get_project_paths($modules) {
  $module_paths = array();
  foreach ($modules as $key => $value) {
    $module_paths[$key] = drupal_get_path('module', $key);
  }
  return $module_paths;
}

/**
 * Get the project name
 * 
 * @return  string
 *   Get the project name provided by argument, otherwise use the default project name.
 */
function po_audithub_agent_get_project_name() {
  $site_name = \Drupal::config('system.site')->get('name');
  $project_name_default = $site_name;
  $project_name = drush_get_option('project-name', $project_name_default);
  return $project_name;
}

/**
 * Expand tilde symbol in a path.
 *
 * @param string $path
 *   Path to expand.
 * @return string
 *   Path with tilde symbols expanded.
 */
function po_audithub_agent_expand_tilde($path) {

  if (function_exists('posix_getuid') && strpos($path, '~') !== false) {
    $info = posix_getpwuid(posix_getuid());
    $path = str_replace('~', $info['dir'], $path);
  }

  return $path;
}

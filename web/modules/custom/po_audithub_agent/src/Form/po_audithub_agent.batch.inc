<?php

use Drupal\node\Entity\Node;

function po_audithub_agent_process_audit($command, &$context) {
  $time_start = microtime(TRUE);
  $drush = '/Users/jaspzx/.composer/global/drush/drush/vendor/drush/drush/drush.php';
  $context['results']['test'] = $command;

  $context['results']['result'] = exec($drush . $command, $output, $return);
  $context['results']['time'] = $time_start;
}

function po_audithub_agent_process_audit_batch_finished($success, $results, $operations) {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  $time = microtime(TRUE);
  $time_elapsed = $time - $results['time'];
  if ($success) {
    $message = t('Audit completed. Time taken: %time seconds.', array(
      '%time' => $time_elapsed,
    ));
  }
  else {
    $message = t('Finished with an error.');
  }
  // drupal_set_message($message);
  \Drupal::messenger()->addStatus($message);
  \Drupal::messenger()->addStatus($results['test']);

}

<?php

namespace Drupal\po_audithub_agent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Process\Process;

include 'po_audithub_agent.batch.inc';

/**
 * A simple form class.
 */
class SimpleForm extends FormBase {
  public function getFormId() {
    return 'po_audithub_agent_audit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['api-key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('api-key'),
      '#description' => $this->t('Enter the api-key that you have registered on Pixel Onion Audit Hub'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('po_audithub_agent.settings')->get('po_audithub_agent.api_key'),
    );

    $form['test'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configurations'),
      '#submit' => array([$this, 'save_configurations']),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run audit'),
    );

    return $form;
  }

  public function save_configurations(array &$form, FormStateInterface $form_state) {
    //Mutable Config
    $config = \Drupal::service('config.factory')->getEditable('po_audithub_agent.settings');
    $config->set('po_audithub_agent.api_key', $form['api-key']['#value']);
    $config->save();
    // drupal_set_message(t('Configurations saved!'), 'status');
    \Drupal::messenger()->addStatus('Configurations saved!');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $command = ' poall';
    $drush = '/Users/jaspzx/.composer/global/drush/drush/vendor/drush/drush/drush.php';

    $site_name = \Drupal::config('system.site')->get('name');

    $operations[] = ['po_audithub_agent_process_audit', [$command]];

    $batch = array(
      'title' => t('Auditing ' . $site_name),
      'operations' => $operations,
      'finished' => 'po_audithub_agent_process_audit_batch_finished',
      'init_message' => t('Running site audit tests on ' . $site_name . ', please wait.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The audit process has encountered an error.'),
      'file' => drupal_get_path('module', 'po_audithub_agent') . '/po_audithub_agent.batch.inc',
    );

    batch_set($batch);
  }
}

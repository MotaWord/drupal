<?php

/**
 * @file
 * Contains Drupal\tmgmt_mw\MwTranslatorUi.
 */

namespace Drupal\tmgmt_mw;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\tmgmt_mw\Plugin\tmgmt\Translator\MwTranslator;

/**
 * MW translator UI.
 */
class MwTranslatorUi extends TranslatorPluginUiBase {

  /**
   * Last settings before starting the project. Presented after clicking "Send to translator".
   * Creates a quote, shows its details and asks for style guide and glossary (not available yet).
   *
   * @param array $settings
   * @param FormStateInterface $form_state
   * @param JobInterface $job
   *
   * @return mixed
   */
  public function checkoutSettingsForm(array $settings, FormStateInterface $form_state, JobInterface $job) {
    $settings['quote'] = array(
      '#type' => 'fieldset',
      '#title' => t('Quote'),
    );

    $quote = $this->getQuote($job);

    if (!$quote) {
      $settings['quote']['error'] = array(
        '#type' => 'item',
        '#title' => t('Error'),
        '#markup' => 'We could not calculate your quote. Please let us know at info@motaword.com',
      );

      return $settings;
    }

    $settings['quote']['word_count'] = array(
      '#type' => 'item',
      '#title' => t('Word count'),
      '#markup' => $quote->word_count,
    );

    $settings['quote']['price'] = array(
      '#type' => 'item',
      '#title' => t('Price'),
      '#markup' => $quote->price->amount . strtoupper($quote->price->currency),
    );

    $settings['quote']['delivery'] = array(
      '#type' => 'item',
      '#title' => t('Delivery'),
      '#markup' => \Drupal::service('date.formatter')->format($quote->delivery_at, "long"),
    );

    /** @todo Not implemented yet. */
    /*$settings['additionals'] = array(
      '#type' => 'fieldset',
      '#title' => t('Additional Documents'),
    );

    $settings['styleguide'] = array(
      '#type' => 'file',
      '#title' => t('Style guide (optional)'),
      '#size' => 50,
      '#description' => t('Supported formats: .PDF and .DOCX'),
    );

    $settings['glossary'] = array(
      '#type' => 'file',
      '#title' => t('Glossary (optional)'),
      '#size' => 50,
      '#description' => t('Supported formats: .TBX and .XLSX'),
    );*/

    if (!isset($quote->allow_invoicing) || !$quote->allow_invoicing) {
      $settings['payment'] = array(
        '#type' => 'fieldset',
        '#title' => t('Payment'),
      );

      $settings['payment']['note'] = array(
        '#type' => 'item',
        '#markup' => '<span style="color: green;">Once you submit, we will charge your card on file.</span>',
      );
    }

    return $settings;
  }

  /**
   * Creates a project with the documents.
   *
   * @param JobInterface $job
   *
   * @return bool|object
   */
  public function getQuote(JobInterface $job) {
    /** @var MwTranslator $controller */
    $controller = $job->getTranslator()->getPlugin();

    try {
      return $controller->getQuote($job);
    } catch(TMGMTException $e) {
      \Drupal::logger('tmgmt_mw')->error($e->getMessage());
      drupal_set_message($e->getMessage(), 'error');

      return false;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    \Drupal::logger('tmgmt_mw')->error('here: '.$translator->getSetting('api_client_id'));

    $form['registration_link'] = [
      '#type' => 'markup',
      '#markup' => t(print_r($translator->getSetting('api_client_id'), true).'To use MotaWord for your translations, visit the Developer Portal to create a Drupal API client (app): <a href="@url" alt="MotaWord">@url</a>.', ['@url' => 'https://www.motaword.com/developer']),
    ];
    $form['api_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('MW API Client ID'),
      '#default_value' => $translator->getSetting('api_client_id'),
      '#description' => t('Please enter your API client ID or visit <a href="@url">the API keys page</a> to get one.', ['@url' => 'https://www.motaword.com/developer']),
    );
    $form['api_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('MW API Client Secret Key'),
      '#default_value' => $translator->getSetting('api_client_secret'),
      '#description' => t('Please enter your API client secret or visit <a href="@url">the API keys page</a> to get one.', ['@url' => 'https://www.motaword.com/developer']),
    );
    $form['use_sandbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the sandbox'),
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => t('Check to use the testing environment.'),
    );
      $form['use_multiple_source_files'] = array(
          '#type' => 'checkbox',
          '#title' => t('Use separate files for each post/page'),
          '#default_value' => $translator->getSetting('use_multiple_source_files'),
          '#description' => t('Compile all pages in one source file, or use separate files for each page. Requires ZIP extension in your system.'),
      );
    //$form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    /** @var MwTranslator $translator_plugin */
    $translator_plugin = $translator->getPlugin();
    $translator_plugin->setTranslator($translator);
    $settings = $form['plugin_wrapper']['settings'];

    try {
      $account = $translator_plugin->getAccountDetails();
    } catch(\Exception $e) {
      $account = false;
    }

    if(!$account) {
      \Drupal::logger('tmgmt_mw')->error(t('The "MW API Client ID" or "MW API Client Secret Key" is not valid.'));
      $form_state->setError($settings['api_client_secret'], t('The "MW API Client ID" or "MW API Client Secret Key" is not valid.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    if (!isset($job->reference->mw_quote_id) || !$job->reference->mw_quote_id) {
      return parent::checkoutInfo($job);
    }

    /** @var MwTranslator $plugin */
    $plugin = $job->getTranslatorPlugin();
    $project = $plugin->getProject($job);
    if (!$project) {
      return parent::checkoutInfo($job);
    }

    $progress = $plugin->getProjectProgress($job);

    $form = array();

    $form['summary'] = array(
      '#title' => 'Summary',
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
    );

    $form['progress'] = array(
      '#title' => 'Progress',
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
    );

    $summaryMarkup = '
            <p>
                <strong>Project ID: </strong>' . $project->id . '<br/>
                <strong>Word Count: </strong>' . $project->word_count . '<br/>
                <strong>Delivery: </strong>' . \Drupal::service('date.formatter')->format(
        $project->delivery_at,
        "long"
      ) . '<br/>
                <strong>Price: </strong>' . $project->price->amount . strtoupper(
        $project->price->currency
      ) . '<br/><br/>
                <a style="background: #3277D4; color: white; font-weight: bold; padding: 10px;" target="_blank" href="https://www.motaword.com/dashboard/projects/' . $project->id . '">GO TO MOTAWORD DASHBOARD</a>
            </p>
        ';

    $form['summary']['summary'] = array(
      '#type' => 'item',
      '#markup' => $summaryMarkup
    );

    $progressMarkup = '
            <p>
                <strong>Translation: </strong> ' . $progress->translation . '%<br/>
                <strong>Proofreading: </strong> ' . $progress->proofreading . '%<br/>
                <strong>Total: </strong> ' . $progress->total . '%<br/>
            </p>
        ';

    if (!!$progress) {
      $form['progress']['progress'] = array(
        '#type' => 'item',
        '#markup' => $progressMarkup
      );
    }

    if (property_exists($project, 'custom')) {
      $customData = array();

      $form['custom'] = array(
        '#title' => 'Custom Data',
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
      );

      foreach (get_object_vars($project->custom) as $key => $value) {
        $customData[] = '<strong>' . $key . ':</strong> ' . $value;
      }

      $customMarkup = '
            <p>
                <strong>Callback URL: </strong> ' . $project->callback_url . '%<br/>
                ' . implode('<br/>', $customData) . '
            </p>
        ';

      $form['custom']['custom'] = array(
        '#type' => 'item',
        '#markup' => $customMarkup
      );
    }

    if ($job->isActive()) {
      $form['actions']['pull'] = array(
        '#type' => 'submit',
        '#value' => t('Pull translations'),
        '#submit' => array(array($this, 'submitPullTranslations')),
        '#weight' => -10,
      );
    }

    return $form;
  }

  /**
   * Pull translations from MotaWord for this job.
   *
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitPullTranslations(array $form, FormStateInterface $form_state) {
    /** @var Job $job */
    $job = $form_state->getFormObject()->getEntity();
    /** @var MwTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->retrieveTranslation($job);
    tmgmt_write_request_messages($job);
  }

}

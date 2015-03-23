<?php
/**
 * Drupal Plugin - User Interface
 *
 * PHP version 5.3
 *
 * @category Plugins
 * @package  Drupal
 * @author   Oytun Tez <oytun@motaword.com>
 */

/**
 * @file
 * Provides myGengo translation plugin controller.
 */
class TMGMTMWTranslatorUIController extends TMGMTDefaultTranslatorUIController {
  /**
   * @var TMGMTMotaWordAPI
   */
  protected $api;

  /**
   * @param                  $form
   * @param                  $form_state
   * @param \TMGMTTranslator $translator
   * @param bool             $busy
   *
   * @override TMGMTDefaultTranslatorUIController::pluginSettingsForm()
   *
   * @return mixed
   */
  public function pluginSettingsForm($form,
                                     &$form_state,
                                     TMGMTTranslator $translator,
                                     $busy = FALSE
  ) {
    $form['api_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#default_value' => $translator->getSetting('api_client_id'),
      '#description' => t('Please enter yout Account ID for MotaWord service.'),
    );
    $form['api_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client Secret Key'),
      '#default_value' => $translator->getSetting('api_client_secret'),
      '#description' => t(
        'Please enter your secret API key for MotaWord service.'
      ),
    );
    $form['use_sandbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the sandbox'),
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => t('Check to use the testing environment.'),
    );

    return parent::pluginSettingsForm($form, $form_state, $translator, $busy);
  }

  public function checkoutSettingsForm($settings, &$form_state, TMGMTJob $job) {
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
      '#markup' => format_date($quote->delivery_at, "long"),
    );

    $settings['additionals'] = array(
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
    );

    $settings['payment'] = array(
      '#type' => 'fieldset',
      '#title' => t('Payment'),
    );

    if (!isset($quote->allow_invoicing) || !$quote->allow_invoicing) {
      $settings['payment']['note'] = array(
        '#type' => 'item',
        '#markup' => '<span style="color: green;">Once you submit, we will charge your card on file.</span>',
      );
    }

    return $settings;
  }

  public function getQuote(TMGMTJob $job) {
    /** @var TMGMTMWPluginController $controller */
    $controller = $job->getTranslatorController();

    try {
      return $controller->getQuote($job);
    } catch(TMGMTException $e) {
      watchdog_exception('tmgmt_mygengo', $e);
      drupal_set_message($e->getMessage(), 'error');

      return false;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(TMGMTJob $job) {
    if (!$job->reference) {
      return parent::checkoutInfo($job);
    }

    $api = $this->getApi($job->getTranslator());
    $project = $api->getProject($job->reference);

    if (!$project) {
      return parent::checkoutInfo($job);
    }

    $progress = $api->getProgress($job->reference);

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
                <strong>Delivery: </strong>' . format_date(
        $project->delivery_at,
        "long"
      ) . '<br/>
                <strong>Price: </strong>' . $project->price->amount . strtoupper(
        $project->price->currency
      ) . '<br/><br/>
                <a style="background: #3277D4; color: white; font-weight: bold; padding: 10px;" target="_blank" href="https://www.motaword.com/projects?key=' . $project->key . '">GO TO MOTAWORD DASHBOARD</a>
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

    return $form;
  }

  protected function getApi(TMGMTTranslator $translator) {
    if (!$this->api) {
      $this->api = new TMGMTMotaWordAPI($translator);
    }

    return $this->api;
  }
}

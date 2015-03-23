<?php
/**
 * Drupal Plugin - Controller
 *
 * PHP version 5.3
 *
 * @category Plugins
 * @package  Drupal
 * @author   Oytun Tez <oytun@motaword.com>
 */

/**
 * Class TMGMTMWPluginController
 */
class TMGMTMWPluginController extends TMGMTDefaultTranslatorPluginController {

  /**
   * @var array       [Drupal code => MotaWord code]
   */
  protected $supportedLanguages = array(
    'af' => 'af',
    'ak' => 'ak',
    'am' => 'am',
    'ar' => 'ar',
    'az' => 'az',
    'be' => 'be',
    'bg' => 'bg',
    'bn' => 'bn',
    'bs' => 'bs',
    'ca' => 'ca',
    'cs' => 'cs',
    'da' => 'da',
    'de' => 'de',
    'el' => 'el',
    'en' => 'en-US',
    'en-gb' => 'en-US',
    'es' => 'es-ES',
    'es-ar' => 'es-AR',
    'es-es' => 'es-ES',
    'es-mx' => 'es-MX',
    'es-us' => 'es-US',
    'et' => 'et',
    'fa' => 'fa',
    'fa-AF' => 'fa-AF',
    'fi' => 'fi',
    'fr' => 'fr',
    'he' => 'he',
    'hi' => 'hi',
    'hr' => 'hr',
    'ht' => 'ht',
    'hu' => 'hu',
    'hy' => 'hy-AM',
    'hy-am' => 'hy-AM',
    'id' => 'id',
    'is' => 'is',
    'it' => 'it',
    'ja' => 'ja',
    'ka' => 'ka',
    'km' => 'km',
    'ko' => 'ko',
    'ku' => 'ku',
    'la' => 'la-LA',
    'la-la' => 'la-LA',
    'lb' => 'lb',
    'lt' => 'lt',
    'lv' => 'lv',
    'mk' => 'mk',
    'ml' => 'ml-IN',
    'ml-in' => 'ml-IN',
    'ms' => 'ms',
    'mt' => 'mt',
    'my' => 'my',
    'ne' => 'ne-NP',
    'ne-np' => 'ne-NP',
    'nl' => 'nl',
    'nb' => 'no',
    'nn' => 'no',
    'pa' => 'pa-IN',
    'pa-in' => 'pa-IN',
    'pl' => 'pl',
    'ps' => 'ps',
    'pt' => 'pt-PT',
    'pt-br' => 'pt-BR',
    'pt-pt' => 'pt-PT',
    'ro' => 'ro',
    'ru' => 'ru',
    'sk' => 'sk',
    'sl' => 'sl',
    'sq' => 'sq',
    'sr' => 'sr',
    'sv' => 'sv-SE',
    'sv-se' => 'sv-SE',
    'ta' => 'ta',
    'th' => 'th',
    'tl' => 'tl',
    'tr' => 'tr',
    'uk' => 'uk',
    'ur' => 'ur-PK',
    'ur-pk' => 'ur-PK',
    'uz' => 'uz',
    'vi' => 'vi',
    'wo' => 'wo',
    'yi' => 'yi',
    'zh' => 'zh-CN',
    'zh-hans' => 'zh-CN',
    'zh-hant' => 'zh-TW',
    'zh-cn' => 'zh-CN',
    'zh-tw' => 'zh-TW',
  );

  /**
   * @var TMGMTMotaWordAPI
   */
  protected $api;

  /**
   * We will check are source and target language supported.
   *
   * @param \TMGMTTranslator $translator
   * @param \TMGMTJob        $job
   *
   * @implements TMGMTTranslatorPluginControllerInterface::canTranslation().
   *
   * @return bool
   */
  public function canTranslate(TMGMTTranslator $translator, TMGMTJob $job) {
    // If Translator is configured - check if the source and
    // target language are supported.
    if ($this->isAvailable($translator)) {
      $languages = $this->getSupportedLanguages();
      /** @var $job->target_language string */
      return isset($languages[$job->source_language]) && isset($languages[$job->target_language]);
    }

    return FALSE;
  }

  /**
   * @param \TMGMTTranslator $translator
   *
   * @implements TMGMTTranslatorPluginControllerInterface::isAvailable()
   *
   * @return bool
   */
  public function isAvailable(TMGMTTranslator $translator) {
    if ($translator->getSetting('api_client_id') && $translator->getSetting(
        'api_client_secret'
      )
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns array of supported languages by MW service.
   *
   * @return array
   *   Returns array of the supported languages.
   *   The key is drupal-style language code,
   *   the value - MW-style language code.
   */
  protected function getSupportedLanguages() {
    return $this->supportedLanguages;
  }

  /**
   * Start the project.
   *
   * @param \TMGMTJob $job
   *
   * @implements TMGMTTranslatorPluginControllerInterface::requestTranslation()
   * @return boolean
   */
  public function requestTranslation(TMGMTJob $job) {
    if(!$job->isTranslatable()) {
      $job->rejected('Your job has been rejected. Contact us at info@motaword.com');
      watchdog(
        'tmgmt',
        'Rejected quote.',
        array(),
        WATCHDOG_DEBUG
      );
      return false;
    }

    if ((int) $job->reference < 1) {
      $quote = $this->getQuote($job);
      $job->reference = $quote->id;
      $job->save();
    }

    $api = $this->getApi($job->getTranslator());

    if (!isset($quote) && !!$job->reference) {
      $quote = $api->getProject($job->reference);
    }

    // Pull the source data array through the job and flatten it.
    $data = array(
      'callback_url' => url('tmgmt_mw_callback', array('absolute' => TRUE)),
      'custom[job_id]' => $job->tjid
    );

    if (isset($job->settings['styleguide']) && !!$job->settings['styleguide']) {
      /**
       * @todo Upload style guide via API.
       */
    }

    if (isset($job->settings['glossary']) && !!$job->settings['glossary']) {
      /**
       * @todo Upload glossary via API.
       */
    }

    /**
     * Returns Quote model.
     */
    $response = $this->getApi($job->getTranslator())->launchProject(
      $job->reference,
      $data
    );

    if (!!$response && isset($response->status) && $response->status === 'started') {
      $job->submitted(
        'Job has been successfully submitted for translation. Project ID is: %project_id',
        array('%project_id' => $quote->id)
      );
    }
    else {
      $job->rejected('Your job has been rejected. Contact us at info@motaword.com');
    }

    return true;
  }

  /**
   * Get a quote (create a project) via the API.
   *
   * @param \TMGMTJob $job
   *
   * @return bool|object
   */
  public function getQuote(TMGMTJob $job) {
    if(!$job->isTranslatable()) {
      $job->rejected('Your job has been rejected. Contact us at info@motaword.com');
      watchdog(
        'tmgmt',
        'Rejected quote.',
        array(),
        WATCHDOG_DEBUG
      );
      return false;
    }

    $languages = $this->getSupportedLanguages();
    $defaultType = 'json';

    $data = array();

    $tmpFile = tmpfile();
    fwrite($tmpFile, $this->prepareDataForSending($job, $defaultType));

    $data['source_language'] = $languages[$job->source_language];
    $data['target_languages'] = array($languages[$job->target_language]);
    $metaData = stream_get_meta_data($tmpFile);
    $data['documents'] = array(
      '@' . $metaData['uri'] . ';filename=' . $job->tjid . '.' . $defaultType
    );

    $quote = $this->getApi($job->getTranslator())->submitProject($data);

    if (!!$quote) {
      $job->reference = $quote->id;
      $job->save();
    }

    return $quote;
  }

  /**
   * Prepares data to be send to MW service and returns XML string.
   *
   * @param TMGMTJob $job
   * @param string   $type File format: json, xml
   *
   * @return string Data for sending to the translator service.
   */
  protected function prepareDataForSending(TMGMTJob $job, $type = 'json') {
    $data = array_filter(
      tmgmt_flatten_data($job->getData()),
      '_tmgmt_filter_data'
    );

    if ($type === 'json') {
      $items = array();
    }
    else {
      $items = '';
    }

    foreach ($data as $key => $value) {
      if ($type === 'json') {
        $items[$key] = $value['#text'];
      }
      else {
        $items .= str_replace(
          array('@key', '@text'),
          array($key, $value['#text']),
          '<item key="@key"><text type="text/html"><![CDATA[@text]]></text></item>'
        );
      }
    }

    if ($type === 'json') {
      return json_encode($items);
    }
    else {
      return '<items>' . $items . '</items>';
    }
  }

  protected function getApi(TMGMTTranslator $translator) {
    if (!$this->api) {
      $this->api = new TMGMTMotaWordAPI($translator);
    }

    return $this->api;
  }

  /**
   * Receives and stores a translation returned by MW.
   *
   * @param TMGMTJob $job
   *
   * @return  boolean
   */
  public function retrieveTranslation(TMGMTJob $job) {
    // Requesting translated test from the MW API server to avoid spam through the callback.
    $api = $this->getApi($job->getTranslator());

    $response = $api->downloadProject((int) $job->reference);
    if (!$response) {
      // Error message will be published by API controller.

      return FALSE;
    }

    $response = $this->parseTranslationData($response);

    if (!$response) {
      $job->rejected(
        'Could not parse translation received from MotaWord. Contact us at info@motaword.com',
        array(),
        'error'
      );

      return FALSE;
    }

    $job->addMessage('The translation has been received.');
    $job->addTranslatedData($response);

    return TRUE;
  }

  /**
   * Parses received translation from MW and returns unflatted data.
   *
   * @param object $data JSON array
   * @param string $type Content type: json, xml
   *
   * @return array            Unflatted data.
   */
  protected function parseTranslationData($data, $type = 'json') {
    if ($type === 'json') {
      // JSON is already coming json_decoded.
      $items = $data;
      $data = array();

      foreach ($items as $key => $text) {
        $data[$key] = array();
        $data[$key]['#text'] = (string) $text;
      }
    }
    else {
      $items = simplexml_load_string($data);
      $data = array();

      if (isset($items->item)) {
        foreach ($items->item as $item) {
          $key = (string) $item['key'];
          if (isset($item->text)) {
            $data[$key]['#text'] = (string) $item->text;
          }
        }
      }
    }

    return tmgmt_unflatten_data($data);
  }

  /**
   * Returns list of languages supported by MW service.
   *
   * @param TMGMTTranslator $translator
   *   Translator object
   * @param string          $source_language
   *   Source language.
   *
   * @return array
   *   Returns array of supported languages
   */
  public function getSupportedTargetLanguages(TMGMTTranslator $translator,
                                              $source_language
  ) {
    $languages = array_keys($this->supportedLanguages);
    $index = array_search($source_language, $languages);

    if(!!$index) {
      array_splice($languages, array_search($source_language, $languages), 1);
    }

    return drupal_map_assoc($languages);
  }
}

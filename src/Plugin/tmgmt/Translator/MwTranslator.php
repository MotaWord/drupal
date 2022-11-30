<?php

/**
 * @file
 * Contains \Drupal\tmgmt_mw\Plugin\tmgmt\Translator\MwTranslator.
 */

namespace Drupal\tmgmt_mw\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_mw\MwApi;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use ZipArchive;

/**
 * MW translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "mw",
 *   label = @Translation("MotaWord"),
 *   description = @Translation("..."),
 *   ui = "Drupal\tmgmt_mw\MwTranslatorUi",
 *   logo = "icons/mw.png",
 * )
 */
class MwTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {
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
   * @var MwApi
   */
  protected $api;
  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The translator.
   *
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(ClientInterface $httpClient, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Sets a Translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   */
  public function setTranslator(TranslatorInterface $translator) {
    $this->translator = $translator;
  }

  /**
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   * @param \GuzzleHttp\ClientInterface|null  $httpClient
   *
   * @return \Drupal\tmgmt_mw\MwApi
   */
  protected function getApi(TranslatorInterface $translator = null, ClientInterface $httpClient = null) {
    if (!$this->api) {
      if(!$translator) {
        if($this->translator) {
          $translator = $this->translator;
        } else {
          throw new ServiceNotFoundException('translator object');
        }
      }

      $this->api = new MwApi($translator, $httpClient ? $httpClient : $this->httpClient);
    }

    return $this->api;
  }

  /**
   * Start the project.
   *
   * @param JobInterface $job
   *
   * @return boolean
   */
  public function requestTranslation(JobInterface $job) {
    /*if(!$job->isTranslatable()) {
      $job->rejected('Your job has been rejected. Contact us at info@motaword.com');
      \Drupal::logger('tmgmt_mw')->error('Rejected quote.');

      return false;
    }*/

    if ((int) $job->getReference() < 1) {
      $quote = $this->getQuote($job);
      $job->reference = $quote->id;
      $job->save();
    }

    $api = $this->getApi($job->getTranslator());
    $reference = $job->getReference();

    if (!isset($quote) && !!$reference) {
      $quote = $api->getProject($reference);
    }

    // Pull the source data array through the job and flatten it.
    $data = array(
      'callback_url' => Url::fromRoute('tmgmt_mw.callback')->setAbsolute()->toString(),
      'custom[job_id]' => $job->id()
    );

    if ($job->getSetting('styleguide')) {
      /**
       * @todo Upload style guide via API.
       */
    }

    if ($job->getSetting('glossary')) {
      /**
       * @todo Upload glossary via API.
       */
    }

    /**
     * Returns Quote model.
     */
    $response = $this->getApi($job->getTranslator())->launchProject(
      $reference,
      $data
    );

    if (!!$response && isset($response->status) && $response->status === 'started') {
      $job->submitted(
        'Job has been successfully submitted for translation. Project ID is: %project_id',
        array('%project_id' => $quote->id)
      );
    } else {
      $job->rejected('Your job has been rejected. Contact us at info@motaword.com');
    }

    return true;
  }

  /**
   * Get a quote (create a project) via the API.
   *
   * @param JobInterface $job
   *
   * @return bool|object
   */
  public function getQuote(JobInterface $job) {
    /*
     * // Jobs are not translatable. See Drupal\tmgmt\Entity\Job.
     * if(!$job->isTranslatable()) {
      $job->rejected('This content type is not marked as translatable in your system. Go to Admin > Structure > Content Types, click "Edit" operation for the content type of this record. Switch to "Language settings" and check "Enable translation". If you have any questions, contact us at info@motaword.com');
      \Drupal::logger('tmgmt_mw')->error('Rejected quote.');
      return false;
    }*/

    $languages = $this->getSupportedLanguages();
    $defaultType = 'json';

    $data = array();

    $data['source_language'] = $languages[$job->getSourceLangcode()];
    $data['target_languages'] = array($languages[$job->getTargetLangcode()]);
    $data['callback_url'] = Url::fromRoute('tmgmt_mw.callback')->setAbsolute()->toString();
    $data['custom[job_id]'] = $job->id();

      $files = $this->prepareDataForSending($job, $defaultType);
      $apiDocuments = [];
      foreach ($files as $fileName => $fileContent) {
          $tmpFile = tempnam(\Drupal::service('file_system')->getTempDirectory(), 'mw_src_');
          file_put_contents($tmpFile, $fileContent);
          $apiDocuments[] = '@' . $tmpFile . ';filename=' . $fileName;
      }
      foreach ($apiDocuments as $i => $apiDocument) {
          $data['documents['.$i.']'] = $apiDocument;
      }

    $quote = $this->getApi($job->getTranslator())->submitProject($data);

    if (!!$quote) {
      $job->reference = $quote->id;
      $job->save();
    }

    return $quote;
  }

  /**
   * Get the record of a previously submitted project
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return null|object
   */
  public function getProject(JobInterface $job) {
    if (!$job->getReference()) {
      return null;
    }

    return $this->getApi($job->getTranslator())->getProject($job->getReference());
  }

  /**
   * Get the progress of a previously submitted project
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return null|object
   */
  public function getProjectProgress(JobInterface $job) {
    if (!$job->getReference()) {
      return null;
    }

    return $this->getApi($job->getTranslator())->getProgress($job->getReference());
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
     * Prepares data to be send to MW service and returns XML string.
     *
     * @param JobInterface $job
     * @param string $type File format: json, xml
     *
     * @return array Data for sending to the translator service. Key is the file name.
     * @throws TMGMTException
     */
  protected function prepareDataForSending(JobInterface $job, $type = 'json') {
    if ($job->getTranslator()->getSetting('use_multiple_source_files')) {
        $files = [];
        foreach ($job->getData() as $key => $jobData) {
            $fileBaseName = $key;

            if (isset($jobData['title'][0]['value']['#text']) && !empty($jobData['title'][0]['value']['#text'])) {
                $fileBaseName = $jobData['title'][0]['value']['#text'];
            }

            $fileBaseName = preg_replace("#[[:punct:]]#", "", $fileBaseName);

            $fileContent = $this->prepareSingleDataForSending($job, $type, $key);

            if (isset($files[$fileBaseName])) {
                $fileBaseName .= '-'.rand();
            }

            $fileBaseName .= '.' . $type;
            $files[$fileBaseName] = $fileContent;
        }

        return $files;
    } else {
        return ['job-' . $job->id() . '.' . $type => $this->prepareSingleDataForSending($job, $type)];
    }
  }

  protected function prepareSingleDataForSending(JobInterface $job, $type = 'json', $keepOnlyThisIndex = null)
  {
      /** @var \Drupal\tmgmt\Data $data_service */
      $data_service = \Drupal::service('tmgmt.data');

      $data = array_filter($data_service->flatten($job->getData()), function($value) {
          return !(empty($value['#text']) || (isset($value['#translate']) && $value['#translate'] === FALSE));
      });

      if ($type === 'json') {
          $items = array();
      }
      else {
          $items = '';
      }

      foreach ($data as $key => $value) {
          // when we are in multiple source files mode,
          // for proper translation return,
          // we need to keep keys like this: "13][title][0][value"
          // they need to start with the job item ID.
          // for this, for each item, we flatten the whole job (instead of job item)
          // and then remove the other job items from the flattened data, so that the file will contain
          // only the current job item.
          if ($keepOnlyThisIndex !== null) {
              if (strpos($key, $keepOnlyThisIndex . $data_service::TMGMT_ARRAY_DELIMITER) !== 0) {
                  continue;
              }
          }

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

  /**
   * @param TranslatorInterface $translator
   *
   * @return \Drupal\tmgmt\Translator\AvailableResult
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('api_client_id') && $translator->getSetting('api_client_secret')) {
      return AvailableResult::yes();
    }

    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
    ]));
  }

  /**
   * We will check are source and target language supported.
   *
   * @param TranslatorInterface $translator
   * @param JobInterface        $job
   *
   * @implements TMGMTTranslatorPluginControllerInterface::canTranslation().
   *
   * @return bool
   */
  public function canTranslate(TranslatorInterface $translator, JobInterface $job) {
    // If Translator is configured - check if the source and
    // target language are supported.
    if ($this->checkAvailable($translator)->getSuccess()) {
      $languages = $this->getSupportedLanguages();
      /** @var $job->target_language string */
      return isset($languages[$job->getSourceLangcode()]) && isset($languages[$job->getTargetLangcode()]);
    }

    return FALSE;
  }

  /**
   * Receives and stores a translation returned by MW.
   *
   * @param JobInterface $job
   *
   * @return  boolean
   */
  public function retrieveTranslation(JobInterface $job) {
    // Requesting translated test from the MW API server to avoid spam through the callback.
    $api = $this->getApi($job->getTranslator());

    $response = $api->downloadProject((int) $job->getReference());
    if (!$response) {
      // Error message will be published by API controller.

      return FALSE;
    }

    // parse translations as json
      if (is_object($response) || is_array($response)) {
          $response = $this->parseTranslationData($response);
      } else {
          // parse translation result as binary or xml
          $tmpFile = tmpfile();
          fwrite($tmpFile, $response);
          $metaData = stream_get_meta_data($tmpFile);

          $zip = new ZipArchive();
          // try loading as ZIP file. if not, we will assume the translation content is XML.
          // for our API to return ZIP, the API client must be configured not to return single files.
          if ($zip->open($metaData['uri']) !== true) {
              $response = $this->parseTranslationData($response);
          } else {
              // As long as statIndex() does not return false keep iterating
              for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
                  $fileName = basename($zipFile['name']);
                  $extension = pathinfo($fileName, PATHINFO_EXTENSION);

                  if (!is_dir($zipFile['name'])
                      && in_array($extension, array('xml', 'json'))) {
                      // file contents
                      $contents = $zip->getFromIndex($idx);

                      if ($contents) {
                          $response = $this->parseTranslationData($contents);
                          if ($response) {
                              $job->addMessage('The translation for file ' . $fileName . 'has been received.');
                              $job->addTranslatedData($response);
                          }
                      }
                  }
              }
              $zip->close();

              return TRUE;
          }
      }

      // json or xml translation content will flow here. ZIP will return above.
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
   * @param array|string $data JSON array
   * @param string $type Content type: json, xml
   *
   * @return array            Unflatted data.
   */
  protected function parseTranslationData($data, $type = 'json') {
    /** @var \Drupal\tmgmt\Data $data_service */
    $data_service = \Drupal::service('tmgmt.data');

    if ($type === 'json') {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
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

    return $data_service->unflatten($data);
  }

  /**
   * Returns MW account details for the authenticated user.
   *
   * @return array|object
   */
  public function getAccountDetails() {
    try {
      return $this->getApi()->getAccount();
    } catch (TMGMTException $e) {
      return array();
    }
  }
}

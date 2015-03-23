<?php
/**
 * Drupal Plugin - API
 *
 * PHP version 5.3
 *
 * @category Plugins
 * @package  Drupal
 * @author   Oytun Tez <oytun@motaword.com>
 */

/**
 * Class TMGMTMotaWordAPI
 */
class TMGMTMotaWordAPI {

  /**
   * Base API URL
   */
  const PRODUCTION_URL = 'https://api.motaword.com';

  /**
   * Base API URL for sandbox mode
   */
  const SANDBOX_URL = 'http://api.box';

  /**
   * Current API version
   *
   * @var string
   */
  const API_VERSION = 'v0';

  private $useSandbox = FALSE;
  private $clientId;
  private $clientSecret;
  /**
   * @var Array
   */
  private $supportedLanguages = array(
    'sq' => 'sq-al',
    'ar' => 'ar-sa',
    'bn' => 'bn-bd',
    'bs' => 'bs-ba',
    'bg' => 'bg-bg',
    'ca' => 'ca-es',
    // '' => 'zh-cn-yue',
    'zn-hans' => 'zh-cn-cmn-s',
    'zn-hant' => 'zh-cn-cmn-t',
    'hr' => 'hr-hr',
    'cs' => 'cs-cz',
    'da' => 'da-dk',
    'fa' => 'fa-af',
    'nl' => 'nl-nl',
    'en-gb' => 'en-uk',
    'en' => 'en-us',
    'et' => 'et-ee',
    // '' => 'fa-ir',
    'fi' => 'fi-fi',
    'fr' => 'fr-fr',
    // '' => 'fr-ca',
    'ka' => 'ka-ge',
    'de' => 'de-de',
    'el' => 'el-gr',
    'he' => 'he-il',
    'hi' => 'hi-in',
    'hu' => 'hu-hu',
    'id' => 'id-id',
    'it' => 'it-it',
    'jp' => 'jp-jp',
    'kk' => 'kk-kz',
    // '' => 'km-kh',
    'ko' => 'ko-kp',
    'lo' => 'lo-la',
    'lv' => 'lv-lv',
    'lt' => 'lt-lt',
    'mk' => 'mk-mk',
    'ms' => 'ms-my',
    'nb' => 'no-no',
    'ps' => 'ps',
    'pl' => 'pl-pl',
    'pt-br' => 'pt-br',
    'pt-pt' => 'pt-pt',
    'ro' => 'ro-ro',
    'ru' => 'ru-ru',
    'sr' => 'sr-rs',
    'sk' => 'sk-sk',
    'sl' => 'sl-si',
    'es' => 'es-es',
    // '' => 'es-ar',
    'sv' => 'sv-se',
    'tl' => 'tl-ph',
    'ta' => 'ta-in',
    'th' => 'th-th',
    'tr' => 'tr-tr',
    'uk' => 'uk-ua',
    'ur' => 'ur',
    'uz' => 'uz-uz',
    'vi' => 'vi-vn',
  );

  /**
   * Construct the API
   *
   * @param TMGMTTranslator $translator Translator which has the connection
   *                                    settings.
   */
  function __construct(TMGMTTranslator $translator) {
    $this->useSandbox = $translator->getSetting('use_sandbox');
    $this->clientId = $translator->getSetting('api_client_id');
    $this->clientSecret = $translator->getSetting('api_client_secret');
  }

  /**
   * Gets a list of supported languages from the API
   *
   * @return object
   */
  public function getLanguages() {
    return $this->get('languages');
  }

  /**
   * Returns array of supported languages by MW service.
   *
   * @return array
   *   Returns array of the supported languages.
   *   The key is drupal-style language code,
   *   the value - MW-style language code.
   */
  public function getSupportedLanguages() {
    return $this->supportedLanguages;
  }

  /**
   * Gets project details.
   *
   * @param int $projectId MotaWord project ID
   *
   * @return object
   */
  public function getProject($projectId) {
    return $this->get('projects/' . $projectId);
  }

  /**
   * Gets project progress.
   *
   * @param int $projectId MotaWord project ID
   *
   * @return object
   */
  public function getProgress($projectId) {
    return $this->get('projects/' . $projectId . '/progress');
  }

  /**
   * Submits project (does not launch it yet)
   *
   * @param array $data
   *
   * @return object
   */
  public function submitProject(array $data) {
    return $this->post('projects', $data, TRUE);
  }

  /**
   * Submits project (does not launch it yet)
   *
   * @param integer $projectId
   *
   * @return object
   */
  public function downloadProject($projectId) {
    $response = $this->post(
      'projects/' . $projectId . '/package',
      array('async' => FALSE)
    );

    return $response;
  }

  public function launchProject($projectId, $data = array()) {
    return $this->post('projects/' . $projectId . '/launch', $data, TRUE);
  }

  /**
   * HTTP GET
   *
   * @param string $path Relative API resource path
   * @param array  $data Query parameters
   *
   * @return object
   */
  protected function get($path, $data = array()) {
    return $this->request($path, 'GET', $data);
  }

  /**
   * HTTP POST
   *
   * @param string  $path   Relative API resource path
   * @param array   $data   Post form data
   * @param boolean $upload Is this a file upload?
   *
   * @return object
   */
  protected function post($path, $data = array(), $upload = FALSE) {
    return $this->request($path, 'POST', $data, $upload);
  }

  /**
   * HTTP PUT
   *
   * @param string $path Relative API resource path
   * @param array  $data Put body parameters
   *
   * @return object
   */
  protected function put($path, $data = array()) {
    return $this->request($path, 'PUT', $data);
  }

  /**
   * HTTP request base
   *
   * @param string  $path   Relative API resource path
   * @param string  $method HTTP method: GET, POST, PUT, DELETE
   * @param array   $data   Request parameters
   * @param boolean $upload Is this a file upload?
   *
   * @return object
   * @throws TMGMTException
   */
  protected function request($path, $method, $data = array(), $upload = FALSE) {
    $data = $this->prepareData($data);

    if (!isset($data['detailed'])) {
      $data['detailed'] = TRUE;
    }

    $options = array(
      'headers' => array(
        'User-Agent' => $this->getUserAgent(),
        'Accept' => 'application/json'
      ),
      'timeout' => 99999999,
      'method' => $method,
    );

    if ($this->useSandbox) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/' . $path;
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/' . $path;
    }

    if ($method == 'GET' || $method == 'DELETE') {
      $query = array_merge(
        array('access_token' => $this->getAccessToken()),
        $data
      );

      $url = url($url, array('query' => $query, 'absolute' => TRUE));
      $response = drupal_http_request($url, $options);
    }
    else {
      if ($upload === TRUE) {
        $boundary = uniqid();
        $options['headers']['Content-Type'] = "multipart/form-data; boundary=$boundary";
        $options['data'] = $this->multipart_encode($boundary, $data);
      }
      else {
        $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['data'] = http_build_query($data);
      }

      $url = url(
        $url,
        array(
          'query' => array('access_token' => $this->getAccessToken()),
          'absolute' => TRUE
        )
      );

      $response = drupal_http_request($url, $options);
    }

    if ($response->code != 200 && $response->code != 201) {
      throw new TMGMTException(
        'There was an error with your MotaWord request: @error',
        array('@error' => $this->flattenError($response), '@url' => $url)
      );
    }

    $response = json_decode($response->data);

    // Find if we have only one error or multiple.
    if (isset($response->error) || isset($response->errors)) {
      throw new TMGMTException(
        t('MotaWord returned an error: @error'),
        array('@error' => $this->flattenError($response))
      );
    }

    return $response;
  }

  function prepareData($data) {
    $result = array();

    if (is_array($data)) {
      foreach ($data as $key => $datum) {
        if (is_array($datum)) {
          foreach ($datum as $i => $datuman) {
            $result[$key . '[' . '' . ']'] = $datuman;
          }
        }
        else {
          $result[$key] = $datum;
        }
      }
    }
    else {
      return $data;
    }

    return $result;
  }

  function multipart_encode($boundary, $params) {
    $output = "";
    foreach ($params as $key => $value) {
      $output .= "--$boundary\r\n";
      if (substr($value, 0, 1) === '@') {
        $output .= $this->multipart_enc_file($key, $value);
      }
      else {
        $output .= $this->multipart_enc_text($key, $value);
      }
    }

    $output .= "--$boundary\r\n";

    return $output;
  }

  function multipart_enc_text($name, $value) {
    return "Content-Disposition: form-data; name=\"$name\"\r\n\r\n$value\r\n";
  }

  function multipart_enc_file($key, $path) {
    if (substr($path, 0, 1) == "@") {
      $path = substr($path, 1);
    }

    if (strpos($path, ';filename=') === FALSE) {
      $fileName = basename($path);
    }
    else {
      $path = explode(';filename=', $path);
      $fileName = $path[1];
      $path = $path[0];
    }

    $mimetype = "application/octet-stream";
    $data = "Content-Disposition: form-data; name=\"" . $key . "\"; filename=\"$fileName\"\r\n";
    $data .= "Content-Transfer-Encoding: binary\r\n";
    $data .= "Content-Type: $mimetype\r\n\r\n";
    $data .= file_get_contents($path) . "\r\n";
    return $data;
  }

  protected function getAccessToken($forceNew = FALSE) {
    if ($forceNew === FALSE && isset($_SESSION['mw_access_token']) && isset($_SESSION['mw_access_token_expiration']) && time(
      ) < (int) $_SESSION['mw_access_token_expiration']
    ) {
      return $_SESSION['mw_access_token'];
    }

    $options = array(
      'headers' => array(
        'User-Agent' => $this->getUserAgent(),
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode(
            $this->clientId . ':' . $this->clientSecret
          ),
        'Content-Type' => 'application/x-www-form-urlencoded'
      ),
      'data' => http_build_query(array('grant_type' => 'client_credentials')),
      'method' => 'POST'
    );

    if ($this->useSandbox) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/token';
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/token';
    }

    $url = url(
      $url,
      array(
        'query' => array('grant_type' => 'client_credentials'),
        'absolute' => TRUE
      )
    );

    $response = drupal_http_request($url, $options);

    if (isset($response->error) || $response->code != 200) {
      throw new TMGMTException(
        'Can\'t connect to MotaWord due to following error: @error at "@url"',
        array('@error' => $this->flattenError($response), '@url' => $url)
      );
    }


    $response = json_decode($response->data);

    if (isset($response->access_token)) {
      $_SESSION['mw_access_token'] = $response->access_token;
      $_SESSION['mw_access_token_expiration'] = time() + $response->expires_in;

      return $response->access_token;
    }
    else {
      throw new TMGMTException(
        'There was a problem retrieving your access token: @error',
        array('@error' => $this->flattenError($response))
      );
    }
  }

  protected function flattenError(stdClass $response) {
    $result = NULL;

    $response = json_decode($response->data);

    if (!$response) {
      if (is_object($response) && property_exists($response, 'error')) {
        return $response->error;
      }
      else {
        return FALSE;
      }
    }

    if (isset($response->error) || isset($response->errors)) {
      if (isset($response->errors)) {
        $error = $response->errors[0];
      }
      else {
        $error = $response->error;
      }

      $errMsg = NULL;
      $errCode = NULL;

      if (isset($error->code)) {
        $errCode = $error->code;
      }

      if (isset($error->message) && ((isset($error->code) && $error->code !== $error->message) || !isset($error->code))) {
        $errMsg = $error->message;
      }

      $result = $errCode . ': ' . $errMsg;
    }

    return $result;
  }


  /**
   * Builds user agent info.
   *
   * @return string
   */
  protected function getUserAgent() {
    global $base_url;

    $info = system_get_info('module', 'tmgmt');
    $tmgmt_version = !empty($info['version']) ? $info['version']
      : $info['core'] . '-1.x-dev';

    $info = system_get_info('module', 'tmgmt_mw');
    $mw_version = !empty($info['version']) ? $info['version']
      : $info['core'] . '-1.x-dev';

    return 'Drupal TMGMT/' . $tmgmt_version . '; MotaWord/' . $mw_version . '; ' . $base_url;
  }
}

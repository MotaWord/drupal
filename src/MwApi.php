<?php
/**
 * Drupal Plugin - API
 *
 * PHP version 5.6
 *
 * @category Plugins
 * @package  Drupal
 * @author   MotaWord Engineering <it@motaword.com>
 */

namespace Drupal\tmgmt_mw;

use Drupal\Core\Url;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MwApi
 */
class MwApi {

  /**
   * Base API URL
   */
  const PRODUCTION_URL = 'https://api.motaword.com';

  /**
   * Base API URL for sandbox mode
   */
  const SANDBOX_URL = 'https://sandbox.motaword.com';

  /**
   * Current API version
   *
   * @var string
   */
  const API_VERSION = 'v0';

  /**
   * @var bool
   */
  private $useSandbox = FALSE;
  /**
   * @var string
   */
  private $clientId;
  /**
   * @var string
   */
  private $clientSecret;
  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $http;

    /**
     * Construct the API
     *
     * @param TranslatorInterface         $translator Translator which has the connection
     *                                                settings.
     * @param \GuzzleHttp\ClientInterface $httpClient
     */
  function __construct(TranslatorInterface $translator, ClientInterface $httpClient = null) {
    $this->useSandbox = $translator->getSetting('use_sandbox');
    $this->clientId = $translator->getSetting('api_client_id');
    $this->clientSecret = $translator->getSetting('api_client_secret');

    \Drupal::logger('tmgmt_mw')->error($this->clientId);
    \Drupal::logger('tmgmt_mw')->error($this->clientSecret);

    $this->http = $httpClient;
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
   * Gets MW account details for the authenticated user.
   *
   * @return object
   */
  public function getAccount() {
    return $this->get('me');
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
      'projects/' . $projectId . '/package?async=0',
      array(), FALSE, array('Accept' => '*/*')
    );

    return $response;
  }

  /**
   * Launch a project
   *
   * @param integer $projectId
   * @param array   $data
   *
   * @return object
   */
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
  protected function post($path, $data = array(), $upload = FALSE, $headers = array()) {
    return $this->request($path, 'POST', $data, $upload, $headers);
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
     * @param string $path Relative API resource path
     * @param string $method HTTP method: GET, POST, PUT, DELETE
     * @param array $data Request parameters
     * @param boolean $upload Is this a file upload?
     *
     * @param array $headers        headers to override
     * @return object|string      May return binary string for translated package response
     * @throws TMGMTException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
  protected function request($path, $method, $data = array(), $upload = FALSE, $headers = array()) {
    $data = $this->prepareData($data);

    if (!isset($data['detailed'])) {
      $data['detailed'] = TRUE;
    }

    $options = array(
      'headers' => array_merge(array(
        'User-Agent' => $this->getUserAgent(),
        'Accept' => 'application/json'
      ), $headers),
      'timeout' => 99999999
    );

    if ($this->useSandbox) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/' . $path;
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/' . $path;
    }

    $query = array('access_token' => $this->getAccessToken());

    $url = Url::fromUri($url, array('query' => $query))->setAbsolute()->toString();

    if($method !== 'GET' && $method !== 'DELETE') {
      if ($upload === TRUE) {
        $boundary = uniqid();
        $options['headers']['Content-Type'] = "multipart/form-data; boundary=$boundary";
        $options['body'] = $this->multipart_encode($boundary, $data);
      } else {
        $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
        $options['body'] = http_build_query($data);
      }
    }

    $response = $this->http->request($method, $url, $options);
    //$response = \GuzzleHttp\json_decode($response->getBody());

    if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 201) {
      throw new TMGMTException(
        'There was an error with your MotaWord request: @error',
        array('@error' => $this->flattenError($response), '@url' => $url)
      );
    }

    $responseString = $response->getBody()->getContents();
    $result = json_decode($responseString);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $responseString;
    }

    // Find if we have only one error or multiple.
    if (isset($result->error) || isset($result->errors)) {
      throw new TMGMTException(
        t('MotaWord returned an error: @error'),
        array('@error' => $this->flattenError($result))
      );
    }

    return $result;
  }

  /**
   * Prepare data to be sent via an HTTP request.
   *
   * @param array $data
   *
   * @return array
   */
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

  /**
   * Encode post body.
   *
   * @param $boundary
   * @param $params
   *
   * @return string
   */
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

  /**
   * Regular form body.
   *
   * @param string $name   Form input name
   * @param string $value  Form input value
   *
   * @return string
   */
  function multipart_enc_text($name, $value) {
    return "Content-Disposition: form-data; name=\"$name\"\r\n\r\n$value\r\n";
  }

  /**
   * File upload form body.
   *
   * @param string $key    Form input name
   * @param string $path   File path
   *
   * @return string
   */
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

    $mimeType = "application/octet-stream";
    $data = "Content-Disposition: form-data; name=\"" . $key . "\"; filename=\"$fileName\"\r\n";
    $data .= "Content-Transfer-Encoding: binary\r\n";
    $data .= "Content-Type: $mimeType\r\n\r\n";
    $data .= file_get_contents($path) . "\r\n";
    return $data;
  }

  /**
   * Get a new access token. Once we retrieve one, we store it in the session for a while ('expires' result)
   * to prevent unnecessary calls.
   *
   * @param bool $forceNew    When true, we'll retrieve a new access token even though the previous one was not
   *                          expired yet.
   *
   * @return string
   * @throws TMGMTException
   */
  protected function getAccessToken($forceNew = FALSE) {
    if ($forceNew === FALSE
      && isset($_SESSION['mw_access_token'])
      && isset($_SESSION['mw_access_token_expiration'])
      && isset($_SESSION['mw_client_id'])
      && $_SESSION['mw_client_id'] === $this->clientId
      && time() < (int) $_SESSION['mw_access_token_expiration']
    ) {
      \Drupal::logger('tmgmt_mw')->debug(t('Using existing access token.'));
      return $_SESSION['mw_access_token'];
    }

    $options = array(
      'headers' => array(
        'User-Agent' => $this->getUserAgent(),
        'Accept' => 'application/json',
      ),
      'json' => array('grant_type' => 'client_credentials'),
      'auth' => [$this->clientId, $this->clientSecret]
    );

    if ($this->useSandbox) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/token';
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/token';
    }

    $url = Url::fromUri($url, array('query' => array('grant_type' => 'client_credentials')))->setAbsolute()->toString();
    $response = $this->http->request('POST', $url, $options);

    if ($response->getStatusCode() !== 200) {
      \Drupal::logger('tmgmt_mw')->error($this->flattenError($response));

      return null;
    }

    $response = json_decode($response->getBody());

    if (isset($response->error) || !isset($response->access_token)) {
      \Drupal::logger('tmgmt_mw')->error($this->flattenError($response));

      return null;
    }

    $_SESSION['mw_client_id'] = $this->clientId;
    $_SESSION['mw_access_token'] = $response->access_token;
    $_SESSION['mw_access_token_expiration'] = time() + $response->expires_in;

    return $response->access_token;
  }

  /**
   * Convert error object returned from MW API response to string.
   *
   * @param ResponseInterface $response
   *
   * @return bool|null|string
   */
  protected function flattenError(ResponseInterface $response) {
    $result = NULL;

    $response = json_decode($response->getBody());

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

    $info = \Drupal::service('extension.list.module')->getExtensionInfo('tmgmt');
    $tmgmt_version = !empty($info['version']) ? $info['version']
      : (isset($info['core']) ? $info['core'] : '') . '-1.x-dev';

    $info = \Drupal::service('extension.list.module')->getExtensionInfo('tmgmt_mw');
    $mw_version = !empty($info['version']) ? $info['version']
      : (isset($info['core']) ? $info['core'] : '') . '-1.x-dev';

    return 'Drupal TMGMT/' . $tmgmt_version . '; MotaWord/' . $mw_version . '; ' . $base_url;
  }
}

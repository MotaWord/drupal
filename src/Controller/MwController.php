<?php
/**
 * @file
 * Contains \Drupal\tmgmt_mw\Controller\MwController.
 */

namespace Drupal\tmgmt_mw\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_mw\Plugin\tmgmt\Translator\MwTranslator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route controller class for the tmgmt_mw module.
 */
class MwController extends ControllerBase {

  /**
   * Callback for MW requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \InvalidArgumentException
   */
  function callback(Request $request) {
    if (!$request->get('type') || !$request->get('action') || !$request->get('project')) {
      \Drupal::logger('tmgmt_mw')->error('Invalid parameters when receiving remote callback.');
      throw new MissingDataException();
    }

    $type = $request->get('type');
    $action = $request->get('action');
    $project = (object) $request->get('project');

    if (!property_exists($project, 'custom')) {
      \Drupal::logger('tmgmt_mw')->error('Invalid parameters from remote callback to update the job status: Custom data missing.');
      throw new \InvalidArgumentException();
    }

    $custom = (object) $project->custom;

    if (!property_exists($custom, 'job_id')) {
      \Drupal::logger('tmgmt_mw')->error('Invalid parameters from remote callback to update the job status: Job ID missing.');
      throw new \InvalidArgumentException();
    }

    $job = Job::load($custom->job_id);
    if (!$job) {
      \Drupal::logger('tmgmt_mw')->error('Could not find job #%job_id', array('%job_id' => $custom->job_id));
      throw new \InvalidArgumentException(sprintf('Could not find job #%s', $custom->job_id));
    }

    /** @var MwTranslator $mw */
    $mw = $job->getTranslator()->getPlugin();

    switch ($type) {
      case 'project':
        switch ($action) {
          case 'translated':
            \Drupal::logger('tmgmt_mw')->info('Your project has been translated. Waiting to be proofread and finalized.');

            $job->addMessage(
              'Your project has been translated. Waiting to be proofread.'
            );

            break;
          case 'proofread':
            \Drupal::logger('tmgmt_mw')->info('Your project has been proofread. Waiting for finalization to retrieve the translations.');

            $job->addMessage(
              'Your project has been proofread. Waiting for finalization to retrieve the translations.'
            );

            break;
          case 'completed':
            \Drupal::logger('tmgmt_mw')->info('Your project is now complete. Retrieving the translations.');
            $job->addMessage('Your project is now complete. Retrieving the translations.');

            $mw->retrieveTranslation($job);
            $job->setState(JobInterface::STATE_FINISHED);

            break;
        }

        break;

      default:
        \Drupal::logger('tmgmt_mw')->error('Callback actions did not match. Quitting.');
    }

    return new Response();
  }

}

<?php

namespace Drupal\rekognition_api\Plugin\rest\resource;

use Drupal\Core\Entity\Entity;
use Drupal\media_entity\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Rekognition API Endpoint Resource
 *
 * @RestResource(
 *   id = "rekognition_api_resource",
 *   label = @Translation("Rekognition API Resource"),
 *   uri_paths = {
 *     "canonical" = "/rekognition_api/objects",
 *     "https://www.drupal.org/link-relations/create" = "/rekognition_api/objects/create"
 *   }
 * )
 */
class RekognitionAPIResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    $response = ['message' => 'Hello, this is a rest service'];
    return new ResourceResponse($response);
  }

  /**
   * Responds to entity POST requests.
   *
   * @param string $body
   *   The body of the POST request.
   *
   * @return ResourceResponse
   */
  public function post($body) {
    $jsonBody = json_encode($body);

    // Log the body of the request.
    // \Drupal::logger('rekognition_api')->notice("POST body is:\n{$jsonBody}");

    // Find the image.
    $uri = "s3://{$body['Name']}";

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);

    // Throw an exception if this file doesn't exist.
    if (empty($files)) {
      throw new \Exception("File not found in database: $uri");
    }

    $file = reset($files);
    $fid = $file->fid->value;

    $query = \Drupal::entityQuery('media');
    $query->condition('bundle', 'image')
      ->condition('field_image', $fid);
    $result = $query->execute();

    // Load the Media entity for this image.
    $media_id = reset($result);
    $media = Media::load($media_id);

    // Store labels (for recognized objects).
    if (!empty($body['Labels'])) {
      $tids = $this->findOrCreateTerms($body['Labels']);
      $jsonTids = json_encode($body);

      $media->set('field_label', $tids);
      $media->save();
    }

    // Store faces (and new names).
    if (!empty($body['Faces'])) {
      $faceNodeIds = [];
      foreach ($body['Faces'] as $faceInfo) {
        if (empty($faceInfo['Face']['FaceId'])) {
          throw new \Exception("FaceId is null");
        }
        $faceId = $faceInfo['Face']['FaceId'];

        // If there are similar faces, use FaceMatches ID(s).
        if (!empty($faceInfo['FaceMatches'])) {
          $similarFaceIds = $this->extractSimilarFaceIds($faceInfo['FaceMatches']);
          $nameNodeId = $this->findOrCreateName($similarFaceIds);
          $faceNodeIds[] = $this->createFace($faceId, $nameNodeId);
        }
        // If this is a new face, create a name and face for it.
        else {
          $nameNodeId = $this->findOrCreateName([$faceId]);
          $faceNodeIds[] = $this->createFace($faceId, $nameNodeId);
        }
      }
      $jsonFaces = json_encode($faceNodeIds);

      $media->set('field_face', $faceNodeIds);
      $media->save();
    }

    $jsonMedia = json_encode($media);
    $response = ['media' => $media];
    return new ResourceResponse($response);
  }

  /**
   * Create a new Face node.
   *
   * @param string $faceId
   *   Face UUID.
   * @param int $nameNodeId
   *   Name node ID.
   *
   * @return int
   *   Node ID of the new Face node.
   */
  private function createFace($faceId, $nameNodeId) {
    $node = Node::create([
      'type' => 'face_uuid',
      'title' => $faceId,
      'field_name' => [
        'target_id' => $nameNodeId,
      ],
    ]);
    $node->save();
    return $node->id();
  }

  /**
   * Extract similar face IDs from a set of matches.
   *
   * @param array $faceMatches
   *   TODO: Description.
   *
   * @return array
   *   TODO: Description.
   */
  private function extractSimilarFaceIds($faceMatches) {
    return array_map(function ($match) {
      return $match['Face']['FaceId'];
    }, $faceMatches);
  }

  /**
   * Find a Name node, or create one if it doesn't exist.
   *
   * @param array $similarFaceIds
   *   TODO: Description.
   *
   * @return int
   *   Node ID of found or created Name node.
   */
  private function findOrCreateName($similarFaceIds) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'face_uuid')
      ->condition('title', $similarFaceIds, 'IN');

    $result = $query->execute();
    if (empty($result)) {
      $rand = rand();
      $node = Node::create([
        'type'        => 'name',
        'title'       => "Unknown Person $rand",
      ]);
      $node->save();
      return $node->id();
    }
    $faceNodeId = reset($result);
    $faceNode = Node::load($faceNodeId);

    return $faceNode->field_name[0]->target_id;
  }

  private function findOrCreateTerms($labels) {
    $tids = [];
    foreach ($labels as $label) {
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', "labels");
      $query->condition('name', $label['Name']);
      $labelTids = $query->execute();
      if (!empty($labelTids)) {
        $tids = $tids + $labelTids;
        continue;
      }

      $term = \Drupal\taxonomy\Entity\Term::create([
        'vid' => 'labels',
        'name' => $label['Name'],
        'weight' => 0,
        'parent' => [],
      ]);
      $term->save();
      $tids[] = $term->id();
    }
    return $tids;
  }
}

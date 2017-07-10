# Rekognition API

This module interacts with the [AWS Rekognition](https://aws.amazon.com/rekognition/) service to identify objects and faces in photos. The module requires integration with an active [Amazon Web Services](https://aws.amazon.com) (AWS) account, and also requires some initial setup in order to use with a Drupal site using the [Media Entity](https://www.drupal.org/project/media_entity) and [Media Entity Image](https://www.drupal.org/project/media_entity_image) modules.

## Requirements

This module assumes you have configured a few entity types:

  - Taxonomy 'Name' (`name`)
  - Taxonomy 'Face' (`face`)
    - Field 'Name' (`field_name`) - entity reference to Name taxonomy.
  - Taxonomy 'Label' (`label`)
  - Media Image 'Image' (`image`)
    - Field 'Image' (`field_image`) that stores images in S3 (via the [S3 File System](https://www.drupal.org/project/s3fs) module).
    - Field 'Label' (`field_label`) - entity reference to Label taxonomy.
    - Field 'Face' (`field_face`) - entity reference to Face taxonomy.

Also, you must be using the [S3 File System](https://www.drupal.org/project/s3fs) module to store all Media Image images on Amazon S3 in a specific bucket.

## AWS S3 Setup

TODO.

## AWS Lambda Setup

TODO.

## Authors

TODO.

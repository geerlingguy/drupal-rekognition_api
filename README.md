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

## AWS Setup - S3, Lambda, and Rekognition

This module includes an AWS CloudFormation stack template (inside the `lambda` folder) which allows you to quickly and automatically set up all the required resources and permissions to integrate S3, Lambda, and Rekognition so they work with your Drupal site.

See the detailed guide that's part of the [Drupal Photo Gallery](https://github.com/geerlingguy/drupal-photo-gallery) documentation for more details on how to use the AWS CLI to deploy this CloudFormation stack inside your own account.

## Authors

This project was initially created during Acquia's 2017 Build Week Hackathon by:

  - [Matthew Wagner](https://ma.ttwagner.com)
  - [Glenn Pratt](https://about.me/glennpratt)
  - [ASH Heath](http://www.burnashburn.com)
  - [Jeff Geerling](https://www.jeffgeerling.com)
  - [Rok Zlender](https://twitter.com/Zlender)
  - [Meagan White](https://twitter.com/MeaganWhite_)

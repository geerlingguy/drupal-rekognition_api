# Testing the Rekognition API module

The `test-data.json` file contains an example payload of JSON as it would be delivered by the AWS Lambda function, after an image has been processed by Rekognition.

To test that the Rekognition API is working correctly on your site, run the following `curl` command in your Terminal, from within this directory:

    curl -vX POST http://gallery.example.com/rekognition_api/objects/create?_format=json \
      --user "admin:admin" \
      --data-binary @test-data.json \
      --header "Content-Type: application/json"

// Lambda function (for the nodejs6.10 runtime) which does the following:
//
//   - Receives an event call when an image is stored in an S3 bucket.
//   - Detects Labels in said image via Rekognition.
//     - If any are found, send PUT with data to Drupal site (DRUPAL_URL).
//   - Detects Faces in said image via Rekognition.
//     - If any are found, send PUT with data to Drupal site (DRUPAL_URL).

'use strict';

const aws = require('aws-sdk');
const http = require('http');
const drupal_url = process.env.DRUPAL_URL;
const object_endpoint = "/rekognition_api/objects?_format=json";
const drupal_auth = "Basic " + new Buffer(process.env.USER + ":" + process.env.PASS).toString("base64");
const s3 = new aws.S3({ apiVersion: '2006-03-01' });
var rekognition = new aws.Rekognition({apiVersion: '2016-06-27'});

exports.handler = (event, context, callback) => {
    var body='';

    // Get the object from the event and detect Labels on it
    const bucket = event.Records[0].s3.bucket.name;
    const key = decodeURIComponent(event.Records[0].s3.object.key.replace(/\+/g, ' '));
    const params = {
        Image: {
            S3Object: {
                Bucket: bucket,
                Name: key,
            },
        },
        MaxLabels: 30,
        MinConfidence: 30,
    };

    // Detect Labels.
    rekognition.detectLabels(params, function(err, data) {
        if (err) console.log(err, err.stack); // an error occurred.
        else {
            // PUT options.
            var options_put = {
                host: drupal_url,
                path: object_endpoint,
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": drupal_auth,
                }
            };

            var reqPost = http.request(options_put, function(res) {
                console.log("statusCode: ", res.statusCode);
                res.on('data', function (chunk) {
                    body += chunk;
                });
            });

            var jsonObject = {
                Bucket: bucket,
                Name: key,
                Labels: data.Labels
            }

            console.log(jsonObject);
            reqPost.write(JSON.stringify(jsonObject));
            reqPost.end();
        }
    });
    delete params.MinConfidence
    delete params.MaxLabels

    // Detect Faces.
    params["CollectionId"] = process.env.COLLECTION
    var response_faces = [];
    rekognition.indexFaces(params, function(err, data) {
        if (err) console.log(err, err.stack); // an error occurred
        else {
            data.FaceRecords.forEach(function(face) {
                var search_params = {
                    CollectionId: process.env.COLLECTION,
                    FaceId: face.Face.FaceId,
                    FaceMatchThreshold: 90,
                    MaxFaces: 10
                };
                rekognition.searchFaces(search_params, function(err2, data2) {
                    if (err) console.log(err2, err2.stack); // an error occurred
                    else {
                        console.log(data2)
                        console.log(data2.FaceMatches.length)
                        if (data2.FaceMatches.length > 0) {
                            face.FaceMatches = data2.FaceMatches;
                            console.log(face)
                        }
                        response_faces.push(face);
                        console.log(JSON.stringify(response_faces));
                        // the PUT options
                        var options_put = {
                            host: drupal_url,
                            path: object_endpoint,
                            method: "PUT",
                            headers: {
                                "Content-Type": "application/json",
                                "Authorization": drupal_auth,
                            }
                        };

                        var reqPost = http.request(options_put, function(res) {
                            console.log("statusCode: ", res.statusCode);
                            res.on('data', function (chunk) {
                                body += chunk;
                            });
                        });

                        var jsonFacesObject = {
                            Bucket: bucket,
                            Name: key,
                            Faces: response_faces
                        }

                        console.log(jsonFacesObject);
                        reqPost.write(JSON.stringify(jsonFacesObject));
                        reqPost.end();
                    }
                });
            });
        }
    });
};

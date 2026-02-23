<?php

namespace App\Http\Controllers;

use App\Http\Requests\Beamdrop\BucketExistsRequest;
use App\Http\Requests\Beamdrop\CreateBucketRequest;
use App\Http\Requests\Beamdrop\DeleteBucketRequest;
use App\Http\Requests\Beamdrop\DeleteObjectRequest;
use App\Http\Requests\Beamdrop\DownloadObjectRequest;
use App\Http\Requests\Beamdrop\ListObjectsRequest;
use App\Http\Requests\Beamdrop\ObjectExistsRequest;
use App\Http\Requests\Beamdrop\ObjectMetadataRequest;
use App\Http\Requests\Beamdrop\PresignedUrlRequest;
use App\Http\Requests\Beamdrop\UploadObjectRequest;
use App\Services\Beamdrop;
use App\Services\BeamdropException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BeamdropController extends Controller
{
    public function __construct(private Beamdrop $beamdrop)
    {
    }

    public function index(ListObjectsRequest $request): View
    {
        $data = $request->validated();
        $bucket = $data['bucket'] ?? null;
        $prefix = $data['prefix'] ?? null;
        $delimiter = $data['delimiter'] ?? null;
        $maxKeys = (int) ($data['max_keys'] ?? 1000);
        $objectKey = $data['object_key'] ?? null;

        $bucketsResponse = $this->beamdrop->listBuckets();
        $buckets = $bucketsResponse['buckets'] ?? [];

        $objects = null;
        $bucketExists = null;
        $objectExists = null;
        $objectMetadata = null;
        $presignedUrl = null;

        if ($bucket !== null && $bucket !== '') {
            $bucketExists = $this->beamdrop->bucketExists($bucket);
            $objects = $this->beamdrop->listObjects($bucket, $prefix, $delimiter, $maxKeys);

            if ($objectKey !== null && $objectKey !== '') {
                $objectExists = $this->beamdrop->objectExists($bucket, $objectKey);

                if ($objectExists) {
                    $objectMetadata = $this->beamdrop->headObject($bucket, $objectKey);
                    $presignedUrl = $this->beamdrop->presignedUrl($bucket, $objectKey, 3600);
                }
            }
        }

        return view('welcome', [
            'buckets' => $buckets,
            'selectedBucket' => $bucket,
            'selectedPrefix' => $prefix,
            'selectedDelimiter' => $delimiter,
            'selectedMaxKeys' => $maxKeys,
            'selectedObjectKey' => $objectKey,
            'objects' => $objects,
            'bucketExists' => $bucketExists,
            'objectExists' => $objectExists,
            'objectMetadata' => $objectMetadata,
            'presignedUrl' => $presignedUrl,
        ]);
    }

    public function createBucket(CreateBucketRequest $request): RedirectResponse
    {
        $bucket = $request->validated('bucket');
        $this->beamdrop->createBucket($bucket);

        return to_route('beamdrop.index', ['bucket' => $bucket])->with('success', "Bucket {$bucket} created.");
    }

    public function deleteBucket(DeleteBucketRequest $request): RedirectResponse
    {
        $bucket = $request->validated('bucket');
        $this->beamdrop->deleteBucket($bucket);

        return to_route('beamdrop.index')->with('success', "Bucket {$bucket} deleted.");
    }

    public function uploadObject(UploadObjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        if ($file === null) {
            return to_route('beamdrop.index')->with('error', 'No file was uploaded.');
        }

        $this->beamdrop->putObject(
            $validated['bucket'],
            $validated['key'],
            file_get_contents($file->getRealPath()) ?: '',
        );

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('success', 'Object uploaded successfully.');
    }

    public function downloadObject(DownloadObjectRequest $request): Response
    {
        $validated = $request->validated();
        $object = $this->beamdrop->getObject($validated['bucket'], $validated['key']);

        return response($object['body'])
            ->header('Content-Type', $object['content_type'])
            ->header('Content-Length', (string) $object['content_length'])
            ->header('ETag', $object['etag'])
            ->header('Last-Modified', $object['last_modified']);
    }

    public function deleteObject(DeleteObjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->beamdrop->deleteObject($validated['bucket'], $validated['key']);

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
        ])->with('success', 'Object deleted successfully.');
    }

    public function bucketExists(BucketExistsRequest $request): RedirectResponse
    {
        $bucket = $request->validated('bucket');
        $exists = $this->beamdrop->bucketExists($bucket);

        return to_route('beamdrop.index', [
            'bucket' => $bucket,
        ])->with('success', $exists ? "Bucket {$bucket} exists." : "Bucket {$bucket} does not exist.");
    }

    public function objectExists(ObjectExistsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $exists = $this->beamdrop->objectExists($validated['bucket'], $validated['key']);

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('success', $exists ? 'Object exists.' : 'Object does not exist.');
    }

    public function metadata(ObjectMetadataRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $metadata = $this->beamdrop->headObject($validated['bucket'], $validated['key']);

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('metadata', $metadata);
    }

    public function presignedUrl(PresignedUrlRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $url = $this->beamdrop->presignedUrl(
            $validated['bucket'],
            $validated['key'],
            (int) $validated['expires_in'],
            $validated['method'],
        );

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('presigned_url', $url);
    }

    public function withBeamdropErrors(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (BeamdropException $exception) {
            return to_route('beamdrop.index')->with('error', $exception->getMessage());
        }
    }
}

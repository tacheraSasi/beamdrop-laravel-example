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

        $buckets = [];

        $objects = null;
        $bucketExists = null;
        $objectExists = null;
        $objectMetadata = null;
        $presignedUrl = null;

        try {
            $bucketsResponse = $this->beamdrop->listBuckets();
            $buckets = $bucketsResponse['buckets'] ?? [];

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
        } catch (\Throwable $throwable) {
            session()->flash('error', $throwable->getMessage());
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

        try {
            $this->beamdrop->createBucket($bucket);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index')->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', ['bucket' => $bucket])->with('success', "Bucket {$bucket} created.");
    }

    public function deleteBucket(DeleteBucketRequest $request): RedirectResponse
    {
        $bucket = $request->validated('bucket');

        try {
            $this->beamdrop->deleteBucket($bucket);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index', ['bucket' => $bucket])->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index')->with('success', "Bucket {$bucket} deleted.");
    }

    public function uploadObject(UploadObjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        if ($file === null) {
            return to_route('beamdrop.index')->with('error', 'No file was uploaded.');
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            return to_route('beamdrop.index')->with('error', 'Could not read the uploaded file.');
        }

        try {
            $this->beamdrop->putObject(
                $validated['bucket'],
                $validated['key'],
                $contents,
            );
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index', ['bucket' => $validated['bucket']])->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('success', 'Object uploaded successfully.');
    }

    public function downloadObject(DownloadObjectRequest $request): Response
    {
        $validated = $request->validated();

        try {
            $object = $this->beamdrop->getObject($validated['bucket'], $validated['key']);
        } catch (\Throwable $throwable) {
            abort(422, $throwable->getMessage());
        }

        return response($object['body'])
            ->header('Content-Type', $object['content_type'])
            ->header('Content-Length', (string) $object['content_length'])
            ->header('ETag', $object['etag'])
            ->header('Last-Modified', $object['last_modified']);
    }

    public function deleteObject(DeleteObjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->beamdrop->deleteObject($validated['bucket'], $validated['key']);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index', [
                'bucket' => $validated['bucket'],
                'object_key' => $validated['key'],
            ])->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
        ])->with('success', 'Object deleted successfully.');
    }

    public function bucketExists(BucketExistsRequest $request): RedirectResponse
    {
        $bucket = $request->validated('bucket');

        try {
            $exists = $this->beamdrop->bucketExists($bucket);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index')->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $bucket,
        ])->with('success', $exists ? "Bucket {$bucket} exists." : "Bucket {$bucket} does not exist.");
    }

    public function objectExists(ObjectExistsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $exists = $this->beamdrop->objectExists($validated['bucket'], $validated['key']);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index')->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('success', $exists ? 'Object exists.' : 'Object does not exist.');
    }

    public function metadata(ObjectMetadataRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $metadata = $this->beamdrop->headObject($validated['bucket'], $validated['key']);
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index')->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('metadata', $metadata);
    }

    public function presignedUrl(PresignedUrlRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $url = $this->beamdrop->presignedUrl(
                $validated['bucket'],
                $validated['key'],
                (int) $validated['expires_in'],
                $validated['method'],
            );
        } catch (\Throwable $throwable) {
            return to_route('beamdrop.index')->with('error', $throwable->getMessage());
        }

        return to_route('beamdrop.index', [
            'bucket' => $validated['bucket'],
            'object_key' => $validated['key'],
        ])->with('presigned_url', $url);
    }

}

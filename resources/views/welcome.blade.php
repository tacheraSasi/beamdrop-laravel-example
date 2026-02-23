<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Beamdrop Manager</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-gray-100 text-gray-900">
        <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold">Beamdrop Full Integration</h1>
                <p class="mt-2 text-sm text-gray-600">Upload files, manage buckets and objects, inspect metadata, and generate presigned URLs.</p>

                @if (session('success'))
                    <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Bucket Actions</h2>

                    <form method="POST" action="{{ route('beamdrop.buckets.create') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-sm font-medium">Create Bucket</label>
                        <div class="flex gap-2">
                            <input type="text" name="bucket" value="{{ old('bucket', $selectedBucket) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="example-bucket" required>
                            <button type="submit" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Create</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('beamdrop.buckets.exists') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-sm font-medium">Check Bucket Exists</label>
                        <div class="flex gap-2">
                            <input type="text" name="bucket" value="{{ old('bucket', $selectedBucket) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="example-bucket" required>
                            <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium">Check</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('beamdrop.buckets.delete') }}" class="mt-4 space-y-3">
                        @csrf
                        @method('DELETE')
                        <label class="block text-sm font-medium">Delete Bucket (must be empty)</label>
                        <div class="flex gap-2">
                            <input type="text" name="bucket" value="{{ old('bucket', $selectedBucket) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="example-bucket" required>
                            <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700">Delete</button>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Upload Object</h2>

                    <form method="POST" action="{{ route('beamdrop.objects.upload') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium">Bucket</label>
                            <input type="text" name="bucket" value="{{ old('bucket', $selectedBucket) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Object Key</label>
                            <input type="text" name="key" value="{{ old('key', $selectedObjectKey) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="folder/file.pdf" required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">File</label>
                            <input type="file" name="file" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>

                        <button type="submit" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Upload</button>
                    </form>
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold">List Buckets</h2>
                <p class="mt-1 text-sm text-gray-600">Total: {{ count($buckets) }}</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Created</th>
                                <th class="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($buckets as $bucket)
                                <tr>
                                    <td class="px-3 py-2 font-medium">{{ $bucket['name'] }}</td>
                                    <td class="px-3 py-2">{{ $bucket['createdAt'] ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('beamdrop.index', ['bucket' => $bucket['name']]) }}" class="text-blue-700 underline">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-gray-500">No buckets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">List Objects</h2>
                    <form method="GET" action="{{ route('beamdrop.index') }}" class="mt-4 grid gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Bucket</label>
                            <input type="text" name="bucket" value="{{ $selectedBucket }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Prefix (optional)</label>
                            <input type="text" name="prefix" value="{{ $selectedPrefix }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Delimiter (optional)</label>
                            <input type="text" name="delimiter" value="{{ $selectedDelimiter }}" maxlength="1" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="/">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Max Keys</label>
                            <input type="number" name="max_keys" min="1" max="1000" value="{{ $selectedMaxKeys }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Object Key (optional for details)</label>
                            <input type="text" name="object_key" value="{{ $selectedObjectKey }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                        </div>

                        <button type="submit" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Load Objects</button>
                    </form>

                    @if (!is_null($bucketExists))
                        <p class="mt-4 text-sm">Bucket exists: <span class="font-semibold">{{ $bucketExists ? 'Yes' : 'No' }}</span></p>
                    @endif
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Object Tools</h2>

                    <form method="POST" action="{{ route('beamdrop.objects.exists') }}" class="mt-4 grid gap-3">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium">Bucket</label>
                            <input type="text" name="bucket" value="{{ $selectedBucket }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Object Key</label>
                            <input type="text" name="key" value="{{ $selectedObjectKey }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium">Check Exists</button>
                    </form>

                    <form method="POST" action="{{ route('beamdrop.objects.metadata') }}" class="mt-4 grid gap-3">
                        @csrf
                        <input type="hidden" name="bucket" value="{{ $selectedBucket }}">
                        <input type="hidden" name="key" value="{{ $selectedObjectKey }}">
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium">Fetch Metadata (HEAD)</button>
                    </form>

                    <form method="POST" action="{{ route('beamdrop.presigned-url') }}" class="mt-4 grid gap-3">
                        @csrf
                        <input type="hidden" name="bucket" value="{{ $selectedBucket }}">
                        <input type="hidden" name="key" value="{{ $selectedObjectKey }}">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Expires In (seconds)</label>
                            <input type="number" name="expires_in" min="1" max="604800" value="3600" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
                        </div>
                        <input type="hidden" name="method" value="GET">
                        <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium">Generate Presigned URL</button>
                    </form>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($selectedBucket && $selectedObjectKey)
                            <a href="{{ route('beamdrop.objects.download', ['bucket' => $selectedBucket, 'key' => $selectedObjectKey]) }}" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white">Download Object (GET)</a>
                        @endif

                        <form method="POST" action="{{ route('beamdrop.objects.delete') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="bucket" value="{{ $selectedBucket }}">
                            <input type="hidden" name="key" value="{{ $selectedObjectKey }}">
                            <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700">Delete Object</button>
                        </form>
                    </div>

                    @if (!is_null($objectExists))
                        <p class="mt-4 text-sm">Object exists: <span class="font-semibold">{{ $objectExists ? 'Yes' : 'No' }}</span></p>
                    @endif
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Object Metadata</h2>
                    @php
                        $metadata = session('metadata') ?? $objectMetadata;
                    @endphp

                    @if ($metadata)
                        <dl class="mt-4 grid grid-cols-1 gap-2 text-sm">
                            <div><dt class="font-medium">Content Type</dt><dd>{{ $metadata['content_type'] ?? '-' }}</dd></div>
                            <div><dt class="font-medium">Content Length</dt><dd>{{ $metadata['content_length'] ?? '-' }}</dd></div>
                            <div><dt class="font-medium">ETag</dt><dd>{{ $metadata['etag'] ?? '-' }}</dd></div>
                            <div><dt class="font-medium">Last Modified</dt><dd>{{ $metadata['last_modified'] ?? '-' }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No metadata loaded yet.</p>
                    @endif
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold">Presigned URL</h2>
                    @php
                        $signedUrl = session('presigned_url') ?? $presignedUrl;
                    @endphp

                    @if ($signedUrl)
                        <p class="mt-4 break-all text-sm">{{ $signedUrl }}</p>
                        <a href="{{ $signedUrl }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm text-blue-700 underline">Open Presigned URL</a>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No URL generated yet.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold">Objects Result</h2>

                @if (is_array($objects))
                    <p class="mt-2 text-sm text-gray-600">Truncated: {{ !empty($objects['isTruncated']) ? 'Yes' : 'No' }}</p>

                    @if (!empty($objects['commonPrefixes']))
                        <div class="mt-3">
                            <h3 class="text-sm font-medium">Common Prefixes</h3>
                            <ul class="mt-2 list-disc pl-5 text-sm">
                                @foreach ($objects['commonPrefixes'] as $prefix)
                                    <li>{{ is_array($prefix) ? ($prefix['prefix'] ?? json_encode($prefix)) : $prefix }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th class="px-3 py-2">Key</th>
                                    <th class="px-3 py-2">Size</th>
                                    <th class="px-3 py-2">ETag</th>
                                    <th class="px-3 py-2">Last Modified</th>
                                    <th class="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse(($objects['contents'] ?? []) as $item)
                                    <tr>
                                        <td class="px-3 py-2">{{ $item['key'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item['size'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item['etag'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item['lastModified'] ?? '-' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($selectedBucket && !empty($item['key']))
                                                <a href="{{ route('beamdrop.index', ['bucket' => $selectedBucket, 'object_key' => $item['key']]) }}" class="text-blue-700 underline">Inspect</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-gray-500">No objects found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500">Load a bucket to view objects.</p>
                @endif
            </section>
        </main>
    </body>
</html>

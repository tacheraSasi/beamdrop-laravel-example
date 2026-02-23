<?php

use App\Http\Controllers\BeamdropController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BeamdropController::class, 'index'])->name('beamdrop.index');
Route::post('/beamdrop/buckets', [BeamdropController::class, 'createBucket'])->name('beamdrop.buckets.create');
Route::delete('/beamdrop/buckets', [BeamdropController::class, 'deleteBucket'])->name('beamdrop.buckets.delete');
Route::post('/beamdrop/objects', [BeamdropController::class, 'uploadObject'])->name('beamdrop.objects.upload');
Route::get('/beamdrop/objects/download', [BeamdropController::class, 'downloadObject'])->name('beamdrop.objects.download');
Route::delete('/beamdrop/objects', [BeamdropController::class, 'deleteObject'])->name('beamdrop.objects.delete');
Route::post('/beamdrop/buckets/exists', [BeamdropController::class, 'bucketExists'])->name('beamdrop.buckets.exists');
Route::post('/beamdrop/objects/exists', [BeamdropController::class, 'objectExists'])->name('beamdrop.objects.exists');
Route::post('/beamdrop/objects/metadata', [BeamdropController::class, 'metadata'])->name('beamdrop.objects.metadata');
Route::post('/beamdrop/presigned-url', [BeamdropController::class, 'presignedUrl'])->name('beamdrop.presigned-url');

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | This file contains the credentials needed to authenticate with Firebase.
    |
    */
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | The Firebase project ID from your Firebase console.
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | The Firebase Realtime Database URL (optional).
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | The Firebase Storage bucket name (optional).
    |
    */
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
];

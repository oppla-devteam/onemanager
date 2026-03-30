<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public contract signature page
Route::get('/contracts/sign/{token}', function ($token) {
    return view('contracts.signature-page');
})->name('contracts.sign');

// Success and declined pages
Route::get('/contracts/signed-success', function () {
    return view('contracts.signed-success');
})->name('contracts.signed-success');

Route::get('/contracts/declined', function () {
    return view('contracts.declined');
})->name('contracts.declined');

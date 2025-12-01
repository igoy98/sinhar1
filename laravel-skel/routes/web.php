<?php
use Illuminate\Support\Facades\Route;
Route::get('/cancellation', 'RequirementController@cancellation');
Route::get('/transfer', 'RequirementController@transfer');

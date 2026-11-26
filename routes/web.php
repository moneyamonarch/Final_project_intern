<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SavedController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/questionnaire/start', [QuestionnaireController::class, 'start'])->name('questionnaire.start');
Route::get('/questionnaire/{step?}', [QuestionnaireController::class, 'show'])->whereNumber('step')->name('questionnaire.show');
Route::post('/questionnaire/{step}', [QuestionnaireController::class, 'store'])->whereNumber('step')->middleware('throttle:60,1')->name('questionnaire.store');
Route::post('/demo/{persona}', [DemoController::class, 'start'])->where('persona', '[a-z_]+')->middleware('throttle:60,1')->name('demo.start');
Route::get('/portfolios', [PortfolioController::class, 'index'])->name('portfolios.index');
Route::get('/portfolios/{portfolio}', [PortfolioController::class, 'show'])->name('portfolios.show');
Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
Route::get('/recommendations/export', [RecommendationController::class, 'export'])->middleware('throttle:30,1')->name('recommendations.export');
Route::get('/compare', [CompareController::class, 'show'])->name('compare');
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:60,1')->name('feedback.store');
Route::get('/go/{run}/{portfolio}', [FeedbackController::class, 'go'])->whereNumber(['run', 'portfolio'])->middleware('throttle:60,1')->name('go');
Route::get('/r/{token}', [ShareController::class, 'show'])->where('token', '[A-Za-z0-9]{16,40}')->middleware('throttle:60,1')->name('share');
Route::get('/admin', [AdminController::class, 'index'])->middleware('admin')->name('admin');
Route::get('/method', [MethodController::class, 'show'])->name('method');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/saved', [SavedController::class, 'index'])->name('saved.index');
Route::post('/saved', [SavedController::class, 'store'])->middleware('throttle:60,1')->name('saved.store');
Route::delete('/saved', [SavedController::class, 'destroy'])->middleware('throttle:60,1')->name('saved.destroy');

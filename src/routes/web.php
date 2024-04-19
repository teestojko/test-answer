<?php

// コンタクトコントローラ使いますよー
use App\Http\Controllers\ContactController;

use Illuminate\Support\Facades\Route;
// 一番最初のインデックス画面を表示
Route::get('/', [ContactController::class, 'index']);

// 入力情報を一緒に送ってるからポストで、コンファーム画面を表示
Route::post('/confirm', [ContactController::class, 'confirm']);

// storeメソッドで登録情報を保存して、thanks画面を表示
Route::post('/thanks', [ContactController::class, 'store']);

// 認証('auth')ミドルウェアを適用したルートグループ 認証されたユーザーのみがアクセスできるルートをグループ化している
Route::middleware('auth')->group(function () {



    
    Route::get('/search', [ContactController::class, 'search']);
    Route::post('/delete', [ContactController::class, 'destroy']);
    Route::post('/export', [ContactController::class, 'export']);
});

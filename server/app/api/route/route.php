<?php


use think\facade\Route;

Route::rule('v1/chat/completions', 'chat/wxchat');
Route::rule('v1/chat/commonChat', 'kb.chat/chat');
Route::post('/wechatUpload', 'upload/wechatUpload');
Route::post('station/deviceAuthCode/sync', 'station.DeviceAuthCode/sync');

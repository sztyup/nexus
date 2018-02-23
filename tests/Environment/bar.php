<?php

$this->get('/', function (\Illuminate\Http\Request $request) {
    return response('lol');
})->name('index');

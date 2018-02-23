<?php

$this->get('/', function (\Illuminate\Http\Request $request) {
    return response('bar');
})->name('index');


$this->get('foo/lol', function (\Illuminate\Http\Request $request) {
    return response('lol');
})->name('lol');

$this->get('foo/asd', function (\Illuminate\Http\Request $request) {
    return response('asd');
})->name('asd');

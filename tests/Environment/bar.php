<?php

$this->get('/', function () {
    return response('lol');
})->name('index');

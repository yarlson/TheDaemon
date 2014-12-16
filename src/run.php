<?php
require __DIR__ . '/TheDaemon.php';

$theDaemon = new \Yarlson\TheDaemon\TheDaemon(
    [
        'testChild1' => function () {
            echo 'sample text';
        },
        'testChild2' => function () {
            echo 'another sample text';
        }
    ]
);

$theDaemon->init();

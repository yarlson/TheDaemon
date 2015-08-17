<?php
require __DIR__ . '/Yarlson/TheDaemon/TheDaemon.php';

$theDaemon = new \Yarlson\TheDaemon\TheDaemon(
    [
        'testChild1' =>
            [
                function () {
                    echo 'sample text', "\n";
                    sleep(5);
                },
                5
            ],
        'testChild2' => function () {
            echo 'another sample text', "\n";
            sleep(1);
        }
    ]
);

$theDaemon->init();

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

$signalHeader = function ($signo) use ($theDaemon) {
    $theDaemon->signalHandler($signo);
};

pcntl_signal(SIGTERM, $signalHeader);

$theDaemon->init();

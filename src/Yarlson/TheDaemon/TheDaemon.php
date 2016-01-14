<?php
namespace Yarlson\TheDaemon;

declare(ticks = 1);

class TheDaemon
{
    const DELAY = 1000;

    private $stopServer = false;

    private $childProcesses = [];

    private $childProcessesMax = [];

    private $maxChild = 0;

    private $processName = 'TheDaemon';

    private $callbacks;

    private $group;

    private $user;

    private $demonized;

    public function __construct($callbacks = array(), $demonized = true)
    {
        $this->demonized = $demonized;
        foreach ($callbacks as $key => $value) {
            if (is_array($value)) {
                $this->callbacks[$key] = $value[0];
                $this->childProcessesMax[$key] = (int)$value[1];
            } else {
                $this->callbacks[$key] = $value;
                $this->childProcessesMax[$key] = 1;
            }
            $this->childProcesses[$key] = [];
            $this->maxChild += $this->childProcessesMax[$key];
        }
    }

    public function init()
    {
        $this->setGroupAndUser();

        if ($this->isDaemonActive()) {
            echo 'The Daemon is running already!' . "\n";
            exit;
        }

        $this->startDaemon();

        while (!$this->stopServer) {
            if (count($this->childProcesses, COUNT_RECURSIVE) - count($this->childProcesses) < $this->maxChild) {
                $this->startChildProcess();
            }

            usleep(self::DELAY);

            while ($signaled_pid = pcntl_waitpid(-1, $status, WNOHANG)) {
                if ($signaled_pid == -1) {
                    $this->childProcesses = [];
                    break;
                } else {
                    foreach ($this->childProcesses as $key => $childPids) {
                        if (is_array($childPids)) {
                            foreach ($childPids as $keyPid => $value) {
                                if ($value == $signaled_pid) {
                                    unset($this->childProcesses[$key][$keyPid]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function isDaemonActive()
    {
        $pid_file = $this->getPidFile();

        if (is_file($pid_file)) {
            $pid = file_get_contents($pid_file);
            if (posix_kill($pid, 0) && $this->demonized) {
                return true;
            } else {
                if (!unlink($pid_file)) {
                    exit(-1);
                }
            }
        }

        return false;
    }

    private function getNotRunningCallback()
    {
        foreach ($this->callbacks as $callback => $value) {
            if (!isset($this->childProcesses[$callback]) || count($this->childProcesses[$callback]) < $this->childProcessesMax[$callback]) {
                return $callback;
            }
        }
    }

    private function getPidFile()
    {
        return '/var/run/php_' . $this->processName . '.pid';
    }

    private function startDaemon()
    {
        if ($this->demonized) {
            if ($pid = pcntl_fork()) {
                exit;
            }
        }

        if (function_exists('cli_set_process_title')) {
            \cli_set_process_title('php-' . $this->processName . ': master process');
        }

        posix_setsid();

        file_put_contents($this->getPidFile(), getmypid());
    }

    private function startChildProcess()
    {
        $pid = pcntl_fork();

        $callback = $this->getNotRunningCallback();

        if ($pid == -1) {
        } elseif ($pid) {
            $this->childProcesses[$callback][] = $pid;;
        } else {
            posix_setuid($this->user);
            posix_setgid($this->group);

            $this->childProcesses = [];

            if (function_exists('cli_set_process_title')) {
                \cli_set_process_title('php-' . $this->processName . ': ' . $callback);
            }

            $this->callbacks[$callback]();
            usleep(2 * self::DELAY);
            exit;
        }
    }

    public function setGroupAndUser()
    {
        $this->group = posix_getgrnam('www-data')['gid'];
        $this->user = posix_getpwnam('www-data')['uid'];
    }

    public function killChildProcesses()
    {
        foreach ($this->childProcesses as $childProcesses) {
            foreach ($childProcesses as $pid) {
                exec("kill -15 $pid");
            }
        }
    }

    public function signalHandler($signo)
    {
        $this->stopServer = true;
        $this->killChildProcesses();
    }
}
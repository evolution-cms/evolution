<?php
namespace EvolutionCMS;

use Illuminate\View\ViewException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\LostConnectionException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\ErrorHandler\Error\FatalError as FatalErrorException;
use EvolutionCMS\Providers\TracyServiceProvider;

/**
 * @see: https://github.com/laravel/framework/blob/5.6/src/Illuminate/Foundation/Bootstrap/HandleExceptions.php
 */
class ExceptionHandler
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Create a new exception handler instance.
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->container->register(TracyServiceProvider::class);
        if (!$this->container['config']->get('tracy.active')) {
            $this->registerHandlers();
        }
    }

    /**
     * @var bool
     */
    protected $registered = false;

    protected function registerHandlers(): void
    {
        register_shutdown_function([$this, 'handleShutdown']);
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'phpError']);
        $this->registered = true;
    }

    public function restoreHandlers(): void
    {
        if ($this->registered) {
            restore_exception_handler();
            restore_error_handler();
            $this->registered = false;
        }
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     * @deprecated
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * @param array $error
     * @param int|null $traceOffset
     * @return FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null): FatalErrorException
    {
        return new FatalErrorException(
            $error['message'],
            $error['type'],
            $error
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     * @return bool
     */
    protected function isFatal($type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * @param int $nr The PHP error level as per http://www.php.net/manual/en/errorfunc.constants.php
     * @param string $text Error message
     * @param string $file File where the error was detected
     * @param string $line Line number within $file
     * @return void
     * @deprecated
     * PHP error handler set by http://www.php.net/manual/en/function.set-error-handler.php
     *
     * Checks the PHP error and calls messageQuit() unless:
     *  - error_reporting() returns 0, or
     *  - the PHP error level is 0, or
     *  - the PHP error level is 8 (E_NOTICE) and stopOnNotice is false
     *
     */
    public function phpError($nr, $text, $file, $line)
    {
        if (error_reporting() == 0 || $nr == 0 || error_reporting() !== E_ALL & ~E_DEPRECATED & ~E_NOTICE) {
            return true;
        }
        if ($this->container->stopOnNotice == false) {
            switch ($nr) {
                case E_USER_DEPRECATED:
                    if ($this->container->error_reporting <= 99) {
                        return true;
                    }
                    $isError = false;
                    $msg = 'PHP User deprecated (this message show logged in only)';
                    break;
                case E_NOTICE:
                    if ($this->container->error_reporting <= 2) {
                        return true;
                    }
                    $isError = false;
                    $msg = 'PHP Minor Problem (this message show logged in only)';
                    break;
                case E_DEPRECATED:
                    if ($this->container->error_reporting <= 1) {
                        return true;
                    }
                    $isError = true;
                    $msg = 'PHP Strict Standards Problem';
                    break;
                default:
                    if ($this->container->error_reporting === 0) {
                        return true;
                    }
                    $isError = true;
                    $msg = 'PHP Parse Error';
            }
        }
        if (is_readable($file)) {
            $source = file($file);
            $source = $this->container->getPhpCompat()->htmlspecialchars($source[$line - 1]);
        } else {
            $source = "";
        } //Error $nr in $file at $line: <div><code>$source</code></div>

        $this->messageQuit($msg, '', $isError, $nr, $file, $source, $text, $line);
    }

    /**
     * @param string $msg
     * @param string $query
     * @param bool $is_error
     * @param string $nr
     * @param string $file
     * @param string $source
     * @param string $text
     * @param string $line
     * @param string $output
     * @return void|bool
     */
    public function messageQuit(
        $msg = 'unspecified error',
        $query = '',
        $is_error = true,
        $nr = '',
        $file = '',
        $source = '',
        $text = '',
        $line = '',
        $output = '',
        $backtrace = []
    ) {
        if (0 < $this->container->messageQuitCount) {
            return;
        }
        $this->container->messageQuitCount++;
        $MakeTable = $this->container->getService('makeTable');
        $MakeTable->setTableClass('grid');
        $MakeTable->setRowRegularClass('gridItem');
        $MakeTable->setRowAlternateClass('gridAltItem');
        $MakeTable->setColumnWidths(['100px']);

        $table = [];

        if (isset($_SERVER['HTTP_HOST'])) {
            $request_uri = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . $_SERVER['HTTP_HOST'];
            if (!in_array((int) $_SERVER['SERVER_PORT'], [80, (int) HTTPS_PORT]) && !str_contains($_SERVER['HTTP_HOST'], ':')) {
                $request_uri .= ':' . $_SERVER['SERVER_PORT'];
            }
            $request_uri .= $_SERVER['REQUEST_URI'];
            $request_uri = $this->container->getPhpCompat()->htmlspecialchars(
                $request_uri,
                ENT_QUOTES,
                $this->container->getConfig('evo_charset')
            );
        } else {
            $request_uri = '';
        }
        $ua = $this->container->getPhpCompat()->htmlspecialchars(
            $_SERVER['HTTP_USER_AGENT'],
            ENT_QUOTES,
            $this->container->getConfig('evo_charset')
        );
        $referer = $this->container->getPhpCompat()->htmlspecialchars(
            $_SERVER['HTTP_REFERER'],
            ENT_QUOTES,
            $this->container->getConfig('evo_charset')
        );
        if ($is_error) {
            $str = '<h2 style="color:red">&laquo; Evolution CMS Parse Error &raquo;</h2>';
            if ($msg != 'PHP Parse Error') {
                $str .= '<h3 style="color:red">' . $msg . '</h3>';
            }
        } else {
            $str = '<h2 style="color:#003399">&laquo; Evolution CMS Debug/ stop message &raquo;</h2>';
            $str .= '<h3 style="color:#003399">' . $msg . '</h3>';
        }

        if (!empty($query)) {
            $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">SQL &gt; <span id="sqlHolder">' . $query . '</span></pre>';
        }

        $errortype = [
            E_ERROR => "ERROR",
            E_WARNING => "WARNING",
            E_PARSE => "PARSING ERROR",
            E_NOTICE => "NOTICE",
            E_CORE_ERROR => "CORE ERROR",
            E_CORE_WARNING => "CORE WARNING",
            E_COMPILE_ERROR => "COMPILE ERROR",
            E_COMPILE_WARNING => "COMPILE WARNING",
            E_USER_ERROR => "USER ERROR",
            E_USER_WARNING => "USER WARNING",
            E_USER_NOTICE => "USER NOTICE",
            E_RECOVERABLE_ERROR => "RECOVERABLE ERROR",
            E_DEPRECATED => "DEPRECATED",
            E_USER_DEPRECATED => "USER DEPRECATED"
        ];

        if (!empty($nr) || !empty($file)) {
            if ($text != '') {
                $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">Error : ' . $text . '</pre>';
            }
            if ($output != '') {
                $str .= '<pre style="font-weight:bold;border:1px solid #ccc;padding:8px;color:#333;background-color:#ffffcd;margin-bottom:15px;">' . $output . '</pre>';
            }
            if ($nr !== '') {
                $table[] = ['ErrorType[num]', $errortype[$nr] . "[" . $nr . "]"];
            }
            if ($file) {
                $table[] = ['File', $file];
            }
            if ($line) {
                $table[] = ['Line', $line];
            }

        }

        if ($source != '') {
            $table[] = ["Source", $source];
        }

        if (!empty($this->currentSnippet)) {
            $table[] = ['Current Snippet', $this->currentSnippet];
        }

        if (!empty($this->event->activePlugin)) {
            $table[] = ['Current Plugin', $this->event->activePlugin . '(' . $this->event->name . ')'];
        }

        $str .= $MakeTable->create($table, ['Error information', '']);
        $str .= "<br />";

        $table = [];
        $table[] = ['REQUEST_URI', $request_uri];

        if ($this->container->getManagerApi()->action) {
            $actionName = Legacy\LogHandler::getAction($this->container->getManagerApi()->action);
            if (!empty($actionName)) {
                $actionName = ' - ' . $actionName;
            }

            $table[] = ['Manager action', $this->container->getManagerApi()->action . $actionName];
        }

        if (preg_match('~^[1-9][0-9]*$~', $this->container->documentIdentifier)) {
            try {
                $resource = $this->container->getDocumentObject('id', $this->container->documentIdentifier);
                $url = $this->container->makeUrl($this->container->documentIdentifier, '', '', 'full');
            } catch (\Exception $e) {
                // DB or connection may be failed, but we still should proceed with original error processing
                $resource['pagetitle'] = '';
                $url = '#';
            }
            $table[] = [
                'Resource',
                '[' . $this->container->documentIdentifier . '] <a href="' . $url . '" target="_blank">' . $resource['pagetitle'] . '</a>'
            ];
        }
        $table[] = ['Referer', $referer];
        $table[] = ['User Agent', $ua];
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $table[] = ['IP', $_SERVER['REMOTE_ADDR']];
        }
        $table[] = [
            'Current time',
            date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME'] + $this->container->getConfig('server_offset_time'))
        ];
        $str .= $MakeTable->create($table, ['Basic info', '']);
        $str .= "<br />";

        $table = [];
        $table[] = ['MySQL', '[^qt^] ([^q^] Requests)'];
        $table[] = ['PHP', '[^p^]'];
        $table[] = ['Total', '[^t^]'];
        $table[] = ['Memory', '[^m^]'];
        $str .= $MakeTable->create($table, ['Benchmarks', '']);
        $str .= "<br />";

        $totalTime = ($this->container->getMicroTime() - $this->container->tstart);

        $mem = memory_get_peak_usage(true);
        $total_mem = $mem - $this->container->mstart;
        $total_mem = ($total_mem / 1024 / 1024) . ' mb';

        $queryTime = $this->container->queryTime;
        $phpTime = $totalTime - $queryTime;
        $queries = isset($this->container->executedQueries) ? $this->container->executedQueries : 0;
        $queryTime = sprintf("%2.4f s", $queryTime);
        $totalTime = sprintf("%2.4f s", $totalTime);
        $phpTime = sprintf("%2.4f s", $phpTime);

        $str = str_replace(
            ['[^q^]', '[^qt^]', '[^p^]', '[^t^]', '[^m^]']
            ,
            [$queries, $queryTime, $phpTime, $totalTime, $total_mem]
            ,
            $str
        );

        $php_errormsg = error_get_last();
        if (!empty($php_errormsg) && isset($php_errormsg['message'])) {
            $str = '<b>' . $php_errormsg['message'] . '</b><br />' . PHP_EOL . $str;
        }

        if (empty($backtrace)) {
            $backtrace = debug_backtrace();
        }
        $backtrace = $this->prepareBacktrace($backtrace);
        $str .= $this->renderBacktrace($backtrace);

        // Log error
        if (!empty($this->container->currentSnippet)) {
            $source = 'Snippet - ' . $this->container->currentSnippet;
        } elseif (!empty($this->container->event->activePlugin)) {
            $source = 'Plugin - ' . $this->container->event->activePlugin;
        } elseif ($source !== '') {
            $source = 'Parser - ' . $source;
        } elseif ($query !== '') {
            $source = 'SQL Query';
        } else {
            $source = 'Parser';
        }
        if ($msg) {
            $source .= ' / ' . $msg;
        }
        if (isset($actionName) && !empty($actionName)) {
            $source .= $actionName;
        }
        switch ($nr) {
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_NOTICE:
            case E_USER_NOTICE:
                $error_level = 2;
                break;
            default:
                $error_level = 3;
        }

        try {
            if ($this->container->getDatabase()->getConnection()->getDatabaseName()) {
                $this->container->logEvent(0, $error_level, $str, $source);
            }
        } catch (\Exception $e) {
            // DB or connection may be failed, but we still should proceed with original error processing
        }

        if ($error_level === 2 && $this->container->error_reporting < 99) {
            return true;
        }
        if ($this->container->error_reporting >= 99 && !isset($_SESSION['mgrValidated'])) {
            return true;
        }
        if (!headers_sent()) {
            // Set 500 response header
            if ($error_level !== 2) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            ob_get_clean();
        }

        // Display error
        if (is_cli()) {
            echo $msg, "\n\n";

            if (!empty($query)) {
                echo 'SQL: ', $query, "\n";
            }

            if (!empty($nr) || !empty($file)) {
                if ($text != '') {
                    echo 'Error: ', $text, "\n";
                }
                if ($output != '') {
                    echo $output, "\n";
                }
                if ($nr !== '') {
                    echo 'ErrorType[num]: ', $errortype[$nr] . "[$nr]", "\n";
                }
                if ($file) {
                    echo 'File: ', $file, "\n";
                }
                if ($line) {
                    echo 'Line: ', $line, "\n";
                }
            }

            if ($source != '') {
                echo 'Source: ', $source, "\n";
            }

            if (!empty($this->currentSnippet)) {
                echo 'Current Snippet: ', $this->currentSnippet, "\n";
            }

            if (!empty($this->event->activePlugin)) {
                echo 'Current Plugin: ', $this->event->activePlugin . '(' . $this->event->name . ')', "\n";
            }

            echo "\n", $this->renderConsoleBacktrace($backtrace);
        } else if ($this->shouldDisplay()) {
            $version = isset($GLOBALS['evo_version']) ? $GLOBALS['evo_version'] : '';
            $release_date = isset($GLOBALS['release_date']) ? $GLOBALS['release_date'] : '';

            echo '<!DOCTYPE html><html><head><title>Evolution CMS ' . $version . ' &raquo; ' . $release_date . '</title>
                 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                 <link rel="stylesheet" type="text/css" href="' . EVO_MANAGER_URL . 'media/style/' . $this->container->getConfig(
                        'manager_theme',
                        'default'
                    ) . '/style.css" />
                 <style type="text/css">body { padding:10px; } td {font:inherit;}</style>
                 </head><body>
                 ' . $str . '</body></html>';
        } else {
            if (file_exists(EVO_CORE_PATH . 'custom/error_page.html')) {
                echo file_get_contents(EVO_CORE_PATH . 'custom/error_page.html');
            } else
                echo 'Error';
        }
        if (!is_cli()) {
            ob_end_flush();
        }
        exit;
    }

    protected function shouldDisplay()
    {
        return isset($_SESSION['mgrValidated']) || $this->container['config']->get('app.debug');
    }

    protected function prepareBacktrace($backtrace)
    {
        $result = [];
        $backtrace = array_reverse($backtrace);

        foreach ($backtrace as $key => $val) {
            $key++;
            if (substr($val['function'], 0, 11) === 'messageQuit') {
                break;
            }

            if (substr($val['function'], 0, 8) === 'phpError') {
                break;
            }

            if (isset($val['file'])) {
                $path = str_replace('\\', '/', $val['file']);
                if (strpos($path, EVO_BASE_PATH) === 0) {
                    $path = substr($path, strlen(EVO_BASE_PATH));
                }
            } else {
                $path = '';
            }

            switch (get_by_key($val, 'type')) {
                case '->':
                case '::':
                    $functionName = $val['function'] = $val['class'] . $val['type'] . $val['function'];
                    break;
                default:
                    $functionName = $val['function'];
            }
            $tmp = 1;
            $_ = (!empty($val['args'])) ? count($val['args']) : 0;
            $args = array_pad([], $_, '$var');
            $args = implode(", ", $args);
            $modx = &$this;
            $args = preg_replace_callback('/\$var/', function () use ($modx, &$tmp, $val) {
                $arg = $val['args'][$tmp - 1];
                switch (true) {
                    case $arg === null: {
                        $out = 'NULL';
                        break;
                    }
                    case is_numeric($arg): {
                        $out = $arg;
                        break;
                    }
                    case is_scalar($arg): {
                        $out = strlen($arg) > 20 ? 'string $var' . $tmp : ("'" . $this->container->getPhpCompat()->htmlspecialchars(str_replace(
                            "'",
                            "\\'",
                            $arg
                        )) . "'");
                        break;
                    }
                    case is_bool($arg): {
                        $out = $arg ? 'TRUE' : 'FALSE';
                        break;
                    }
                    case is_array($arg): {
                        $out = 'array $var' . $tmp;
                        break;
                    }
                    case is_object($arg): {
                        $out = get_class($arg) . ' $var' . $tmp;
                        break;
                    }
                    default: {
                        $out = '$var' . $tmp;
                    }
                }
                $tmp++;

                return $out;
            }, $args);

            $result[] = [
                'func' => $functionName,
                'args' => $args,
                'path' => $path,
                'line' => $val['line'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * @param $backtrace
     * @return string
     */
    public function getBacktrace($backtrace)
    {
        return $this->renderBacktrace($this->prepareBacktrace($backtrace));
    }

    /**
     * @param $backtrace
     * @return string
     */
    public function renderBacktrace($backtrace)
    {
        $MakeTable = $this->container->getService('makeTable');
        $MakeTable->setTableClass('grid');
        $MakeTable->setRowRegularClass('gridItem');
        $MakeTable->setRowAlternateClass('gridAltItem');
        $table = [];

        foreach ($backtrace as $line) {
            $table[] = [
                implode("<br />", [
                    "<strong>" . $line['func'] . "</strong>(" . $line['args'] . ")",
                    $line['path'] . " on line " . $line['line'],
                ])
            ];
        }

        return $MakeTable->create($table, ['Backtrace']);
    }

    /**
     * @param $backtrace
     * @return string
     */
    public function renderConsoleBacktrace($backtrace)
    {
        $result = '';

        foreach ($backtrace as $i => $line) {
            $result .= '#' . ($i + 1) . '. ' . $line['func'] . '(' . $line['args'] . '), ' . $line['path'] . ' on line ' . $line['line'] . "\n";
        }

        return $result;
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param \Throwable $exception
     * @return bool
     */
    public function shouldReport(\Throwable $exception)
    {
        return true;
    }

    /**
     * @param \Throwable $exception
     * @deprecated
     */
    public function handleException(\Throwable $exception)
    {
        if (
            ($exception instanceof LostConnectionException) ||
            ($exception instanceof \PDOException && $exception->getCode() === 1045)
        ) {
            $this->container->getDatabase()->disconnect();
        }

        if (
            is_cli() && (
                $exception instanceof RuntimeException ||
                $exception instanceof InvalidArgumentException ||
                $exception instanceof InvalidOptionException ||
                $exception instanceof CommandNotFoundException
            )
        ) {
            echo $exception->getMessage();
            exit;
        }

        if ($exception instanceof ViewException) {
            $trace = $exception->getPrevious()->getTrace();
        } else {
            $trace = $exception->getTrace();
        }

        $this->messageQuit(
            $exception->getMessage(),
            '',
            true,
            '',
            $exception->getFile(),
            '',
            '',
            $exception->getLine(),
            '',
            $trace
        );
    }
}

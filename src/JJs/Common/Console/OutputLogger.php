<?php

namespace JJs\Common\Console;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use JJs\Common\String\LineBuffer;
use Exception;

/**
 * Console output logger
 * 
 * PSR-3 Compatible Logger which logs output to a Symfony console output
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class OutputLogger extends AbstractLogger
{
    /**
     * Output
     * 
     * @var OutputInterface
     */
    protected $output;

    /**
     * Output formatting
     * 
     * @var OutputFormatterInterface
     */
    protected $format;

    /**
     * Creates a new output logger
     * 
     * @param OutputInterface          $output Output
     * @param OutputFormatterInterface $format Formatting colors (optional)
     */
    public function __construct(OutputInterface $output, OutputFormatterInterface $format = null)
    {
        $this->output = $output;
        $this->format = $format ?: $this->getDefaultOutputFormatter();
    }

    /**
     * Returns the output where this logger will write information to
     * 
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Returns the format of the output
     * 
     * @return OutputFormatterInterface
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Gets the default output formatter for the log
     * 
     * @return return
     */
    public function getDefaultOutputFormatter()
    {
        return new OutputFormatter(true, [
            LogLevel::EMERGENCY => new OutputFormatterStyle('red', null, ['bold', 'blink']),
            LogLevel::ALERT     => new OutputFormatterStyle('yellow', null, ['bold', 'blink']),
            LogLevel::CRITICAL  => new OutputFormatterStyle('red', null, ['bold']),
            LogLevel::ERROR     => new OutputFormatterStyle('red'),
            LogLevel::WARNING   => new OutputFormatterStyle('yellow', null, ['bold']),
            LogLevel::NOTICE    => new OutputFormatterStyle('green'),
            LogLevel::INFO      => new OutputFormatterStyle('blue'),
            LogLevel::DEBUG     => new OutputFormatterStyle('white'),
        ]);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level   Level
     * @param string $message Message
     * @param array  $context Context
     */
    public function log($level, $message, array $context = array())
    {
        $output = $this->getOutput();

        // Based on the console output verbosity, hide some messages
        switch ($level) {
            case LogLevel::DEBUG:
                if ($output->getVerbosity() < OutputInterface::VERBOSITY_DEBUG) return;

            case LogLevel::INFO:
                if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERY_VERBOSE) return;

            case LogLevel::NOTICE:
                if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) return;

            case LogLevel::WARNING:
                if ($output->getVerbosity() < OutputInterface::VERBOSITY_NORMAL) return;
        }
        
        $outputFormat = $output->getFormatter();
        $output->setFormatter($this->getFormat());
        $output->setDecorated($outputFormat->isDecorated());

        // Generate a set of token from the context keys
        $placeholders = [];
        foreach ($context as $key => $value) {
            $placeholders['{'.$key.'}'] = $value;
        }

        // Output the message on lines prefixed by the formatter
        LineBuffer::lines(
            strtr($message, $placeholders),
            function ($line) use ($output, $level) {
                $output->writeLn(sprintf('<%1$s>%2$s</%1$s>', $level, $line));
            }
        );

        // Handle exceptions in the context
        $exception = @$context['exception'];
        if ($exception instanceof Exception) {
            LineBuffer::lines(
                $exception->getTraceAsString(),
                function ($line) use ($output, $level) {
                    $output->writeLn(sprintf('<%1$s>%2$s</%1$s>', $level, $line));
                }
            );
        }

        $output->setFormatter($outputFormat);
    }
}
<?php

namespace JJs\Common\String;

/**
 * Line Buffer
 *
 * Buffers text input and only executes the callback function once each line is
 * collected.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class LineBuffer
{
    /**
     * Line callback
     *
     * Called with the contents of each line
     * 
     * @var callable
     */
    protected $callback;

    /**
     * Buffer
     *
     * Holds text which has been input since the last new line.
     * 
     * @var string
     */
    protected $buffer;

    /**
     * Creates a new line buffer and assigns the callback
     * 
     * @param callable $callback 
     */
    public function __construct(callable $callback)
    {
        $this->buffer = "";
        $this->callback = $callback;
    }

    /**
     * Called for each line as its encountered
     *
     * Each line of text is passed to the callback as it is encountered.
     * 
     * @param string $line          Line of text
     * @param bool   $prependBuffer Indicates whether the buffer should be 
     *                              prepended to the line.
     * 
     * @return void
     */
    protected function line($line, $prependBuffer = false)
    {
        if ($prependBuffer) {
            $line = $this->buffer.$line;
            $this->buffer = "";
        }

        call_user_func($this->callback, $line);
    }

    /**
     * Inputs new text into the line buffer
     * 
     * @param string $text Text
     * @return void
     */
    public function input($text)
    {
        // Iterate over the text input
        while (false !== $n = strpos("\n", $text) && false !== $r = strpos("\r", $text)) {

            // Unix or Linux
            if (false !== $n && $n < $r) {
                $this->line(substr($text, 0, $n), true);
                $text = substr($text, $n+1);
            }

            // Windows
            if (false !== $r && false !== $n && $r === $n-1) {
                $this->line(substr($text, 0, $r), true);
                $text = substr($text, $n+1);
            }

            // Mac
            if (false !== $r && $r < $n) {
                $this->line(substr($text, 0, $r), true);
                $text = substr($text, $r+1);
            }
        }

        // Append the remaining text tothe buffer
        $this->buffer .= $text;
    }

    /**
     * Flushes the remaining buffer as a line
     * 
     * @return void
     */
    public function flush()
    {
        $this->line($this->buffer);
        $this->buffer = "";
    }

    /**
     * Calls the callback for each line in the text
     * 
     * @param string   $text     Text
     * @param callable $callback Callback
     */
    public static function lines($text, callable $callback)
    {
        $buffer = new LineBuffer($callback);
        $buffer->input($text);
        $buffer->flush();
    }
}
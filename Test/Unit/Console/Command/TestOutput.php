<?php
declare(strict_types=1);
/**
 */

namespace CommerceLeague\ActiveCampaign\Test\Unit\Console\Command;

use Symfony\Component\Console\Output\Output;

class TestOutput extends Output
{
    /**
     * @var string
     */
    public $output = '';

    /**
     * @return void
     */
    public function clear()
    {
        $this->output = '';
    }

    /**
     * @param string $message
     * @param bool $newline
     */
    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}

<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Console;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class CommandList has a list of commands, which can be extended via DI configuration.
 *
 * @package Magento\Framework\Console
 */
class CommandList
{
    /**
     * @var string[]
     */
    protected $commands;

    /**
     * Constructor
     *
     * @param array $commands
     */
    public function __construct(array $commands = [])
    {
        $this->commands = $commands;
    }

    /**
     * Gets list of command instances
     *
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }
}

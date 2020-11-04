<?php

namespace App\Commands;

use Illuminate\Console\Command;

class Example extends Command
{
    protected $name = 'example';

    public function handle(): int
    {
        $this->output->writeln('Hello World!!');

        return 0;
    }
}

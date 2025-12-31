<?php

namespace App\Console\Commands;

use App\Jobs\GenerateLLMFilesJob;
use Illuminate\Console\Command;

class DocsGenerateLLM extends Command
{
    protected $signature = 'docs:generate-llm';

    protected $description = 'Generate llms.txt and llms-full.txt files';

    public function handle(): int
    {
        $this->info('Generating LLM files...');

        try {
            GenerateLLMFilesJob::dispatchSync();

            $this->info('✓ LLM files generated successfully');
            $this->line('  - public/llms.txt');
            $this->line('  - public/llms-full.txt');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to generate LLM files: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

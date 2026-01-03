<?php

namespace Database\Seeders;

use App\Services\LocalDocsImporter;
use App\Services\MarkdownParser;
use App\Services\PageImporterService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DocumentationSeeder extends Seeder
{
    public function run(): void
    {
        $parser = new MarkdownParser;
        $pageImporterService = new PageImporterService;
        $importer = new LocalDocsImporter($parser, $pageImporterService);

        if (! $importer->hasDocumentation()) {
            Log::warning('No documentation found in docs directory. Skipping documentation seeding.');
            $this->command?->warn('No documentation found in docs directory. Skipping documentation seeding.');

            return;
        }

        $this->command?->info('Importing documentation from docs directory...');

        $stats = $importer->import();

        if (! empty($stats['errors'])) {
            foreach ($stats['errors'] as $error) {
                Log::error('Documentation import error: '.$error);
                $this->command?->error($error);
            }
        }

        $this->command?->info("Imported: {$stats['navigation']} navigation tabs, {$stats['groups']} groups, {$stats['documents']} documents");
    }
}

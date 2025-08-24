<?php

namespace App\Console\Commands;

use App\Models\DailyWeight;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportWeightData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:weight-data {directory} {user_id} {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import weight data from JSON files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = $this->argument('directory');
        $userId = $this->argument('user_id');
        $dryRun = $this->option('dry-run');

        if (!File::isDirectory($directory)) {
            $this->error("Directory {$directory} does not exist.");
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} does not exist.");
            return 1;
        }

        $this->info("Starting weight import from: {$directory}");
        $this->info("Target user: {$user->name} ({$user->email})");

        $files = File::glob($directory . '/*.json');
        $this->info('Found ' . count($files) . ' JSON files');

        $totalWeights = 0;
        $errors = [];

        foreach ($files as $file) {
            $this->info('Processing: ' . basename($file));

            try {
                $result = $this->processFile($file, $user, $dryRun);
                $totalWeights += $result['weights'];

                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to process file " . basename($file) . ": " . $e->getMessage();
            }
        }

        $this->info("\nImport Summary:");
        $this->info("- Total weight entries: {$totalWeights}");
        
        if (!empty($errors)) {
            $this->info("- Errors encountered: " . count($errors));
            foreach (array_slice($errors, 0, 10) as $error) { // Show first 10 errors
                $this->warn("  • {$error}");
            }
            if (count($errors) > 10) {
                $this->warn("  ... and " . (count($errors) - 10) . " more errors");
            }
        }

        if ($totalWeights > 0 || empty($errors)) {
            $this->info('Import completed successfully!');
            return 0;
        } else {
            $this->error('Import completed with errors.');
            return 1;
        }
    }

    private function processFile(string $file, User $user, bool $dryRun): array
    {
        $content = File::get($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }

        $weights = 0;
        $errors = [];

        // Check if this file has weight data
        if (!isset($data['weight']) || !$data['weight']) {
            return ['weights' => 0, 'errors' => []];
        }

        $date = $this->extractDateFromFile($file, $data);
        if (!$date) {
            $errors[] = "Could not extract date from file: " . basename($file);
            return ['weights' => 0, 'errors' => $errors];
        }

        try {
            if (!$dryRun) {
                // Create or update weight entry
                DailyWeight::upsertForUserAndDate(
                    $user->id,
                    $date,
                    (float) $data['weight'],
                    $data['notes'] ?? null
                );
            }
            $weights++;
        } catch (\Exception $e) {
            $errors[] = "Failed to create weight entry for {$date->format('Y-m-d')}: " . $e->getMessage();
        }

        return [
            'weights' => $weights,
            'errors' => $errors,
        ];
    }

    private function extractDateFromFile(string $file, array $data): ?Carbon
    {
        $filename = basename($file, '.json');
        
        // Try various date formats from filename
        $patterns = [
            '/^(\d{4}-\d{2}-\d{2})/',           // YYYY-MM-DD
            '/^(\d{2}-\d{2}-\d{4})/',           // MM-DD-YYYY
            '/^(\d{4})(\d{2})(\d{2})/',         // YYYYMMDD
            '/(\d{4}-\d{2}-\d{2})/',            // YYYY-MM-DD anywhere in filename
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $filename, $matches)) {
                try {
                    if (count($matches) >= 4) {
                        // YYYYMMDD format
                        return Carbon::createFromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);
                    } else {
                        // Other formats
                        return Carbon::parse($matches[1]);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Try parsing the date field from JSON data if available
        if (isset($data['date'])) {
            try {
                return Carbon::parse($data['date']);
            } catch (\Exception $e) {
                // Continue to return null
            }
        }

        return null;
    }
}

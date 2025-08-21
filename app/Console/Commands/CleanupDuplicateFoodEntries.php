<?php

namespace App\Console\Commands;

use App\Models\Food;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateFoodEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:duplicate-food-entries {user_id} {--dry-run : Show what would be deleted without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate food entries (same user, food type, calories, notes, and date)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info("Finding duplicate food entries for user ID: {$userId}");

        // Find duplicates using a more sophisticated approach
        $duplicates = $this->findDuplicates($userId);

        if (empty($duplicates)) {
            $this->info('No duplicate entries found!');

            return 0;
        }

        $this->info('Found '.count($duplicates).' sets of duplicates');

        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $this->line("Processing: {$duplicate->food_name} on {$duplicate->date} ({$duplicate->calories} cal)");
            $this->line("  Found {$duplicate->count} duplicates, keeping 1, removing ".($duplicate->count - 1));

            if (! $dryRun) {
                $deleted = $this->removeDuplicates($duplicate);
                $totalDeleted += $deleted;
                $this->line("  Deleted {$deleted} duplicate entries");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("Would delete {$this->calculateTotalToDelete($duplicates)} duplicate entries");
            $this->warn('Run without --dry-run to actually remove duplicates');
        } else {
            $this->info("Successfully deleted {$totalDeleted} duplicate entries");
        }

        return 0;
    }

    private function findDuplicates(int $userId): array
    {
        $sql = '
            SELECT 
                f.user_id,
                f.food_type_id,
                ft.name as food_name,
                DATE(f.consumed_at) as date,
                f.total_calories as calories,
                f.notes,
                COUNT(*) as count,
                MIN(f.id) as keep_id,
                GROUP_CONCAT(f.id) as all_ids
            FROM food f
            JOIN food_types ft ON f.food_type_id = ft.id
            WHERE f.user_id = ?
            GROUP BY 
                f.user_id, 
                f.food_type_id, 
                DATE(f.consumed_at), 
                f.total_calories, 
                f.notes
            HAVING COUNT(*) > 1
            ORDER BY count DESC, ft.name
        ';

        return DB::select($sql, [$userId]);
    }

    private function removeDuplicates(object $duplicate): int
    {
        $allIds = explode(',', $duplicate->all_ids);
        $keepId = $duplicate->keep_id;

        // Remove the ID we want to keep from the deletion list
        $idsToDelete = array_filter($allIds, function ($id) use ($keepId) {
            return $id != $keepId;
        });

        if (empty($idsToDelete)) {
            return 0;
        }

        return Food::whereIn('id', $idsToDelete)->delete();
    }

    private function calculateTotalToDelete(array $duplicates): int
    {
        return array_sum(array_map(function ($duplicate) {
            return $duplicate->count - 1; // Keep 1, delete the rest
        }, $duplicates));
    }
}

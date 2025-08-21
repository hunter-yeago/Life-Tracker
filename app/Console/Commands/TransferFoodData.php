<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Models\User;
use Illuminate\Console\Command;

class TransferFoodData extends Command
{
    protected $signature = 'transfer:food-data {from_user_id} {to_user_id}';
    protected $description = 'Transfer food data from one user to another';

    public function handle(): int
    {
        $fromUserId = $this->argument('from_user_id');
        $toUserId = $this->argument('to_user_id');

        $fromUser = User::find($fromUserId);
        $toUser = User::find($toUserId);

        if (!$fromUser) {
            $this->error("Source user with ID {$fromUserId} not found.");
            return 1;
        }

        if (!$toUser) {
            $this->error("Target user with ID {$toUserId} not found.");
            return 1;
        }

        $foodCount = Food::where('user_id', $fromUserId)->count();

        if ($foodCount === 0) {
            $this->info("No food entries found for user {$fromUser->name}.");
            return 0;
        }

        $this->info("Transferring {$foodCount} food entries from {$fromUser->name} to {$toUser->name}");

        $updated = Food::where('user_id', $fromUserId)->update(['user_id' => $toUserId]);

        $this->info("Successfully transferred {$updated} food entries!");

        return 0;
    }
}
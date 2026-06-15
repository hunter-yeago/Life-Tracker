<?php

namespace App\Console\Commands;

use App\Actions\Track\EditDailyNote;
use App\Actions\Track\LogFood;
use App\Actions\Track\LogWorkout;
use App\Actions\Track\SetWeight;
use App\Actions\Track\ShowDailySummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class Track extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'track {--date=} {--user=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive terminal session for logging daily food, workouts, weight, and notes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = $this->resolveUser();
        $date = Carbon::parse($this->option('date') ?? 'today');

        while (true) {
            ShowDailySummary::handle($user, $date);

            $action = select(
                label: 'What would you like to do?',
                options: [
                    'food' => 'Log food',
                    'workout' => 'Log workout',
                    'weight' => 'Set/update weight',
                    'note' => 'Edit daily note',
                    'date' => 'Change date',
                    'exit' => 'Exit',
                ],
            );

            match ($action) {
                'food' => LogFood::handle($user, $date),
                'workout' => LogWorkout::handle($user, $date),
                'weight' => SetWeight::handle($user, $date),
                'note' => EditDailyNote::handle($user, $date),
                'date' => $date = Carbon::parse(text(
                    label: 'New date (Y-m-d)',
                    default: $date->format('Y-m-d'),
                )),
                'exit' => null,
            };

            if ($action === 'exit') {
                break;
            }
        }

        return 0;
    }

    private function resolveUser(): User
    {
        if ($userId = $this->option('user')) {
            return User::findOrFail($userId);
        }

        return User::where('email', '!=', 'test@example.com')->first() ?? User::find(2) ?? User::firstOrFail();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ChallengeGroup;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateChallengeGroupStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'challenge-groups:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update challenge group status to Completed when duration ends';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // যেসব group এর end_date পেরিয়েছে এবং এখনও Active আছে
        $groups = ChallengeGroup::where('status', 'Active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $now)
            ->get();

        foreach ($groups as $group) {
            $group->status = 'Completed';
            $group->save();
        }

        $this->info('Challenge group statuses updated successfully.');

    }
}

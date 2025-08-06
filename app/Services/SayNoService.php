<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Habit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SayNoService
{
    public function addEntry(array $data): Entry
    {
        return Entry::create([
            'user_id' => Auth::id(),
            'date' => Carbon::now(),
            'say_no' => $data['say_no'],
        ]);
    }

    public function getEntries(?string $filter)
    {
        $query = Entry::where('user_id', Auth::id());
        $now = Carbon::now();
        if ($filter === 'day') {
            $query->whereDate('date', $now->toDateString());
        } elseif ($filter === 'month') {
            $query->whereYear('date', $now->year)
                ->whereMonth('date', $now->month);
        } elseif ($filter === 'year') {
            $query->whereYear('date', $now->year);
        }
        return $query->latest()->get();
    }

    public function viewEntry(int $id): ?Entry
    {
        return Entry::where('id', $id)->where('user_id', Auth::id())->first();
    }

    public function deleteEntry(int $id): bool
    {
        $entry = Entry::where('id', $id)->where('user_id', Auth::id())->first();

        if ($entry) {
            $entry->delete();
            return true;
        }

        return false;
    }
}
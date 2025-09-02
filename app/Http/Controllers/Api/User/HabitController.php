<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddNewHabitRequest;
use App\Services\User\HabitService;
use Exception;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    protected $habitService;

    public function __construct(HabitService $habitService)
    {
        $this->habitService = $habitService;
    }

    public function addNewHabit(AddNewHabitRequest $request)
    {
        try {
            $habit = $this->habitService->addNewHabit($request->validated());
            return $this->sendResponse($habit, 'Habit created successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to create habit.', [], 500);
        }
    }
    public function getHabits(Request $request)
    {
        try {
            $isArchived = $request->query('isArchived');
            $habits = $this->habitService->getHabits($isArchived);
            return $this->sendResponse($habits, 'Habits fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch habits.', [], 500);
        }
    }
    public function viewHabit($id)
    {
        try {
            $habit = $this->habitService->viewHabit($id);
            if (!$habit) {
                return $this->sendError('Habit not found.', [], 404);
            }
            return $this->sendResponse($habit, 'Habit fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function deleteHabit($id)
    {
        try {
            $deleted = $this->habitService->deleteHabit($id);
            if (!$deleted) {
                return $this->sendError('Habit not found or unauthorized.', [], 404);
            }
            return $this->sendResponse([], 'Habit deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function archivedHabit(Request $request)
    {
        try {
            $habit = $this->habitService->archivedHabit($request->habit_id);
            if (!$habit) {
                return $this->sendError('Habit not found.', [], 404);
            }
            $message = $habit->isArchived === true
                ? 'Habit archived successfully.'
                : 'Habit unarchived successfully.';
            return $this->sendResponse($habit, $message);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function doneHabit(Request $request)
    {
        try {
            $habit = $this->habitService->doneHabit($request->habit_id);
            if (!$habit) {
                return $this->sendError('Habit not found.', [], 404);
            }
            return $this->sendResponse($habit, 'Habit done successfully');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}

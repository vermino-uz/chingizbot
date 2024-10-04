<?php

namespace App\Filament\Widgets;

use App\Models\BotUser;
use App\Models\Group;
use App\Models\Voice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Foydalanuvchilar', number_format(BotUser::count(), 0, '', ' ') . ' ta')->color("success")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->description("Foydanaluvchilar haftalik o'sish darajasi")
            ->chart($this->getUsersPerDay()['usersPerDay']),
            Stat::make('Guruhlar', number_format(Group::count(), 0, '', ' ') . ' ta')->color("primary")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->description("Guruhlar haftalik o'sish darajasi")
            ->chart($this->getGroupsPerDay()['groupsPerDay']),
            Stat::make('Ovozlar', number_format(Voice::count(), 0, '', ' ') . ' ta')->color("warning")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->description("Ovozlar ishlatish darajasi")
            ->chart($this->getVoicesPerDay()['voicesPerDay']),
        ];
    }
    private function getUsersPerDay(): array
    {
        $now = Carbon::now();
        $usersPerDay = [];

        $days = collect(range(0, 6))->map(function ($day) use ($now, &$usersPerDay) {
            // Subtract days from the current day to get the date for each day of the week
            $date = $now->subDays($day);
            $count = BotUser::whereDate('created_at', $date)->count();
            $usersPerDay[] = $count;

            return $date->format('D M j'); // For format like "Wed Sep 28"
        })->toArray();  // reverse the collection to start from 7 days ago

        return [
            'usersPerDay' => array_reverse($usersPerDay),
            'days' => $days
        ];
    }
    private function getGroupsPerDay(): array
    {
        $now = Carbon::now();
        $groupsPerDay = [];

        $days = collect(range(0, 6))->map(function ($day) use ($now, &$groupsPerDay) {
            $date = $now->subDays($day);
            $count = Group::whereDate('created_at', $date)->count();
            $groupsPerDay[] = $count;

            return $date->format('D M j'); // For format like "Wed Sep 28"
        })->toArray();  // reverse the collection to start from 7 days ago

        return [
            'groupsPerDay' => array_reverse($groupsPerDay),
            'days' => $days
        ];
    }
    private function getVoicesPerDay(): array
    {
        $now = Carbon::now();
        $voicesPerDay = [];

        $days = collect(range(0, 6))->map(function ($day) use ($now, &$voicesPerDay) {
            $date = $now->subDays($day);
            $count = Voice::whereDate('created_at', $date)->sum('uses');
            $voicesPerDay[] = $count;

            return $date->format('D M j'); // For format like "Wed Sep 28"
        })->toArray();  // reverse the collection to start from 7 days ago

        return [
            'voicesPerDay' => array_reverse($voicesPerDay),
            'days' => $days
        ];
    }
}

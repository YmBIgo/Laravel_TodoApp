<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table("tasks")->insert(
            [
                [
                    'name' => 'test task1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'test task2',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'test task3',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]
        );
    }
}

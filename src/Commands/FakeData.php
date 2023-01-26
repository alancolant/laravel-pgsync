<?php

namespace Alancolant\LaravelPgsync\Commands;

use Illuminate\Console\Command;

class FakeData extends Command
{
    protected $signature = 'pgsync:fakedata';

    protected $description = 'Listen Postgresql trigger to handle change';

    public function handle(): int
    {
//        while (true) {
//            $u = User::factory()->create();
//            Post::query()->insert(array_fill(0, 500, [
//                'user_id'     => $u->id,
//                'name'        => 'teste',
//                'description' => 'tester'
//            ]));
//        }
        return self::SUCCESS;
    }
}

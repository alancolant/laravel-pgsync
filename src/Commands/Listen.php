<?php

namespace Alancolant\LaravelPgsync\Commands;

use Alancolant\LaravelPgsync\Publishers\AbstractPublisher;
use Alancolant\LaravelPgsync\Publishers\ElasticsearchPublisher;
use Alancolant\LaravelPgsync\Subscribers\AbstractSubscriber;
use Alancolant\LaravelPgsync\Subscribers\PostgresqlSubscriber;
use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;
use Illuminate\Console\Command;

class Listen extends Command
{
    protected $signature = 'pgsync:listen';

    protected $description = 'Listen Postgresql trigger to handle change';

    private AbstractSubscriber $subscriber;

    private AbstractPublisher $publisher;

    private bool $running = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->publisher = new ElasticsearchPublisher();
        $this->subscriber = new PostgresqlSubscriber();

        $this->trap([SIGTERM, SIGQUIT, SIGINT], function () {
            //@TODO Graceful shutdown
            $this->running = false;
            exit();
        });

        $this->subscriber->initialize();

        set_time_limit(0);
        $this->subscriber->startListening();
        $i = 0;
        while ($this->running) {
            $event = $this->subscriber->getNextEvent();
            $i++;
            echo "$i\n";
            $this->_broadcast($event);
        }
        $this->subscriber->stopListening();

        return Command::SUCCESS;
    }

    /**
     * @param  InsertEvent|UpdateEvent|DeleteEvent  $event
     * @return void
     *
     * @suggestion Use Redis or other memory cache as intermediary
     */
    private function _broadcast(InsertEvent|UpdateEvent|DeleteEvent $event): void
    {
        if ($event instanceof InsertEvent) {
            $this->publisher->handleInsert($event);

            return;
        }

        if ($event instanceof UpdateEvent) {
            if (config('pgsync.action_on_soft_delete') === 'delete') {
                $this->publisher->handleUpdateWithSoftDelete($event);
            } elseif (config('pgsync.action_on_soft_delete') === 'update') {
                $this->publisher->handleUpdate($event);
            }

            return;
        }

        if ($event instanceof DeleteEvent) {
            $this->publisher->handleDelete($event);
        }
    }

//    private function test()
//    {
//        $res = DB::table('posts')
//            ->leftJoinSub(
//                DB::table('users')
//                    ->leftJoinSub(
//                        DB::table('posts')
//                            ->leftJoinSub(
//                                DB::table('users')->select([DB::raw('"users".*')]), 'user2', 'user2.id', 'posts.user_id'
//                            )->select([DB::raw('"posts".*'), DB::raw('to_json("user2") AS "user2"')]), 'posts',
//                        'users.id', 'posts.user_id')
//                    ->groupBy(DB::raw('"users"."id"'))
//                    ->select([DB::raw('"users".*'), DB::raw('jsonb_agg("posts") as "posts"')]), 'pgsync_user',
//                'posts.user_id', 'pgsync_user.id')
//            ->select([DB::raw('posts.*'), DB::raw('to_json("pgsync_user") as "user"')]);
//
//        $res = DB::query()
//            ->fromSub($res, 'pgsync_final_res')
//            ->select([DB::raw('to_json("pgsync_final_res") as "pgsync_final_res"')]);
//            ->select([DB::raw('to_json(res) as res')]);
//        dd($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->count());
//        dd(json_decode($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->dd()));
//        dd(json_decode($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->first()->pgsync_final_res));
//        dd(json_decode($res->get()->toArray()[0]->res), $res->get()->toArray()[0]->user);
//    }
}

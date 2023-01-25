<?php

namespace Alancolant\LaravelPgsync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Prepare extends Command
{
    protected $signature = 'pgsync:prepare';

    protected $description = 'Prepare Postgresql database to trigger change';


    public function handle(): int
    {
//        $this->test();
        $this->_createTriggerFunction();
        foreach ($this->_getTables() as $table) {
            $this->_createTriggerForTable($table);
        }
        return Command::SUCCESS;
    }

    protected function _getTables(): array|Collection
    {
        return array_unique(data_get(config('laravel-pgsync.indices'), '*.table'));

        return collect(Schema::getAllTables())
            ->map(fn($i) => $i->tablename)
            ->filter(function ($table) {
                foreach (config('laravel-pgsync.tables.includes', []) as $include) {
                    if (!fnmatch($include, $table)) {
                        return false;
                    }
                }
                foreach (config('laravel-pgsync.tables.excludes', []) as $exclude) {
                    if (fnmatch($exclude, $table)) {
                        return false;
                    }
                }
                return true;
            })->values();
    }


    private function _createTriggerForTable(string $table): void
    {
        DB::unprepared(<<<SQL
CREATE OR REPLACE TRIGGER pgync_{$table}_trigger
AFTER DELETE OR UPDATE OR INSERT ON {$table} FOR EACH ROW
EXECUTE PROCEDURE pgsync_notify_trigger();
SQL
        );
    }

    private function _createTriggerFunction(): void
    {
        DB::unprepared(<<<SQL
CREATE OR REPLACE FUNCTION pgsync_notify_trigger() RETURNS trigger AS \$trigger\$
DECLARE
  payload TEXT;
BEGIN
  IF TG_OP <> 'UPDATE' OR NEW IS DISTINCT FROM OLD THEN
    -- Build the payload
    payload := json_build_object(
        'timestamp',CURRENT_TIMESTAMP,
        'action',LOWER(TG_OP),
        'schema',TG_TABLE_SCHEMA,
        'identity',TG_TABLE_NAME,
        'record',row_to_json(COALESCE(NEW, OLD)),
        'old',row_to_json(CASE TG_OP WHEN 'UPDATE' THEN OLD  END)
    );
    PERFORM pg_notify('pgsync_event',payload);
  END IF;
  RETURN COALESCE(NEW, OLD);
END;
\$trigger\$ LANGUAGE plpgsql VOLATILE;
SQL
        );
    }

    private function test()
    {
        $res = DB::table('posts')
            ->leftJoinSub(
                DB::table('users')
                    ->leftJoinSub(
                        DB::table('posts')
                            ->leftJoinSub(
                                DB::table('users')->select([DB::raw('"users".*')])
                                , 'user2', 'user2.id', 'posts.user_id'
                            )->select([DB::raw('"posts".*'), DB::raw('to_json("user2") AS "user2"')])
                        , 'posts', 'users.id', 'posts.user_id')
                    ->groupBy(DB::raw('"users"."id"'))
                    ->select([DB::raw('"users".*'), DB::raw('jsonb_agg("posts") as "posts"')])
                , 'pgsync_user', 'posts.user_id', 'pgsync_user.id')
            ->select([DB::raw('posts.*'), DB::raw('to_json("pgsync_user") as "user"')]);


        $res = DB::query()
            ->fromSub($res, 'pgsync_final_res')
            ->select([DB::raw('to_json("pgsync_final_res") as "pgsync_final_res"')]);
//            ->select([DB::raw('to_json(res) as res')]);
//        dd($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->count());
        dd(json_decode($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->dd()));
        dd(json_decode($res->where(DB::raw("\"user\"::jsonb->>'id'"), 8)->first()->pgsync_final_res));
        dd(json_decode($res->get()->toArray()[0]->res), $res->get()->toArray()[0]->user);
    }
}

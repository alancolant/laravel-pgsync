<?php

namespace Alancolant\LaravelPgsync\Subscribers;

use Alancolant\LaravelPgsync\Types\DeleteEvent;
use Alancolant\LaravelPgsync\Types\InsertEvent;
use Alancolant\LaravelPgsync\Types\UpdateEvent;
use Error;
use Illuminate\Support\Facades\DB;
use PDO;

class PostgresqlSubscriber extends AbstractSubscriber
{
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        $conn = DB::connection(config('pgsync.connection', config('database.default')));
        if ($conn->getDriverName() !== 'pgsql') {
            throw new Error("Driver {$conn->getDriverName()} not supported!");
        }
        $this->pdo = $conn->getPdo();
    }

    public function initialize(): bool
    {
        foreach ($this->_getTables() as $table) {
            $this->_createTriggerForTable($table);
        }

        return true;
    }

    protected function _createTriggerForTable(string $table): void
    {
        DB::unprepared(
            /** @lang PostgreSQL */
            "CREATE OR REPLACE TRIGGER pgync_{$table}_trigger AFTER DELETE OR UPDATE OR INSERT ON {$table} FOR EACH ROW EXECUTE PROCEDURE pgsync_notify_trigger();"
        );
    }

    public function startListening(): void
    {
        parent::startListening();
        $this->pdo->exec('LISTEN pgsync_event');
    }

    /**
     * @throws Error
     */
    public function getNextEvent(): DeleteEvent|InsertEvent|UpdateEvent
    {
        while (true) {
            while ($result = $this->pdo->pgsqlGetNotify(PDO::FETCH_ASSOC, 1000 * 10)) {
                $payload = json_decode($result['payload'], true);

                return match ($payload['action']) {
                    'insert' => new InsertEvent($payload['identity'], $payload['record']),
                    'update' => new UpdateEvent($payload['identity'], $payload['record'], $payload['old']),
                    'delete' => new DeleteEvent($payload['identity'], $payload['record']),
                    default => throw new Error("Unknown event action {$payload['action']}")
                };
            }
        }
    }
}

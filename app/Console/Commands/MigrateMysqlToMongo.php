<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateMysqlToMongo extends Command
{
    protected $signature = 'data:migrate-mysql-to-mongo
        {--dry-run : Read and count source rows without writing to MongoDB}
        {--force : Replace existing MongoDB collections}';

    protected $description = 'Copy application data from the legacy MySQL database to MongoDB';

    private array $tables = [
        'users' => ['json' => [], 'dates' => ['email_verified_at', 'created_at', 'updated_at']],
        'energy_readings' => ['json' => [], 'dates' => ['created_at', 'updated_at']],
        'device_schedules' => ['json' => ['days'], 'dates' => ['created_at', 'updated_at']],
        'maintenance_alerts' => ['json' => [], 'dates' => ['created_at', 'updated_at']],
        'energy_budgets' => ['json' => [], 'dates' => ['created_at', 'updated_at']],
        'schedule_histories' => ['json' => [], 'dates' => ['start_time', 'end_time', 'logged_at', 'archived_at', 'created_at', 'updated_at']],
        'monthly_electricity_bills' => ['json' => [], 'dates' => ['billing_month', 'created_at', 'updated_at']],
    ];

    public function handle(): int
    {
        $source = DB::connection('mysql_legacy');

        try {
            $source->getPdo();
        } catch (\Throwable $exception) {
            $this->error('Cannot connect to the legacy MySQL database: '.$exception->getMessage());

            return self::FAILURE;
        }

        $mongo = DB::connection('mongodb');

        foreach ($this->tables as $table => $mapping) {
            if (! Schema::connection('mysql_legacy')->hasTable($table)) {
                $this->warn("Skipping missing table: {$table}");
                continue;
            }

            $count = $source->table($table)->count();
            $this->info("{$table}: {$count} row(s)");

            if ($this->option('dry-run') || $count === 0) {
                continue;
            }

            $collection = $mongo->getCollection($table);

            if ($this->option('force')) {
                $collection->deleteMany([]);
            } elseif ($collection->countDocuments() > 0) {
                $this->error("MongoDB collection {$table} is not empty. Use --force to replace it.");

                return self::FAILURE;
            }

            $source->table($table)->orderBy('id')->chunk(500, function ($rows) use ($collection, $mapping): void {
                $documents = [];

                foreach ($rows as $row) {
                    $document = get_object_vars($row);
                    $document['_id'] = $document['id'];
                    unset($document['id']);

                    foreach ($mapping['json'] as $field) {
                        if (isset($document[$field]) && is_string($document[$field])) {
                            $document[$field] = json_decode($document[$field], true, 512, JSON_THROW_ON_ERROR);
                        }
                    }

                    foreach ($mapping['dates'] as $field) {
                        if (! empty($document[$field])) {
                            $document[$field] = Carbon::parse($document[$field]);
                        }
                    }

                    $documents[] = $document;
                }

                if ($documents !== []) {
                    $collection->insertMany($documents);
                }
            });
        }

        $this->info($this->option('dry-run') ? 'Dry run completed.' : 'Migration completed.');

        return self::SUCCESS;
    }
}
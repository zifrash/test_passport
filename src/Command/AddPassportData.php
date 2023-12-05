<?php

namespace App\Command;

use PgSql\Connection;
use PgSql\Result;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Generator;

#[AsCommand(
    name: 'app:add-passport-data',
    description: 'Add password serials and number in DB.',
    aliases: ['app:update-passport-data']
)]
class AddPassportData extends Command
{
    private Connection $connection;

    public function __construct()
    {
        $this->connection = pg_connect($_ENV['DATABASE_URL']);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (pg_fetch_array(
            $this->query("SELECT * FROM pg_catalog.pg_tables WHERE tablename = 'passport_new';")
        )) {
            $this->query('DROP TABLE passport_new;');
        }

        $this->query('CREATE TABLE passport_new (id INT NOT NULL, series VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL)');

        $filesPath = 'files/list_of_expired_passports.csv';

        $rowCounter = 1;
        $insertValues = '';
        foreach ($this->readCsvFile($filesPath) as $row) {
            $insertValues .= "({$rowCounter}, '{$row[0]}', '{$row[1]}'),";

            // все что закоментировано, можно раскоментировать и посмотреть что по производительности
            if ($rowCounter % 1000000 === 0) {
                $timeStart = microtime(true);
                $this->query('INSERT INTO passport_new (id, series, number) VALUES '. substr($insertValues,0,-1) .';');
                self::renderPerformance($timeStart);

                $insertValues = '';
            }

            $rowCounter++;
        }

        // заносим данные которые не вошли в последний миллион
        if (strlen($insertValues) > 0) {
            $this->query('INSERT INTO passport_new (id, series, number) VALUES ' . substr($insertValues, 0, -1) . ';');
        }

        // если все это сделать в конце, то можно по скорости выйграть примерно в 2 раза
        $timeStart = microtime(true);
        $this->query('ALTER TABLE passport_new ADD PRIMARY KEY (id)');
        $this->query('CREATE SEQUENCE passport_new_id_seq START ' . $rowCounter + 1 . ' OWNED BY passport_new.id;');
        $this->query("ALTER TABLE passport_new ALTER COLUMN id SET DEFAULT nextval('passport_new_id_seq')");
        $this->query('CREATE INDEX PASSPORT_NEW_SERIES_NUMBER_IDX ON passport_new (series, number);');
        self::renderPerformance($timeStart);

        $this->query('
            BEGIN TRANSACTION ISOLATION LEVEL SERIALIZABLE;
                ALTER TABLE passport RENAME TO passport_old;
                ALTER INDEX passport_pkey RENAME TO passport_old_pkey;
                ALTER INDEX passport_series_number_idx RENAME TO passport_old_series_number_idx;
                ALTER SEQUENCE passport_id_seq RENAME TO passport_old_id_seq;

                ALTER TABLE passport_new RENAME TO passport;
                ALTER INDEX passport_new_pkey RENAME TO passport_pkey;
                ALTER INDEX passport_new_series_number_idx RENAME TO passport_series_number_idx;
                ALTER SEQUENCE passport_new_id_seq RENAME TO passport_id_seq;

                DROP TABLE passport_old;
            COMMIT TRANSACTION;
        ');

        return Command::SUCCESS;
    }

    private function query(string $query): Result
    {
        return pg_query($this->connection, $query);
    }

    private function readCsvFile(string $filePath): Generator
    {
        $handle = fopen($filePath, "r");
        fgetcsv($handle); // убираем первую строку с названием колонок

        while (($row = fgetcsv($handle)) !== false) { yield $row; }

        fclose($handle);
    }

    private static function renderPerformance(float $timeStart): void
    {
        $time = microtime(true) - $timeStart;
        echo 'time: ' . round($time, 2) . ' s; memory: ' . round(memory_get_usage() / 1048576, 2) . " Mb;\n";
    }
}
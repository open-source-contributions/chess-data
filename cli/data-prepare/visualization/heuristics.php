<?php

namespace ChessData\Cli\DataPrepare\Training;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Dotenv\Dotenv;
use Chess\PGN\Symbol;
use ChessData\Pdo;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

class DataPrepareCli extends CLI
{
    const DATA_FOLDER = __DIR__.'/../../../dataset/visualization';

    protected function setup(Options $options)
    {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../../../');
        $dotenv->load();

        $options->setHelp('Creates a prepared dataset of heuristics in JSON format for further visualization.');
        $options->registerArgument('n', 'The number of games.', true);
        $options->registerArgument('filename', 'The dataset filename.', true);
        $options->registerOption('win', 'White win.');
        $options->registerOption('lose', 'White lose.');
        $options->registerOption('draw', 'Draw.');
    }

    protected function main(Options $options)
    {
        if ($options->getOpt('win')) {
            $result = '1-0';
        } elseif ($options->getOpt('lose')) {
            $result = '0-1';
        } else {
            $result = '1/2-1/2';
        }

        $opt = key($options->getOpt());
        $filename = "{$this->snakeCase($options->getArgs()[1])}_{$opt}.json";

        $sql = "SELECT * FROM games WHERE White SOUNDS LIKE '{$options->getArgs()[1]}'
            ORDER BY RAND()
            LIMIT {$options->getArgs()[0]}";

        $games = Pdo::getInstance()
                    ->query($sql)
                    ->fetchAll(\PDO::FETCH_ASSOC);

        $fp = fopen(self::DATA_FOLDER."/$filename", 'w');
        fwrite($fp, json_encode($games));
        fclose($fp);
    }

    protected function snakeCase(string $string)
    {
        return str_replace(' ', '_', strtolower(trim($string)));
    }
}

$cli = new DataPrepareCli();
$cli->run();

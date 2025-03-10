<?php

namespace ChessData\Cli\DataPrepare\Training;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use Dotenv\Dotenv;
use Chess\Combinatorics\RestrictedPermutationWithRepetition;
use Chess\HeuristicPicture;
use Chess\ML\Supervised\Classification\LinearCombinationLabeller;
use Chess\PGN\Movetext;
use Chess\PGN\Symbol;
use ChessData\Pdo;
use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;

/**
 * Prepares the data.
 *
 * It loads games from the database and plays them from the start position.
 */
class Start extends CLI
{
    const DATA_FOLDER = __DIR__.'/../../../../dataset/training/classification';

    protected function setup(Options $options)
    {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../../../../');
        $dotenv->load();

        $options->setHelp('Creates a prepared CSV dataset in the dataset/training/classification folder.');
        $options->registerArgument('n', 'A random number of games to be queried.', true);
    }

    protected function main(Options $options)
    {
        $opt = key($options->getOpt());
        $filename = "start_{$options->getArgs()[0]}_".time().'.csv';

        $sql = "SELECT * FROM games
            ORDER BY RAND()
            LIMIT {$options->getArgs()[0]}";

        $games = Pdo::getInstance()
                    ->query($sql)
                    ->fetchAll(\PDO::FETCH_ASSOC);

        $dimensions = (new HeuristicPicture(''))->getDimensions();

        $permutations = (new RestrictedPermutationWithRepetition())
            ->get(
                [ 5, 8, 13, 21 ],
                count($dimensions),
                100
            );

        $fp = fopen(self::DATA_FOLDER."/$filename", 'w');

        foreach ($games as $game) {
            try {
                $sequence = (new Movetext($game['movetext']))->sequence();
                foreach ($sequence as $movetext) {
                    $balance = (new HeuristicPicture($movetext))->take()->getBalance();
                    $end = end($balance);
                    $label = (new LinearCombinationLabeller($permutations))->label($end);
                    $row = array_merge($end, [$label[Symbol::BLACK]]);
                    fputcsv($fp, $row, ';');
                }
            } catch (\Exception $e) {}
        }

        fclose($fp);
    }
}

$cli = new Start();
$cli->run();

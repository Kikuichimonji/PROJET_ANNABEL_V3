<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande a usage unique : copie les donnees de l'ancienne base MySQL/Laragon
 * vers la nouvelle base SQLite (var/data.db) pointee par DATABASE_URL.
 *
 * A lancer une seule fois, apres avoir cree le schema SQLite
 * (php bin/console doctrine:schema:create) et sauvegarde la base MySQL.
 */
#[AsCommand(name: 'app:migrate-to-sqlite')]
class MigrateToSqliteCommand extends Command
{
    /** @var Connection */
    private $sqlite;

    public function __construct(Connection $connection)
    {
        $this->sqlite = $connection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Copie les donnees d'une base MySQL existante vers la base SQLite courante (a usage unique).")
            ->addArgument('mysql-url', InputArgument::OPTIONAL, "URL de connexion MySQL source (sinon la variable d'env MYSQL_SOURCE_URL est utilisee)")
            ->addOption('force', null, InputOption::VALUE_NONE, 'Vide chaque table SQLite cible avant de la remplir, meme si elle contient deja des donnees');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->sqlite->getDatabasePlatform()->getName() !== 'sqlite') {
            $io->error('DATABASE_URL ne pointe pas vers une base SQLite. Verifiez le .env avant de lancer cette commande.');

            return Command::FAILURE;
        }

        $sourceUrl = $input->getArgument('mysql-url') ?: ($_ENV['MYSQL_SOURCE_URL'] ?? getenv('MYSQL_SOURCE_URL') ?: null);
        if (!$sourceUrl) {
            $io->error("Aucune URL MySQL source fournie (argument mysql-url ou variable d'env MYSQL_SOURCE_URL).");

            return Command::FAILURE;
        }

        $mysql = DriverManager::getConnection(['url' => $sourceUrl, 'charset' => 'utf8mb4']);

        try {
            $orderedTables = $this->sortTablesByDependency($this->sqlite);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->section('Ordre de copie : ' . implode(' -> ', $orderedTables));

        $this->sqlite->beginTransaction();

        try {
            foreach ($orderedTables as $table) {
                $existing = (int) $this->sqlite->fetchOne("SELECT COUNT(*) FROM {$table}");
                if ($existing > 0 && !$input->getOption('force')) {
                    $io->error("La table '{$table}' contient deja {$existing} ligne(s). Relancez avec --force pour l'ecraser, apres verification.");
                    $this->sqlite->rollBack();

                    return Command::FAILURE;
                }

                if ($existing > 0) {
                    $this->sqlite->executeStatement("DELETE FROM {$table}");
                }

                $rows = $mysql->fetchAllAssociative("SELECT * FROM `{$table}`");
                foreach ($rows as $row) {
                    $this->sqlite->insert($table, $row);
                }

                $io->writeln(sprintf('  %-20s %d ligne(s) copiee(s)', $table, count($rows)));
            }

            $this->sqlite->commit();
        } catch (\Throwable $e) {
            $this->sqlite->rollBack();
            $io->error('Migration annulee suite a une erreur : ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Migration MySQL -> SQLite terminee.');

        return Command::SUCCESS;
    }

    /**
     * Trie les tables de la base cible pour inserer d'abord celles
     * qui ne dependent d'aucune autre table (tri topologique sur les FK).
     *
     * @return string[]
     */
    private function sortTablesByDependency(Connection $connection): array
    {
        $schemaManager = $connection->createSchemaManager();
        $tables = array_values(array_filter(
            $schemaManager->listTableNames(),
            static function (string $table): bool {
                // table technique de gestion des migrations, pas une donnee applicative
                return $table !== 'doctrine_migration_versions';
            }
        ));

        $dependencies = [];
        foreach ($tables as $table) {
            $dependencies[$table] = [];
            foreach ($schemaManager->listTableForeignKeys($table) as $foreignKey) {
                $foreignTable = $foreignKey->getForeignTableName();
                if ($foreignTable !== $table) {
                    $dependencies[$table][] = $foreignTable;
                }
            }
        }

        $ordered = [];
        while (count($ordered) < count($tables)) {
            $progress = false;
            foreach ($dependencies as $table => $requires) {
                if (in_array($table, $ordered, true)) {
                    continue;
                }
                $unresolved = array_diff($requires, $ordered);
                if (empty($unresolved)) {
                    $ordered[] = $table;
                    $progress = true;
                }
            }
            if (!$progress) {
                throw new \RuntimeException('Dependance circulaire detectee entre les tables, migration manuelle necessaire.');
            }
        }

        return $ordered;
    }
}

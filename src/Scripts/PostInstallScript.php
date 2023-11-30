<?php
namespace ByteSpin\MessengerDedupeBundle\Scripts;

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class PostInstallScript
{
    public static function postInstall(): void
    {
        echo "This script will configure the ByteSpin Messenger Dedupe Bundle in your doctrine.yaml file." . PHP_EOL;
        echo "It will add configuration for the bundle under the selected or default entity manager." . PHP_EOL;
        echo "Do you want to proceed? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'yes') {
            echo "Aborting script execution." . PHP_EOL;
            return;
        }

        $projectBasePath = getcwd();
        $doctrineConfigFile = $projectBasePath . '/config/packages/doctrine.yaml';

        if (!file_exists($doctrineConfigFile)) {
            echo "The doctrine.yaml file does not exist." . PHP_EOL;
            return;
        }

        $config = Yaml::parseFile($doctrineConfigFile);

        // Lire les connexions DBAL
        if (empty($config['doctrine']['dbal']['connections'])) {
            echo "No named DBAL connections found in doctrine.yaml. Using the default connection." . PHP_EOL;
            $selectedConnection = 'default';
        } else {
            $connections = array_keys($config['doctrine']['dbal']['connections']);
            $selectedConnection = self::askForDBALConnection($connections);
        }

        if (!isset($config['doctrine']['orm']['entity_managers'][$selectedConnection])) {
            echo "Creating entity manager for connection: $selectedConnection" . PHP_EOL;
            $config['doctrine']['orm']['entity_managers'][$selectedConnection] = [
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping' => true,
                'connection' => $selectedConnection,
                'mappings' => [
                    'ByteSpin\\MessengerDedupeBundle' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/vendor/bytespin/messenger-dedupe-bundle/src/Entity',
                        'prefix' => 'ByteSpin\\MessengerDedupeBundle\\Entity',
                        'alias' => 'ByteSpinMessengerDedupeBundle'
                    ]
                ]
            ];
        } else {
            echo "Modifying entity manager for connection: $selectedConnection" . PHP_EOL;

            if (!isset($config['doctrine']['orm']['entity_managers'][$selectedConnection]['mappings']['ByteSpin\\MessengerDedupeBundle'])) {
                echo "Adding configuration for ByteSpin\MessengerDedupeBundle to the entity manager: $selectedConnection" . PHP_EOL;
                $config['doctrine']['orm']['entity_managers'][$selectedConnection]['mappings']['ByteSpin\\MessengerDedupeBundle'] = [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/vendor/bytespin/messenger-dedupe-bundle/src/Entity',
                    'prefix' => 'ByteSpin\\MessengerDedupeBundle\\Entity',
                    'alias' => 'ByteSpinMessengerDedupeBundle'
                ];
            } else {
                echo "The configuration for ByteSpin\MessengerDedupeBundle already exists for the entity manager: $selectedConnection" . PHP_EOL;
                self::updateBundlesFile($projectBasePath . '/config/bundles.php');
                return;
            }
        }

        echo "We are about to add or update the entity manager configuration for the ByteSpin Bundle in your doctrine.yaml file.".PHP_EOL;
        echo "Do you want to proceed? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'yes') {
            echo "Aborting script execution." . PHP_EOL;
            return;
        }

        file_put_contents($doctrineConfigFile, Yaml::dump($config, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        self::updateBundlesFile($projectBasePath . '/config/bundles.php');
    }

    private static function askForDBALConnection($connections)
    {
        echo "Please choose a DBAL connection:" . PHP_EOL;
        foreach ($connections as $index => $connection) {
            echo "[$index] $connection" . PHP_EOL;
        }

        $selected = (int) readline("Your choice (number): ");
        return $connections[$selected] ?? $connections[0];
    }

    private static function updateBundlesFile($bundlesFilePath): void
    {
        echo "We are about to declare the ByteSpin Bundle in your bundles.php file if it's not already present.".PHP_EOL;
        echo "Do you want to proceed? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) != 'yes') {
            echo "Aborting script execution." . PHP_EOL;
            return;
        }

        if (!file_exists($bundlesFilePath)) {
            echo "The bundles.php file does not exist." . PHP_EOL;
            return;
        }

        $bundlesFileContent = file_get_contents($bundlesFilePath);
        $newBundleLine = "ByteSpin\\MessengerDedupeBundle\\MessengerDedupeBundle::class => ['all' => true],";

        if (!str_contains($bundlesFileContent, "ByteSpin\\MessengerDedupeBundle\\MessengerDedupeBundle::class")) {
            $bundlesFileContent = str_replace('];', $newBundleLine . PHP_EOL . '];', $bundlesFileContent);
            file_put_contents($bundlesFilePath, $bundlesFileContent);

            echo "ByteSpin\\MessengerDedupeBundle has been added to bundles.php" . PHP_EOL;
        } else {
            echo "ByteSpin\\MessengerDedupeBundle is already defined in bundles.php" . PHP_EOL;
        }
    }
}

PostInstallScript::postInstall();

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

        $bundleConfig = [
            'is_bundle' => false,
            'type' => 'attribute',
            'dir' => '%kernel.project_dir%/vendor/bytespin/messenger-dedupe-bundle/src/Entity',
            'prefix' => 'ByteSpin\MessengerDedupeBundle\Entity',
            'alias' => 'ByteSpin\MessengerDedupeBundle'
        ];

        // Check if any entity managers are defined
        if (empty($config['doctrine']['orm']['entity_managers'])) {
            echo "No entity manager found in doctrine.yaml." . PHP_EOL;
            echo "The script will create configuration for the default entity manager." . PHP_EOL;

            $config['doctrine']['orm'] = [
                'auto_generate_proxy_classes' => true,
                'default_entity_manager' => 'default',
                'entity_managers' => [
                    'default' => [
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                        'auto_mapping' => true,
                        'connection' => 'default',
                        'mappings' => [
                            'ByteSpin\MessengerDedupeBundle' => $bundleConfig
                        ]
                    ]
                ]
            ];
        } else {
            $entityManagers = array_keys($config['doctrine']['orm']['entity_managers']);
            $selectedManager = self::askForEntityManager($entityManagers);

            // Check if the bundle configuration already exists
            if (!isset($config['doctrine']['orm']['entity_managers'][$selectedManager]['mappings']['ByteSpin\MessengerDedupeBundle'])) {
                $config['doctrine']['orm']['entity_managers'][$selectedManager]['mappings']['ByteSpin\MessengerDedupeBundle'] = $bundleConfig;
            } else {
                echo "ByteSpin Messenger Dedupe Bundle configuration already exists for the selected entity manager." . PHP_EOL;
                self::updateBundlesFile($projectBasePath . '/config/bundles.php');
                return;
            }
        }
        file_put_contents($doctrineConfigFile, Yaml::dump($config, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

        echo "Configuration successfully added." . PHP_EOL;
        self::updateBundlesFile($projectBasePath . '/config/bundles.php');

    }

    private static function askForEntityManager($entityManagers)
    {
        echo "Please choose an entity manager:" . PHP_EOL;
        foreach ($entityManagers as $index => $manager) {
            echo "[$index] $manager" . PHP_EOL;
        }

        $selected = (int) readline("Your choice (number): ");
        return $entityManagers[$selected] ?? $entityManagers[0];
    }

    private static function updateBundlesFile($bundlesFilePath): void
    {
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
            echo "ByteSpin\\MessengerDedupeBundle is already in bundles.php" . PHP_EOL;
        }
    }

}
PostInstallScript::postInstall();
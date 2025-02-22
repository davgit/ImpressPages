<?php
namespace Ip\Internal\Plugins;


class Model
{

    public static function getModules()
    {
        return array(
            "Core",
            "Content",
            "Admin",
            "Pages",
            "Administrators",
            "Design",
            "Plugins",
            "Log",
            "Email",
            "Config",
            "Breadcrumb",
            "Repository",
            "InlineManagement",
            "Languages",
            "Cron",
            "Translations",
            "System",
            "Update",
            "Ecommerce"
        );
    }

    public static function activatePlugin($pluginName)
    {
        $activePlugins = self::getActivePluginNames();
        if (in_array($pluginName, $activePlugins)) {
            //already activated
            return true;
        }

        $pluginRecord = self::getPluginRecord($pluginName);

        $config = Model::getPluginConfig($pluginName);

        if (!$config) {
            throw new \Ip\Exception(ipFile(
                'Plugin/' . esc($pluginName) . '/Setup/plugin.json'
            ) . ' doesn\'t exist or is incorrect');
        }

        if (empty($config['name']) || $config['name'] !== $pluginName) {
            throw new \Ip\Exception('Plugin name setting in ' . ipFile(
                'Plugin/' . esc($pluginName) . '/Setup/plugin.json'
            ) . " doesn't match the folder name.");
        }

        if (empty($config['version'])) {
            throw new \Ip\Exception('Missing plugin version number in ' . ipFile(
                'Plugin/' . esc($pluginName) . '/Setup/plugin.json'
            ) . " file.");
        }

        if (!empty($pluginRecord['version']) && (float)$pluginRecord['version'] > (float)$config['version']) {
            throw new \Ip\Exception\Plugin\Setup("You can't downgrade the plugin. Please remove the plugin completely and reinstall if you want to use older version.");
        }

        $workerClass = 'Plugin\\' . $pluginName . '\\Setup\\Worker';
        if (class_exists($workerClass) && method_exists($workerClass, 'activate')) {
            $worker = new $workerClass($config['version']);
            $worker->activate();
        }


        $dbh = ipDb()->getConnection();
        $sql = '
        INSERT INTO
            ' . ipTable('plugin') . '
        SET
            `title` = :title,
            `name` = :pluginName,
            `isActive` = 1,
            `version` = :version
        ON DUPLICATE KEY UPDATE
            `title` = :title,
            `isActive` = 1,
            `version` = :version
        ';

        if (!empty($config['title'])) {
            $pluginTitle = $config['title'];
        } else {
            $pluginTitle = $pluginName;
        }

        $params = array(
            'title' => $pluginTitle,
            'pluginName' => $pluginName,
            'version' => $config['version']
        );
        $q = $dbh->prepare($sql);
        $q->execute($params);

        // set default plugin options
        if (!empty($config['options'])) {
            static::importDefaultOptions($pluginName, $config['options']);
        }

        ipLog()->info(
            'Ip.pluginActivated: {plugin} {version} activated.',
            array('plugin' => $pluginName, 'version' => $config['version'])
        );

        ipEvent(
            'ipPluginActivated',
            array(
                'name' => $pluginName,
                'version' => $config['version'],
            )
        );
        return null;
    }

    protected static function importDefaultOptions($pluginName, $options)
    {
        $form = new \Ip\Form();

        /* @var $form \Ip\Form */
        $form = Helper::pluginPropertiesFormFields($pluginName, $form);

        foreach ($options as $option) {
            if (empty($option['name'])) {
                continue;
            }

            $field = $form->getField($option['name']);
            if (!$field) {
                continue;
            }

            $optionKey = $pluginName . '.' . $option['name'];
            if (!is_null(ipGetOption($optionKey))) { // option already exists
                continue;
            }

            ipSetOption($pluginName . '.' . $option['name'], $field->getValue());

        }
    }

    public static function deactivatePlugin($pluginName)
    {
        $activePlugins = self::getActivePluginNames();
        if (!in_array($pluginName, $activePlugins)) {
            //already deactivated
            return true;
        }

        $pluginRecord = self::getPluginRecord($pluginName);
        if (!$pluginRecord) {
            //already deactivated
            return true;
        }

        $workerClass = 'Plugin\\' . $pluginName . '\\Setup\\Worker';
        if (class_exists($workerClass) && method_exists($workerClass, 'deactivate')) {
            $worker = new $workerClass($pluginRecord['version']);
            $worker->deactivate();
        }

        $sql = '
        UPDATE
            ' . ipTable('plugin') . '
        SET
            `isActive` = 0
        WHERE
            `name` = ?
        ';

        ipDb()->execute($sql, array($pluginName));

        ipLog()->info(
            'Ip.pluginDeactivated: {plugin} {version} deactivated.',
            array('plugin' => $pluginName, 'version' => $pluginRecord['version'])
        );

        // TODO remove plugin event listeners
        ipEvent(
            'ipPluginDeactivated',
            array(
                'name' => $pluginName,
                'version' => $pluginRecord['version'],
            )
        );
        return null;
    }

    public static function removePlugin($pluginName)
    {
        $activePlugins = self::getActivePluginNames();
        if (in_array($pluginName, $activePlugins)) {
            throw new \Ip\Exception\Plugin\Setup('Please deactivate the plugin before removing it.');

        }

        $pluginRecord = self::getPluginRecord($pluginName);
        if ($pluginRecord) {
            $version = $pluginRecord['version'];
        } else {
            $version = null;
        }

        $workerClass = 'Plugin\\' . $pluginName . '\\Setup\\Worker';
        if (method_exists($workerClass, 'remove')) {
            $worker = new $workerClass($version);
            $worker->remove();
        }

        $dbh = ipDb()->getConnection();
        $sql = '
        DELETE FROM
            ' . ipTable('plugin') . '
        WHERE
            `name` = :pluginName
        ';

        $params = array(
            'pluginName' => $pluginName
        );
        $q = $dbh->prepare($sql);
        $q->execute($params);

        $pluginDir = ipFile('Plugin/' . $pluginName);
        try {
            $result = Helper::removeDir($pluginDir);
            if (!$result) {
                throw new \Ip\Exception\Plugin\Setup('Can\'t remove folder ' . esc($pluginDir));
            }
        } catch (\Ip\PhpException $e) {
            throw new \Ip\Exception\Plugin\Setup('Can\'t remove folder ' . esc($pluginDir));
        }

        ipLog()->info(
            'Ip.pluginRemoved: {plugin} {version} removed.',
            array('plugin' => $pluginName, 'version' => $version)
        );

        ipEvent(
            'ipPluginRemoved',
            array(
                'name' => $pluginName,
                'version' => $version,
            )
        );

    }

    public static function getActivePlugins()
    {
        return ipDb()->selectAll('plugin', '*', array('isActive' => 1));
    }

    protected static function getPluginRecord($pluginName)
    {
        $dbh = ipDb()->getConnection();
        $sql = '
            SELECT
                *
            FROM
                ' . ipTable('plugin') . '
            WHERE
                `name` = :pluginName
        ';

        $params = array(
            'pluginName' => $pluginName
        );
        $q = $dbh->prepare($sql);
        $q->execute($params);
        $row = $q->fetch();
        return $row;
    }

    public static function getAllPluginNames()
    {
        $answer = array();
        $pluginDir = ipFile('Plugin/');
        $files = scandir($pluginDir);
        if (!$files) {
            return array();
        }
        foreach ($files as $file) {
            if (in_array($file, array('.', '..')) || !is_dir(
                    $pluginDir . $file
                ) || !empty($file[0]) && $file[0] == '.'
            ) {
                continue;
            }
            $answer[] = $file;

        }

        //TODO add filter for plugins in other directories
        return $answer;
    }

    public static function getActivePluginNames()
    {
        $dbh = ipDb()->getConnection();
        $sql = '
            SELECT
                `name`
            FROM
                ' . ipTable('plugin') . '
            WHERE
                `isActive` = 1
        ';

        $params = array();
        $q = $dbh->prepare($sql);
        $q->execute($params);
        $data = $q->fetchAll(\PDO::FETCH_COLUMN); //fetch all rows as an array
        return $data;
    }

    public static function getPluginConfig($pluginName)
    {
        $configFile = ipFile('Plugin/' . $pluginName . '/');
        $config = self::parseConfigFile($configFile);

        if (empty($config)) {
            $config = array(
                'name' => $pluginName,
                'version' => '1',
                'description' => '',
                'author' => '',
            );

        }

        return $config;
    }

    public static function parseConfigFile($pluginDir)
    {
        $configFile = $pluginDir . 'Setup/plugin.json';
        if (!is_file($configFile)) {
            return array();
        }

        $configJson = file_get_contents($configFile);

        $config = Helper::jsonCleanDecode($configJson, true);
        if ($config) {
            return $config;
        } else {
            return array();
        }
    }

    public static function marketUrl()
    {
        if (ipGetOption('Ip.disablePluginMarket', 0)) {
            return '';
        }

        $marketUrl = ipConfig()->get('pluginMarketUrl', 'http://market.impresspages.org/plugins-v1/');

        return $marketUrl;
    }

    public static function pluginInstallDir()
    {
        $themeDirs = Model::pluginDirs();
        return array_shift($themeDirs);
    }

    /**
     * first dir themes will override the themes from last ones
     * @return array
     */
    protected static function pluginDirs()
    {
        //the order of dirs is very important. First dir themes overrides following ones.

        $cleanDirs = array();

        $optionDirs = ipGetOption('Plugins.pluginDirs');
        if ($optionDirs) {
            $optionDirs = str_replace(array("\r\n", "\r"), "\n", $optionDirs);
            $lines = explode("\n", $optionDirs);
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $cleanDirs[] = trim($line);
                }
            }
            $cleanDirs = array_merge($cleanDirs, array(ipFile('Theme/')));
        } else {
            $cleanDirs = array(ipFile('Plugin/'));
        }

        return $cleanDirs;
    }


}


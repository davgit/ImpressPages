<?php
namespace Ip\Internal\Plugins;

use Ip\Response\JsonRpc;

class AdminController extends \Ip\Controller
{

    public function index()
    {
        ipAddJs('Ip/Internal/Core/assets/js/angular.js');
        ipAddJs('Ip/Internal/Plugins/assets/plugins.js');
        ipAddJs('Ip/Internal/Plugins/assets/jquery.pluginProperties.js');
        ipAddJs('Ip/Internal/Plugins/assets/pluginsLayout.js');

        $allPlugins = Model::getAllPluginNames();

        $plugins = array();
        foreach ($allPlugins as $pluginName) {
            $plugin = Helper::getPluginData($pluginName);
            $plugins[] = $plugin;
        }

        ipAddJsVariable('pluginList', $plugins);
        ipAddJsVariable('ipTranslationAreYouSure', __('Are you sure?', 'Ip-admin', false));

        $data = array();
        $view = ipView('view/layout.php', $data);

        ipResponse()->setLayoutVariable('removeAdminContentWrapper', true);

        return $view->render();
    }

    public function pluginPropertiesForm()
    {
        $pluginName = ipRequest()->getQuery('pluginName');
        if (!$pluginName) {
            throw new \Ip\Exception('Missing required parameters');
        }

        $variables = array(
            'plugin' => Helper::getPluginData($pluginName),
        );

        if (in_array($pluginName, Model::getActivePluginNames())) {
            $variables['form'] = Helper::pluginPropertiesForm($pluginName);
        }

        $layout = ipView('view/pluginProperties.php', $variables)->render();

        $data = array(
            'html' => $layout
        );
        return new \Ip\Response\Json($data);
    }

    public function activate()
    {
        $post = ipRequest()->getPost();
        if (empty($post['params']['pluginName'])) {
            throw new \Ip\Exception('Missing parameter');
        }
        $pluginName = $post['params']['pluginName'];

        try {
            Service::activatePlugin($pluginName);
        } catch (\Ip\Exception $e) {
            $answer = array(
                'jsonrpc' => '2.0',
                'error' => array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ),
                'id' => null,
            );

            return new \Ip\Response\Json($answer);
        }

        $answer = array(
            'jsonrpc' => '2.0',
            'result' => array(
                1
            ),
            'id' => null,
        );

        return new \Ip\Response\Json($answer);
    }

    public function deactivate()
    {
        $post = ipRequest()->getPost();
        if (empty($post['params']['pluginName'])) {
            throw new \Ip\Exception('Missing parameter');
        }
        $pluginName = $post['params']['pluginName'];

        try {
            Service::deactivatePlugin($pluginName);
        } catch (\Ip\Exception $e) {
            $answer = array(
                'jsonrpc' => '2.0',
                'error' => array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ),
                'id' => null,
            );

            return new \Ip\Response\Json($answer);
        }

        $answer = array(
            'jsonrpc' => '2.0',
            'result' => array(
                1
            ),
            'id' => null,
        );

        return new \Ip\Response\Json($answer);
    }

    public function remove()
    {
        $post = ipRequest()->getPost();
        if (empty($post['params']['pluginName'])) {
            throw new \Ip\Exception('Missing parameter');
        }
        $pluginName = $post['params']['pluginName'];

        try {
            Service::removePlugin($pluginName);
        } catch (\Ip\Exception $e) {
            $answer = array(
                'jsonrpc' => '2.0',
                'error' => array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ),
                'id' => null,
            );

            return new \Ip\Response\Json($answer);
        }

        $answer = array(
            'jsonrpc' => '2.0',
            'result' => array(
                1
            ),
            'id' => null,
        );

        return new \Ip\Response\Json($answer);
    }

    public function updatePlugin()
    {
        $pluginName = ipRequest()->getPost('pluginName');
        $data = ipRequest()->getPost();

        $result = Helper::savePluginOptions($pluginName, $data);

        if ($result === true) {
            return \Ip\Response\JsonRpc::result($result);
        } else {
            return \Ip\Response\JsonRpc::error(__('Validation failed', 'Ip-admin', false));
        }
    }

    public function market()
    {
        ipAddJs('Ip/Internal/Core/assets/js/jquery-ui/jquery-ui.js');
        ipAddCss('Ip/Internal/Core/assets/js/jquery-ui/jquery-ui.css');
        ipAddJs('Ip/Internal/Core/assets/js/easyXDM/easyXDM.min.js');

//        ipAddJs('Ip/Internal/Design/assets/options.js');
        ipAddJs('Ip/Internal/Plugins/assets/market.js');
//        ipAddJs('Ip/Internal/Design/assets/design.js');
//        ipAddJs('Ip/Internal/Design/assets/pluginInstall.js');
//        ipAddJs('Ip/Internal/System/assets/market.js');


//        $model = Model::instance();
//
//        $themes = $model->getAvailableThemes();
//
//        $model = Model::instance();
//        $theme = $model->getTheme(ipConfig()->theme());
//        $options = $theme->getOptionsAsArray();
//
//        $themePlugins = $model->getThemePlugins();
//        $installedPlugins = \Ip\Internal\Plugins\Service::getActivePluginNames();
//        $notInstalledPlugins = array();
//
//        //filter plugins that are already installed
//        foreach ($themePlugins as $plugin) {
//            if (!empty($plugin['name']) && (!in_array($plugin['name'], $installedPlugins) || !is_dir(ipFile('Plugin/' . $plugin['name'])))) {
//                $notInstalledPlugins[] = $plugin;
//            }
//        }
//
//
//        if (isset($_SESSION['module']['design']['pluginNote'])) {
//            $pluginNote = $_SESSION['module']['design']['pluginNote'];
//            unset($_SESSION['module']['design']['pluginNote']);
//        } else {
//            $pluginNote = '';
//        }
//
        $data = array(
//            'pluginNote' => $pluginNote,
//            'theme' => $model->getTheme(ipConfig()->theme()),
//            'plugins' => $notInstalledPlugins,
//            'availableThemes' => $themes,
            'marketUrl' => Model::marketUrl(),
//            'showConfiguration' => !empty($options),
//            'contentManagementUrl' => ipConfig()->baseUrl() . '?aa=Content.index',
//            'contentManagementText' => __('Manage content', 'Ip-admin', false)
        );


        $contentView = ipView('view/market.php', $data);

        ipResponse()->setLayoutVariable('removeAdminContentWrapper', true);

        return $contentView->render();
    }

    public function downloadPlugins()
    {
        ipRequest()->mustBePost();
        $plugins = ipRequest()->getPost('plugins');

        if (!is_writable(Model::pluginInstallDir())) {
            return JsonRpc::error(
                __(
                    'Directory is not writable. Please check your email and install the plugin manually.',
                    'Ip-admin',
                    false
                ),
                777
            );
        }

        try {
            if (!is_array($plugins)) {
                return JsonRpc::error(__('Download failed: invalid parameters', 'Ip-admin', false), 101);
            }

            if (function_exists('set_time_limit')) {
                set_time_limit(count($plugins) * 180 + 30);
            }

            $pluginDownloader = new PluginDownloader();

            foreach ($plugins as $plugin) {
                if (!empty($plugin['url']) && !empty($plugin['name']) && !empty($plugin['signature'])) {
                    $pluginDownloader->downloadPlugin($plugin['name'], $plugin['url'], $plugin['signature']);
                }
            }
        } catch (\Ip\Exception $e) {
            return JsonRpc::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return JsonRpc::error(__('Unknown error. Please see logs.', 'Ip-admin', false), 987);
        }

        return JsonRpc::result(array('plugins' => $plugins));
    }


}

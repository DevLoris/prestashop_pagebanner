<?php
/**
 * Flaveo Page Banner
 *
 * @author    Loris Pinna <https://lorispinna.com>
 * @copyright Loris Pinna
 * @license   Proprietary
 */

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/FlaveoPageBannerItem.php';

class Flaveo_Pagebanner extends Module implements WidgetInterface
{
    const ADMIN_CONTROLLER = 'AdminFlaveoPageBanner';

    /** @var bool Avoid rendering the banners twice (hook + widget) on the same page */
    protected static $rendered = false;

    public function __construct()
    {
        $this->name = 'flaveo_pagebanner';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Loris Pinna';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Flaveo - Bandeaux haut de page');
        $this->description = $this->l('Affiche des bandeaux d\'information programmables en haut des pages (fixes ou défilants, couleurs personnalisables).');
        $this->confirmUninstall = $this->l('Tous les bandeaux seront supprimés. Continuer ?');
    }

    /* ------------------------------------------------------------------ */
    /* Install / uninstall                                                */
    /* ------------------------------------------------------------------ */

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && $this->registerHook('displayAfterBodyOpeningTag')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallDb()
            && parent::uninstall();
    }

    protected function installDb()
    {
        $engine = _MYSQL_ENGINE_;
        $prefix = _DB_PREFIX_ . FlaveoPageBannerItem::TABLE;

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}` (
                `id_flaveo_pagebanner` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `date_start` DATETIME NULL DEFAULT NULL,
                `date_end` DATETIME NULL DEFAULT NULL,
                `bg_color` VARCHAR(32) NOT NULL DEFAULT '#000000',
                `text_color` VARCHAR(32) NOT NULL DEFAULT '#ffffff',
                `marquee` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `marquee_speed` INT(10) UNSIGNED NOT NULL DEFAULT 60,
                `closable` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                `cookie_days` INT(10) UNSIGNED NOT NULL DEFAULT 1,
                `cookie_key` VARCHAR(32) NOT NULL DEFAULT '',
                `position` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_flaveo_pagebanner`),
                KEY `active_dates` (`active`, `date_start`, `date_end`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$prefix}_lang` (
                `id_flaveo_pagebanner` INT(10) UNSIGNED NOT NULL,
                `id_lang` INT(10) UNSIGNED NOT NULL,
                `message` TEXT NULL,
                `link` VARCHAR(512) NULL DEFAULT NULL,
                PRIMARY KEY (`id_flaveo_pagebanner`, `id_lang`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($queries as $sql) {
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallDb()
    {
        $prefix = _DB_PREFIX_ . FlaveoPageBannerItem::TABLE;

        return Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}_lang`")
            && Db::getInstance()->execute("DROP TABLE IF EXISTS `{$prefix}`");
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName(self::ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = 1;
        // "Apparence / Design" menu
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentThemes');
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = $lang['iso_code'] === 'fr' ? 'Bandeaux haut de page' : 'Page banners';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if (!$idTab) {
            return true;
        }

        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }

    /**
     * "Configure" button -> banners list.
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::ADMIN_CONTROLLER));
    }

    /* ------------------------------------------------------------------ */
    /* Front                                                              */
    /* ------------------------------------------------------------------ */

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'module-' . $this->name . '-style',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 200]
        );

        $this->context->controller->registerJavascript(
            'module-' . $this->name . '-js',
            'modules/' . $this->name . '/views/js/front.js',
            ['position' => 'bottom', 'priority' => 200]
        );
    }

    public function hookDisplayAfterBodyOpeningTag($params)
    {
        return $this->renderWidget('displayAfterBodyOpeningTag', $params);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->active || self::$rendered) {
            return '';
        }

        $vars = $this->getWidgetVariables($hookName, $configuration);
        if (empty($vars['flaveo_banners'])) {
            return '';
        }

        self::$rendered = true;
        $this->smarty->assign($vars);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/pagebanner.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $idLang = (int) $this->context->language->id;
        $banners = [];

        foreach (FlaveoPageBannerItem::getCurrentBanners($idLang) as $row) {
            $cookie = 'flaveo_pb_' . (int) $row['id_flaveo_pagebanner'] . '_' . $row['cookie_key'];

            if ($row['closable'] && isset($_COOKIE[$cookie])) {
                continue;
            }

            $banners[] = [
                'id' => (int) $row['id_flaveo_pagebanner'],
                'message' => $row['message'],
                'link' => $row['link'],
                'bg_color' => $row['bg_color'],
                'text_color' => $row['text_color'],
                'marquee' => (bool) $row['marquee'],
                'marquee_speed' => max(10, (int) $row['marquee_speed']),
                'closable' => (bool) $row['closable'],
                'cookie' => $cookie,
                'cookie_days' => (int) $row['cookie_days'],
            ];
        }

        return ['flaveo_banners' => $banners];
    }
}

<?php

namespace Bigfoot\Bundle\ImportBundle\Listener;

use Bigfoot\Bundle\CoreBundle\Event\MenuEvent;
use Bigfoot\Bundle\CoreBundle\Theme\Menu\Item;

/**
 * Class MenuListener
 * @package Bigfoot\Bundle\ImportBundle\Listener
 */
class MenuListener
{
    /**
     * Add entry to the sidebar menu
     *
     * @param MenuEvent $event
     */
    function onMenuGenerate(MenuEvent $event)
    {
        $menu = $event->getMenu();

        if ($menu->getName() == 'sidebar_menu') {
            $importMenu = new Item('sidebar_settings_import', 'Imports');
            $importMenu->addChild(new Item('sidebar_settings_import', 'Datasources','admin_datasource'));
            $menu->addItem($importMenu);
        }
    }
}



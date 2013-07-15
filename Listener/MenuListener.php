<?php

namespace Bigfoot\Bundle\ImportBundle\Listener;

use Bigfoot\Bundle\CoreBundle\Event\MenuEvent;
use Bigfoot\Bundle\CoreBundle\Theme\Menu\Item;

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
            $menu->addItem(new Item('sidebar_settings_import', 'Import Settings','admin_datasource'));
        }

    }
}



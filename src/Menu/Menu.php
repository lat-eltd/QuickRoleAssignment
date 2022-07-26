<?php

namespace srag\Plugins\QuickRoleAssignment\Menu;

use ILIAS\GlobalScreen\Scope\MainMenu\Factory\AbstractBaseItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticPluginMainMenuProvider;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Standard;
use ilQuickRoleAssignmentGUI;
use ilUIPluginRouterGUI;

class Menu extends AbstractStaticPluginMainMenuProvider
{

    public function getStaticTopItems() : array
    {
        return [
            $this->symbol($this->mainmenu->topLinkItem($this->if->identifier($this->plugin->getId() . "_top"))
                ->withTitle($this->plugin->txt("plugin_title"))
                ->withAction($this->dic->ctrl()->getLinkTargetByClass([
                    ilUIPluginRouterGUI::class,
                    ilQuickRoleAssignmentGUI::class
                ])))
                ->withAvailableCallable(function () : bool {
                    return $this->plugin->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return $this->plugin->getAccessManager()->hasCurrentUserViewPermission();
                })
        ];
    }


    public function getStaticSubItems() : array
    {
        return [];
    }


    protected function symbol(AbstractBaseItem $entry) : AbstractBaseItem
    {
        if (method_exists($entry, "withSymbol")) {
            $entry = $entry->withSymbol($this->dic->ui()->factory()->symbol()->icon()->standard(Standard::ROLF, $this->plugin->getPluginName())->withIsOutlined(true));
        }

        return $entry;
    }
}

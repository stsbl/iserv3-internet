<?php

declare(strict_types=1);

namespace Stsbl\InternetBundle\EventListener;

use IServ\CoreBundle\Event\MenuEvent;
use IServ\CoreBundle\EventListener\MainMenuListenerInterface;
use IServ\CoreBundle\Menu\MenuBuilder;
use IServ\Library\Config\Config;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of MenuListener
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licneses/MIT>
 */
final class MenuListener implements MainMenuListenerInterface
{

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function onBuildMainMenu(MenuEvent $event): void
    {
        // disable internet module if activation is disabled
        if (!$this->config->get('Activation')) {
            return;
        }

        $menu = $event->getMenu(MenuBuilder::GROUP_NETWORK);

        $item = $menu->addChild('internet', [
                'route' => 'internet_index',
                'label' => _('Internet'),
            ]);

        $item->setExtra('icon', 'world-plug');
    }

}

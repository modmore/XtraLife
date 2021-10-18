<?php
/**
 * @var xPDOTransport $transport
 * @var array $options
 */

if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UPGRADE:
        case xPDOTransport::ACTION_INSTALL:
            $modx->addExtensionPackage('xtralife', '[[++core_path]]components/xtralife/model/');

            break;

        case xPDOTransport::ACTION_UNINSTALL:
            $modx->removeExtensionPackage('xtralife');

            break;
    }
}

return true;

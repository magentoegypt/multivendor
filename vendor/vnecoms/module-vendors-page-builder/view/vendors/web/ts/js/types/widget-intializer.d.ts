/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

interface WidgetIntializerConfigInterface {
    currentViewport: any;
    config: any;
    breakpoints: any;
}

declare function WidgetInitializer(data: WidgetIntializerConfigInterface, contextElement?: HTMLElement): void;

declare module "Vnecoms_VendorsPageBuilder/js/widget-initializer" {
    export = WidgetInitializer;
}

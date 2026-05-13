/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

import BaseMaster from "./master";

export default class MasterCollection extends BaseMaster {
    /**
     * Retrieve the child template
     *
     * @returns {string}
     */
    get masterTemplate(): string {
        return "Vnecoms_VendorsPageBuilder/content-type/master-collection";
    }
}

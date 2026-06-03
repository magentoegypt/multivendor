# -*- coding: utf-8 -*-
from odoo import fields, models


class ProductTemplate(models.Model):
    _inherit = 'product.template'

    # Magento-origin enrichment fields written by the Magento connector's M->O push
    # (MagentoEgypt\OdooConnector\Model\Product\ProductPushMapper). Defined here so the
    # addon creates the columns on install/upgrade — they were previously Studio fields
    # and did not survive the Odoo 16->19 migration. Names keep the x_ prefix so existing
    # data and both sync sides keep working unchanged.
    x_magento_visibility = fields.Integer(
        string='Magento Visibility',
        help='Magento catalog visibility (1=Not Visible, 2=Catalog, 3=Search, 4=Catalog+Search).',
    )
    x_magento_special_price = fields.Float(string='Magento Special Price')
    x_magento_attributes = fields.Text(
        string='Magento Attributes (JSON)',
        help='Curated Magento product attributes (color, material, brand, ...) as a JSON object.',
    )

# -*- coding: utf-8 -*-
from odoo import fields, models


class SaleOrder(models.Model):
    _inherit = 'sale.order'

    # Written by the Magento connector's order push (OrderPusher); x_magento_tracking
    # is also read back by the O->M outbox payload.
    x_magento_status = fields.Char(string='Magento Status')
    x_magento_shipping_method = fields.Char(string='Magento Shipping Method')
    x_magento_payment_method = fields.Char(string='Magento Payment Method')
    x_magento_discount_amount = fields.Float(string='Magento Discount Amount')
    x_magento_tracking = fields.Char(string='Magento Tracking')

# -*- coding: utf-8 -*-
from odoo import fields, models


class ResPartner(models.Model):
    _inherit = 'res.partner'

    # Written by the Magento connector's customer push (CustomerPusher).
    x_magento_customer_group = fields.Char(string='Magento Customer Group')

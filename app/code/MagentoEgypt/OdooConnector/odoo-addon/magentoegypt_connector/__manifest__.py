# -*- coding: utf-8 -*-
{
    'name': 'MagentoEgypt Odoo Connector',
    'version': '19.0.1.5.0',
    'summary': 'Pushes Odoo changes (products, customers, orders) to the Magento '
               'MagentoEgypt_OdooConnector inbound endpoint, HMAC-signed.',
    'description': """
MagentoEgypt Odoo Connector (Odoo -> Magento push side)
========================================================
Companion to the Magento module ``MagentoEgypt_OdooConnector``.

* An outbox model (``magentoegypt.sync.outbox``) queues changes.
* Automated actions enqueue product.template / res.partner / sale.order
  create+write events (skipping changes that originated from Magento, tagged
  with the ``magento_sync`` context).
* A scheduled action drains the outbox, HMAC-signing each envelope and POSTing
  it to ``<magento>/odooconnector/inbound/receive``.

Configure System Parameters:
* ``magentoegypt.magento_url``   e.g. https://brassandwood.org
* ``magentoegypt.hmac_secret``   must equal the Magento config 'Inbound Shared Secret'
* ``magentoegypt.enabled``       '1' to enable pushing
""",
    'author': 'MagentoEgypt',
    'category': 'Connector',
    'license': 'OPL-1',
    'depends': ['base', 'product', 'sale', 'stock'],
    'data': [
        'security/ir.model.access.csv',
        'data/automated_actions.xml',
        'data/ir_cron.xml',
    ],
    'installable': True,
    'application': False,
}

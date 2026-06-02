# -*- coding: utf-8 -*-
import hashlib
import hmac
import json
import logging

try:
    import requests
except ImportError:  # pragma: no cover
    requests = None

from odoo import api, fields, models

_logger = logging.getLogger(__name__)

PARAM_URL = 'magentoegypt.magento_url'
PARAM_SECRET = 'magentoegypt.hmac_secret'
PARAM_ENABLED = 'magentoegypt.enabled'
INBOUND_PATH = '/odooconnector/inbound/receive'


class MagentoSyncOutbox(models.Model):
    """Outbox of Odoo->Magento changes, drained by cron as HMAC-signed POSTs."""

    _name = 'magentoegypt.sync.outbox'
    _description = 'MagentoEgypt Connector Outbox (Odoo to Magento)'
    _order = 'id asc'

    entity_type = fields.Char(required=True, index=True,
                              help='Magento entity type: product / customer_buyer / order')
    odoo_model = fields.Char(required=True)
    res_id = fields.Integer(required=True, index=True)
    natural_key = fields.Char(index=True)
    operation = fields.Char(default='update')
    payload = fields.Text()
    checksum = fields.Char()
    correlation_id = fields.Char()
    state = fields.Selection(
        [('pending', 'Pending'), ('done', 'Done'), ('failed', 'Failed')],
        default='pending', index=True)
    attempts = fields.Integer(default=0)
    last_error = fields.Text()

    # ------------------------------------------------------------------
    # Enqueue — invoked by the automated actions on create/write.
    # ------------------------------------------------------------------
    @api.model
    def enqueue(self, entity_type, records):
        # Skip changes that originated from Magento (M->O writes carry the
        # 'magento_sync' context) so we never echo Magento's own writes back.
        if self.env.context.get('magento_sync'):
            return
        if self.env['ir.config_parameter'].sudo().get_param(PARAM_ENABLED, '0') != '1':
            return
        for record in records:
            natural_key, payload = self._build_payload(entity_type, record)
            if not natural_key:
                continue
            self.sudo().create({
                'entity_type': entity_type,
                'odoo_model': record._name,
                'res_id': record.id,
                'natural_key': natural_key,
                'operation': 'update',
                'payload': json.dumps(payload, sort_keys=True, separators=(',', ':')),
                'checksum': self._checksum(payload),
                'correlation_id': 'odoo-%s-%s' % (entity_type, record.id),
            })

    def _build_payload(self, entity_type, record):
        if entity_type == 'product':
            image = record.image_1920
            return (record.default_code or '', {
                'name': record.name or '',
                'price': float(record.list_price or 0.0),
                'description': record.description_sale or '',
                # Magento status: 1=enabled / 2=disabled (mirrors sale_ok).
                'status': 1 if record.sale_ok else 2,
                'visibility': int(record.x_magento_visibility or 4),
                'special_price': float(record.x_magento_special_price or 0.0),
                # base64 image (string) for O->M image sync; '' when none.
                'image': image.decode('ascii') if image else '',
                # primary category name -> Magento find/create + assign.
                'categories': [record.categ_id.name] if record.categ_id else [],
            })
        if entity_type == 'customer_buyer':
            return ((record.email or '').strip().lower(), {
                'name': record.name or '',
                'email': record.email or '',
            })
        if entity_type == 'order':
            return (record.client_order_ref or record.name or '', {
                'state': record.state or '',
                'amount_total': float(record.amount_total or 0.0),
                'tracking': record.x_magento_tracking or '',
            })
        if entity_type == 'inventory_source_item':
            # record is a stock.quant; Magento natural key is default:<sku>.
            sku = record.product_id.default_code or ''
            if not sku:
                return ('', {})
            return ('default:' + sku, {
                'qty': float(record.quantity or 0.0),
            })
        return ('', {})

    @staticmethod
    def _checksum(payload):
        return hashlib.sha256(
            json.dumps(payload, sort_keys=True, separators=(',', ':')).encode('utf-8')
        ).hexdigest()

    # ------------------------------------------------------------------
    # Cron — drain pending rows: HMAC-sign + POST to Magento.
    # ------------------------------------------------------------------
    @api.model
    def _cron_drain(self, limit=50):
        icp = self.env['ir.config_parameter'].sudo()
        base_url = (icp.get_param(PARAM_URL) or '').rstrip('/')
        secret = icp.get_param(PARAM_SECRET) or ''
        if not base_url or not secret:
            _logger.warning('magentoegypt_connector: %s / %s not configured', PARAM_URL, PARAM_SECRET)
            return
        if requests is None:
            _logger.error('magentoegypt_connector: python "requests" not available')
            return

        endpoint = base_url + INBOUND_PATH
        rows = self.sudo().search([('state', '=', 'pending')], limit=limit)
        for row in rows:
            envelope = {
                'entity_type': row.entity_type,
                'operation': row.operation or 'update',
                'natural_key': row.natural_key or '',
                'odoo_id': row.res_id,
                'payload': json.loads(row.payload or '{}'),
                'checksum': row.checksum or '',
                'correlation_id': row.correlation_id or '',
            }
            body = json.dumps(envelope, separators=(',', ':'))
            signature = hmac.new(secret.encode('utf-8'), body.encode('utf-8'), hashlib.sha256).hexdigest()
            try:
                resp = requests.post(
                    endpoint,
                    data=body,
                    headers={'Content-Type': 'application/json', 'X-Odoo-Signature': signature},
                    timeout=30,
                )
                if resp.status_code == 200:
                    row.write({'state': 'done', 'last_error': False})
                else:
                    row.write({
                        'state': 'failed',
                        'attempts': row.attempts + 1,
                        'last_error': 'HTTP %s: %s' % (resp.status_code, (resp.text or '')[:500]),
                    })
            except Exception as exc:  # noqa: BLE001
                row.write({
                    'state': 'failed',
                    'attempts': row.attempts + 1,
                    'last_error': str(exc)[:500],
                })

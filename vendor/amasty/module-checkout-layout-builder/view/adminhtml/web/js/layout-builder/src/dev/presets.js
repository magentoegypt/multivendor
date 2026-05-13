export default amcheckoutPresets = {
    "classic": {
        "2columns": {
            "frontendColumns": 2,
            "columnsWidth": [
                1,
                1
            ],
            "axis": "both",
            "cols": 2,
            "layout": [
                {
                    "i": "shipping_address",
                    "title": "Shipping Address",
                    "x": 0,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "shipping_method",
                    "title": "Shipping Method",
                    "x": 0,
                    "y": 1,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "delivery",
                    "title": "Delivery",
                    "x": 0,
                    "y": 2,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "payment_method",
                    "title": "Payment Method",
                    "x": 1,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "summary",
                    "title": "Order Summary",
                    "x": 1,
                    "y": 1,
                    "w": 1,
                    "h": 1
                }
            ]
        },
        "3columns": {
            "frontendColumns": 3,
            "columnsWidth": [
                1,
                1,
                1
            ],
            "axis": "both",
            "cols": 3,
            "layout": [
                {
                    "i": "shipping_address",
                    "title": "Shipping Address",
                    "x": 0,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "shipping_method",
                    "title": "Shipping Method",
                    "x": 1,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "delivery",
                    "title": "Delivery",
                    "x": 1,
                    "y": 2,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "payment_method",
                    "title": "Payment Method",
                    "x": 1,
                    "y": 3,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "summary",
                    "title": "Order Summary",
                    "x": 2,
                    "y": 0,
                    "w": 1,
                    "h": 1
                }
            ]
        }
    },
    "modern": {
        "1column": {
            "frontendColumns": 1,
            "columnsWidth": [
                1
            ],
            "axis": "both",
            "cols": 1,
            "layout": [
                {
                    "i": "shipping_address",
                    "title": "Shipping Address",
                    "x": 0,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "shipping_method",
                    "title": "Shipping Method",
                    "x": 0,
                    "y": 1,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "delivery",
                    "title": "Delivery",
                    "x": 0,
                    "y": 2,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "payment_method",
                    "title": "Payment Method",
                    "x": 0,
                    "y": 3,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "summary",
                    "title": "Order Summary",
                    "x": 0,
                    "y": 4,
                    "w": 1,
                    "h": 1
                }
            ]
        },
        "2columns": {
            "frontendColumns": 2,
            "columnsWidth": [
                2,
                1
            ],
            "axis": "y",
            "cols": 3,
            "layout": [
                {
                    "i": "shipping_address",
                    "title": "Shipping Address",
                    "x": 0,
                    "y": 0,
                    "w": 2,
                    "h": 1
                },
                {
                    "i": "shipping_method",
                    "title": "Shipping Method",
                    "x": 0,
                    "y": 1,
                    "w": 2,
                    "h": 1
                },
                {
                    "i": "delivery",
                    "title": "Delivery",
                    "x": 0,
                    "y": 2,
                    "w": 2,
                    "h": 1
                },
                {
                    "i": "payment_method",
                    "title": "Payment Method",
                    "x": 0,
                    "y": 3,
                    "w": 2,
                    "h": 1
                },
                {
                    "i": "summary",
                    "title": "Order Summary",
                    "x": 2,
                    "y": 0,
                    "w": 1,
                    "h": 1,
                    "static": true,
                    "axis": "x"
                }
            ]
        },
        "3columns": {
            "frontendColumns": 3,
            "columnsWidth": [
                1,
                1,
                1
            ],
            "axis": "both",
            "cols": 3,
            "layout": [
                {
                    "i": "shipping_address",
                    "title": "Shipping Address",
                    "x": 0,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "shipping_method",
                    "title": "Shipping Method",
                    "x": 1,
                    "y": 0,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "delivery",
                    "title": "Delivery",
                    "x": 1,
                    "y": 2,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "payment_method",
                    "title": "Payment Method",
                    "x": 1,
                    "y": 3,
                    "w": 1,
                    "h": 1
                },
                {
                    "i": "summary",
                    "title": "Order Summary",
                    "x": 2,
                    "y": 0,
                    "w": 1,
                    "h": 1
                }
            ]
        }
    }
}

export default amcheckoutFrontendConfig = [
    [
        {
            "name": "shipping_method",
            "title": "Shipping Method"
        },
        {
            "name": "shipping_address", "title": "Shipping Address"
        },
        {
            "name": "delivery",
            "title": "Delivery"
        },
        {
            "name": "payment_method", "title": "Payment Method"
        }
    ],
    [
        {"name": "summary", "title": "Order Summary"}
    ]
]

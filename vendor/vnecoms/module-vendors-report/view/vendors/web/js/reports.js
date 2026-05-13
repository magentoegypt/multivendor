/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Catalog/js/price-utils',
    'domReady!'
], function (Component, $, alert, utils) {
    'use strict';

    return Component.extend({
        defaults: {
            graphs: [],
            graph_objects: [],
            report_url: '',
            timer: null,
            priceFormat: {},
            defaultPeriod: 'day',
            showPeriod: true,
            graphTypes:[],
            defaultGraphType: 'lineChart'
        },
        initialize: function () {
            this._super()
            .observe({
                selectedPeriod: 'day',                      /* Current Period*/
                selectedDateRangeType: '',                  /* Current date range type*/
                dateRangeFrom: '',                          /* Current from date*/
                dateRangeTo: '',                            /* Current to date*/
                selectedGraphType: this.defaultGraphType,   /* Current selected graph type (Line, Area, Pies, Donut...)*/
                selectedDateRangeTitle: '',                     /* The title of date range selector*/
                showPeriodSelector: true,                   /* Show Period Selector (day, month, quarter, year)*/
                showDateSelectorMenu: false,                /* Show the date selector dropdown menu*/
                showCustomDate: false,                      /* Show the custom date input*/
            });
            
            this.initDateSelectorEvents();
            
            if (this.date_range) {
                this.selectedDateRangeType(this.date_range.type);
                this.setDateRange(this.date_range.value);
                this.selectedDateRangeTitle(this.date_range.title);
            }
            /*Bind this to a variable to calling it from tabs*/
            window.vnecoms_reports = this;
            
            this.selectedPeriod(this.defaultPeriod);
            this.selectedGraphType(this.defaultGraphType);
            this.showPeriodSelector(this.showPeriod);
            
            /*Draw graph*/
            this.drawGraph();
        },
        
        /*Close the date selector when click to any where in window*/
        initDateSelectorEvents: function () {
            var self = this;
            $(window).click(function (event) {
                window.testtest = event;
                var elements = [
                    '#calendar-controller',
                    '#calendar-container .custom-date',
                    '.calendar-footer'
                ];
                
                var target = $(event.target);
                var containers = [
                    ".ui-datepicker",
                    "#calendar-title",
                    ".custom-date-range",
                    '.ui-datepicker-header'
                ];
                containers.each(function (container) {
                    $(container).find('*').each(function (index, elm) {
                        var selector = container+" "+elm.tagName.toLowerCase();
                        if (elements.indexOf(selector) === -1) {
                            elements.push(selector);
                        }
                    });
                    elements.push(container);
                });
                
                if (!event.target.matches(elements.join())) {
                    self.showDateSelectorMenu(false);
                    self.showCustomDate(false);
                }
                
                if (!event.target.matches(elements.join())) {
                    self.showDateSelectorMenu(false);
                    self.showCustomDate(false);
                }
            });
        },
        /**
         * Set Date range for the report
         */
        setDateRange(range){
            var ranges = range.split('_');
            if (ranges.size() != 2) {
                alert({
                    modalClass: 'confirm ves-error',
                    title: 'Error',
                    content: 'The date range is not valid',
                });
            }
            this.dateRangeFrom(ranges[0]);
            this.dateRangeTo(ranges[1]);
        },
        /**
         * Get the current selected date range
         */
        selectedDateRange: function () {
            return this.dateRangeFrom()+ '_' + this.dateRangeTo();
        },
        /**
         * When click to the date range button
         */
        calendarTitleClick: function (data, event) {
            this.showDateSelectorMenu(!this.showDateSelectorMenu());
            this.showCustomDate(false);
        },
        /**
         * When click to custom date item
         */
        customDateClick: function (data, event) {
            this.showCustomDate(true);
        },
        /**
         * Click to apply custom date button
         */
        applyCustomDate: function (data, event) {
            this.selectedDateRangeType('custom');
            var title = $.mage.__('Custom: %1 - %2');
            var from = new Date(this.dateRangeFrom());
            var to = new Date(this.dateRangeTo());
            this.selectedDateRangeTitle(
                title.replace('%1',from.toLocaleDateString()).replace('%2',to.toLocaleDateString())
            );
            this.loadDataAndDraw();
        },
        
        /**
         * Change date range
         */
        changeDateRange: function (data, event) {
            var self = this;
            this.selectedDateRangeType($(event.target).data('date-type'));
            this.setDateRange($(event.target).data('date-value'));
            this.selectedDateRangeTitle($(event.target).data('date-title'));
            this.loadDataAndDraw();
        },
        
        /**
         * Load data for selected date range and draw graphs
         */
        loadDataAndDraw:function () {
            var self = this;
            if (typeof(self.graphs_data[self.selectedDateRange()]) == 'undefined') {
                $('body').trigger('processStart');
                $.ajax({
                      url: self.report_url,
                      method: "POST",
                      data: { date : self.selectedDateRange(), type: self.selectedDateRangeType()},
                      dataType: "json"
                }).done(function ( response ) {
                    $('body').trigger('processStop');
                    if (response.ajaxExpired) {
                        window.location = response.ajaxRedirect;
                        return;
                    }
                    console.log(response.report_data);
                    self.graphs_data[self.selectedDateRange()] = response.report_data;
                    self.drawGraph();
                });
            } else {
                this.drawGraph();
            }
        },
        drawGraph: function (graphType) {
            /*If the google graph API is not loaded, just wait*/
            if (!window.READY_FOR_DRAWING) {
                this.waitGoogleGraphApiAndDrawGraph();
                $('body').trigger('processStart');
                return;
            }
            
            $('body').trigger('processStop');
            
            var self = this;
            var graphs = this.graphs;
            
            graphs.each(function (graph) {
                /*var graph = graphs[g_index];*/
                if (graphType && graphType != graph.type) {
return; }
                /*Get the data based in the selected date range, current grap, selected period*/
                var data = self.graphs_data[self.selectedDateRange()][graph.data_type][self.selectedPeriod()];
                var graphData = [];
                data.each(function (row) {
                    var rowData = [];
                    graph.columns.each(function (column) {
                        var columnData = row[column.name];
                        switch (column.type[self.selectedPeriod()]) {
                            case 'number':
                                columnData = typeof(column.value) != 'undefined'?
                                    {v: parseFloat(row[column.value]), f: row[column.name]}:
                                    parseFloat(row[column.name]);
                                break;
                            case 'date':
                            case 'datetime':
                                columnData = typeof(column.value) != 'undefined'?
                                        {v: new Date(row[column.value]), f: row[column.name]}:
                                            new Date(row[column.name]);
                                break;
                            case 'price':
                                columnData = {v:parseFloat(row[column.name]),   f: utils.formatPrice(row[column.name], self.priceFormat)}
                                break;
                        }
                                            
                        rowData.push(columnData); /*If the column data is a number just parse it to float type.*/
                    });
                    graphData.push(rowData);
                });

                var googleGraphData = new google.visualization.DataTable();;
                /*Add columns*/
                graph.columns.each(function (column) {
                    var columnType = column.type[self.selectedPeriod()];
                    googleGraphData.addColumn(columnType == 'price'?'number':columnType, column.title);
                });
                
                /*Add Data to graph data table*/
                googleGraphData.addRows(graphData);
                if (graph.graph_type == 'lineChart' ||
                        graph.graph_type == 'columnChart' ||
                        graph.graph_type == 'areaChart' ||
                        graph.graph_type == 'pieChart' ||
                        graph.graph_type == 'donutChart' ||
                        graph.graph_type == '3dPieChart'
                ) {
                    var currentGraphType = self.selectedGraphType();
                    var legendPosition = ['lineChart','columnChart','areaChart','barChart'].indexOf(currentGraphType)!== -1?'bottom':'right';
                    var options = {
                      /*title: 'Company Performance',*/
                      legend: { position: legendPosition },
                      chartArea: {left: 100, right: 100},
                      width: $('#'+graph.type).width(),
                      height: 450
                    };
                    options.vAxis = graph.vAxitFormat == 'price'?
                        {format: self.priceFormat.pattern.replace("%s","#")}:
                        {format: graph.vAxitFormat};

                    if (currentGraphType == '3dPieChart') {
                        options.is3D = true;
                    } else if (currentGraphType == 'donutChart') {
                        options.pieHole = 0.4;
                    } else if (currentGraphType == 'pieChart') {
                    }
                    /*If the instance is not exist just create new instance for the graph*/

                    if (typeof(self.graph_objects[graph.type]) !='undefined') {
                        self.graph_objects[graph.type].clearChart();
                    }
                    switch (currentGraphType) {
                        case 'lineChart':
                            self.graph_objects[graph.type] = new google.visualization.LineChart(document.getElementById(graph.type));
                            break;
                        case 'columnChart':
                            self.graph_objects[graph.type] = new google.visualization.ColumnChart(document.getElementById(graph.type));
                            break;
                        case 'barChart':
                            self.graph_objects[graph.type] = new google.visualization.BarChart(document.getElementById(graph.type));
                            break;
                        case 'areaChart':
                            self.graph_objects[graph.type] = new google.visualization.AreaChart(document.getElementById(graph.type));
                            break;
                        case 'pieChart':
                        case 'donutChart':
                        case '3dPieChart':
                            self.graph_objects[graph.type] = new google.visualization.PieChart(document.getElementById(graph.type));
                            break;
                    }
                } else if (graph.graph_type == 'tableChart') {
                    console.log(graph.page);
                    var options = {
                        showRowNumber: false,
                        sortColumn: 0,
                        sortAscending: false, /*Descending*/
                        sort: typeof(graph.sort !='undefined')?graph.sort:'enabled',
                        width: '100%',
                        height: '100%'
                    };
                    if (typeof(graph.page !='undefined') && graph.page) {
                        options.page = graph.page;
                        options.pageSize = typeof(graph.page_size !='undefined')?graph.page_size:30;
                    }
                    
                    
                    if (typeof(self.graph_objects[graph.type]) =='undefined') {
                        self.graph_objects[graph.type] = new google.visualization.Table(document.getElementById(graph.type));
                    } else {
                        self.graph_objects[graph.type].clearChart();
                    }
                    
                    $('.csv-button').off().on('click', function () {
                        var csvColumns;
                        var csvContent;
                        var downloadLink;

                        // build column headings
                        csvColumns = '';
                        for (var i = 0; i < googleGraphData.getNumberOfColumns(); i++) {
                            csvColumns += googleGraphData.getColumnLabel(i);
                            if (i < (googleGraphData.getNumberOfColumns() - 1)) {
                                csvColumns += ',';
                            }
                        }
                        csvColumns += '\n';

                        // get data rows
                        csvContent = csvColumns + google.visualization.dataTableToCsv(googleGraphData);

                        downloadLink = document.createElement('a');
                        downloadLink.href = 'data:text/csv;charset=utf-8,' + encodeURI(csvContent);
                        downloadLink.download = 'data.csv';
                        $("#toolbar_div").append(downloadLink);
                        downloadLink.click();
                        $("#toolbar_div").remove("a");
                    });

                }
                
                self.graph_objects[graph.type].draw(googleGraphData, options);
            });
        },
        
        /* Can show graph type selector*/
        showGraphTypes: function () {
            return this.graphTypes.length;
        },
        
        onPeriodChange: function () {
            this.drawGraph();
        },
        onGraphTypeChange: function () {
            this.drawGraph();
        },
        waitGoogleGraphApiAndDrawGraph: function () {
            var self = this;
            if (!window.READY_FOR_DRAWING) {
                this.timer = setTimeout(function () {
                    self.waitGoogleGraphApiAndDrawGraph();
                    console.log('Waiting for google API');
                }, 500);
            } else {
                clearTimeout(this.timer);
                this.drawGraph();
            }
        }
    });
});


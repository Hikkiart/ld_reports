(function($) {
    'use strict';

    const LDAnalyticsWidgets = {
        
        init: function() {
            this.widgets = $('.ld-analytics-widget:not(.ld-analytics-filters)');
            this.filterWidget = $('.ld-analytics-filters');
            this.dataTables = {}; // Armazena instâncias do DataTable

            // Se o widget de filtros existir, inicializa-o.
            // A inicialização dos filtros irá então despoletar o carregamento dos outros widgets.
            if (this.filterWidget.length) {
                this.initFilters();
            } else {
                // Se não houver filtros na página, carrega os widgets com dados padrão.
                this.loadAllWidgets({});
            }
        },

        /**
         * Inicializa o widget de filtros.
         * Esta função é agora o ponto de partida para tudo.
         */
        initFilters: function() {
            const self = this;
            const $courseSelect = self.filterWidget.find('.ld-course-filter');
            
            // Define as datas padrão para os últimos 30 dias.
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - 30);
            self.filterWidget.find('.ld-date-start').val(startDate.toISOString().split('T')[0]);
            self.filterWidget.find('.ld-date-end').val(endDate.toISOString().split('T')[0]);

            // Adiciona o listener para o botão de aplicar.
            self.filterWidget.find('#ld-apply-filters').on('click', function() {
                self.applyFilters();
            });

            // A MÁGICA ACONTECE AQUI:
            // Busca a lista de cursos e SÓ DEPOIS de a ter, aplica os filtros pela primeira vez.
            // Isto garante que os widgets não são carregados antes de a página estar pronta.
            this.populateCourseFilter($courseSelect).done(function() {
                // Agora que os cursos estão carregados, podemos carregar os dados dos widgets.
                self.applyFilters();
            });
        },
        
        /**
         * Preenche o seletor de cursos.
         * Retorna um objeto Deferred do jQuery para que possamos saber quando termina.
         */
        populateCourseFilter: function($selectElement) {
            return $.ajax({
                url: ldAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'ld_analytics_get_courses',
                    nonce: ldAnalytics.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $.each(response.data, function(id, title) {
                            $selectElement.append(new Option(title, id));
                        });
                    }
                }
            });
        },

        /**
         * Recolhe os valores dos filtros e despoleta o recarregamento dos widgets.
         */
        applyFilters: function() {
            const filters = {
                start_date: this.filterWidget.find('.ld-date-start').val(),
                end_date: this.filterWidget.find('.ld-date-end').val(),
                course_id: this.filterWidget.find('.ld-course-filter').val(),
            };
            this.loadAllWidgets(filters);
        },

        /**
         * Itera sobre todos os widgets de dados e pede para carregar os seus dados.
         */
        loadAllWidgets: function(filters) {
            this.widgets.each((index, el) => {
                const $widget = $(el);
                $widget.addClass('is-loading');
                this.loadWidgetData($widget, filters);
            });
        },

        /**
         * Faz a chamada AJAX para buscar os dados de um widget específico.
         */
        loadWidgetData: function($widget, filters) {
            const widgetType = $widget.data('widget-type');
            const metric = $widget.data('metric');

            $.ajax({
                url: ldAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'ld_analytics_get_widget_data',
                    nonce: ldAnalytics.nonce,
                    widget_type: widgetType,
                    metric: metric,
                    filters: filters
                },
                success: (response) => {
                    if (response.success && this.renderers[widgetType]) {
                        this.renderers[widgetType]($widget, response.data);
                    } else {
                        console.error('Erro no Widget:', response.data.message);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error('Erro AJAX:', textStatus, errorThrown);
                },
                complete: () => {
                    $widget.removeClass('is-loading');
                }
            });
        },

        /**
         * Objeto com funções para renderizar os dados em cada tipo de widget.
         */
        renderers: {
            kpi_card: function($widget, data) {
                $widget.find('.ld-kpi-label').text(data.label);
                let value = data.value;
                if (data.is_percentage) {
                    value = `${parseFloat(value).toFixed(1)}%`;
                } else {
                    value = parseInt(value, 10).toLocaleString('pt-BR');
                }
                $widget.find('.ld-kpi-value').text(value);
            },
            
            line_chart: function($widget, data) {
                const canvas = $widget.find('canvas')[0];
                if (!canvas) return;
                if (canvas.chartInstance) canvas.chartInstance.destroy();
                canvas.chartInstance = new Chart(canvas.getContext('2d'), { type: 'line', data: data, options: { responsive: true, maintainAspectRatio: false }});
            },

            bar_chart: function($widget, data) {
                const canvas = $widget.find('canvas')[0];
                if (!canvas) return;
                if (canvas.chartInstance) canvas.chartInstance.destroy();
                canvas.chartInstance = new Chart(canvas.getContext('2d'), { type: 'bar', data: data, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y' }});
            },
            
            data_table: function($widget, data) {
                const tableId = $widget.find('table').attr('id');
                if (!tableId) return;

                if ($.fn.DataTable.isDataTable('#' + tableId)) {
                    LDAnalyticsWidgets.dataTables[tableId].clear().rows.add(data).draw();
                } else {
                    LDAnalyticsWidgets.dataTables[tableId] = $('#' + tableId).DataTable({
                        data: data,
                        columns: [
                            { data: 'display_name', title: 'Nome' },
                            { data: 'user_email', title: 'Email' },
                            { data: 'course_count', title: 'Cursos Inscritos' },
                            { data: 'last_activity', title: 'Última Atividade' }
                        ],
                        responsive: true,
                        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' }
                    });
                }
            }
        }
    };

    $(document).ready(() => LDAnalyticsWidgets.init());
    $(window).on('elementor/frontend/init', () => {
        elementorFrontend.hooks.addAction('frontend/elementor_ready/global', () => LDAnalyticsWidgets.init());
    });

})(jQuery);

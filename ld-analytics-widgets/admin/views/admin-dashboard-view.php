<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap ld-analytics-admin-wrap">
    <h1><?php echo esc_html__( 'LD Analytics Widgets - Catálogo de Widgets', 'ld-analytics-widgets' ); ?></h1>
    <p class="ld-admin-intro">
        <?php echo esc_html__( 'Bem-vindo! Use os widgets abaixo em qualquer página com o Elementor para construir seu dashboard de análises personalizado.', 'ld-analytics-widgets' ); ?>
    </p>

    <div class="ld-widget-grid">

        <!-- Preview do Widget de Filtros -->
        <div class="ld-widget-preview-card">
            <div class="ld-widget-preview-header">
                <h2><?php echo esc_html__( 'Widget de Filtros', 'ld-analytics-widgets' ); ?></h2>
                <span class="ld-widget-name">ld_filters</span>
            </div>
            <div class="ld-widget-preview-content filter-preview">
                <div class="filter-group">
                    <label>Período</label>
                    <div class="date-inputs">
                        <input type="text" value="2025-06-21" readonly />
                        <span>-</span>
                        <input type="text" value="2025-07-21" readonly />
                    </div>
                </div>
                <div class="filter-group">
                    <label>Curso</label>
                    <select disabled><option>Todos os Cursos</option></select>
                </div>
                <button type="button" class="button-primary" disabled>Aplicar</button>
            </div>
        </div>

        <!-- Preview do KPI Card -->
        <div class="ld-widget-preview-card">
            <div class="ld-widget-preview-header">
                <h2><?php echo esc_html__( 'Cartão de KPI', 'ld-analytics-widgets' ); ?></h2>
                <span class="ld-widget-name">ld_kpi_card</span>
            </div>
            <div class="ld-widget-preview-content kpi-preview">
                <p class="kpi-label">Alunos Ativos</p>
                <p class="kpi-value">1,250</p>
            </div>
        </div>

        <!-- Preview do Gráfico de Linhas -->
        <div class="ld-widget-preview-card large">
            <div class="ld-widget-preview-header">
                <h2><?php echo esc_html__( 'Gráfico de Linhas', 'ld-analytics-widgets' ); ?></h2>
                <span class="ld-widget-name">ld_line_chart</span>
            </div>
            <div class="ld-widget-preview-content">
                <img src="https://placehold.co/600x300/E2E8F0/4A5568?text=Gráfico+de+Linhas" alt="Preview de Gráfico de Linhas" />
            </div>
        </div>

        <!-- Preview do Gráfico de Barras -->
        <div class="ld-widget-preview-card large">
            <div class="ld-widget-preview-header">
                <h2><?php echo esc_html__( 'Gráfico de Barras', 'ld-analytics-widgets' ); ?></h2>
                <span class="ld-widget-name">ld_bar_chart</span>
            </div>
            <div class="ld-widget-preview-content">
                <img src="https://placehold.co/600x300/E2E8F0/4A5568?text=Gráfico+de+Barras" alt="Preview de Gráfico de Barras" />
            </div>
        </div>
        
        <!-- Preview da Tabela de Dados -->
        <div class="ld-widget-preview-card full-width">
            <div class="ld-widget-preview-header">
                <h2><?php echo esc_html__( 'Tabela de Dados', 'ld-analytics-widgets' ); ?></h2>
                <span class="ld-widget-name">ld_data_table</span>
            </div>
            <div class="ld-widget-preview-content">
                 <img src="https://placehold.co/1200x400/E2E8F0/4A5568?text=Tabela+de+Dados+com+Busca+e+Paginação" alt="Preview de Tabela de Dados" />
            </div>
        </div>

    </div>
</div>
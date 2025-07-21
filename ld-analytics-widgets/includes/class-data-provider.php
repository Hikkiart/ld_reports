<?php
namespace LD_Analytics_Widgets;

// Sair se acedido diretamente.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Classe Provedora de Dados.
 * Busca, processa e formata todos os dados do LearnDash com consultas SQL reais.
 */
class Data_Provider {

    /**
     * Busca dados de KPI.
     */
    public static function get_kpi_data( $metric, $filters = [] ) {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        
        $value = 0;
        $label = '';
        
        // Aplica filtros de data e curso
        $date_query_completed = self::get_date_where_clause($filters, 'activity_completed');
        $date_query_started = self::get_date_where_clause($filters, 'activity_started');
        $course_query = self::get_course_where_clause($filters, 'course_id');

        switch ( $metric ) {
            case 'active_students':
                $label = __( 'Alunos Ativos', 'ld-analytics-widgets' );
                $value = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$activity_table} WHERE 1=1 {$date_query_started} {$course_query}" );
                break;
            
            // NOVA MÉTRICA IMPLEMENTADA AQUI
            case 'course_completions':
                $label = __( 'Total de Conclusões de Curso', 'ld-analytics-widgets' );
                // Esta query conta as atividades de conclusão de curso, respeitando os filtros.
                $value = $wpdb->get_var( "SELECT COUNT(activity_id) FROM {$activity_table} WHERE activity_type = 'course' AND activity_status = 1 {$date_query_completed} {$course_query}" );
                break;

            case 'completion_rate':
                $label = __( 'Taxa de Conclusão Média', 'ld-analytics-widgets' );
                // Usamos a data de início para os inscritos e a data de conclusão para os finalizados
                $enrollments = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$activity_table} WHERE activity_type = 'course' {$date_query_started} {$course_query}" );
                $completions = self::get_kpi_data('course_completions', $filters)['value'];
                $value = ( $enrollments > 0 ) ? ( $completions / $enrollments ) * 100 : 0;
                break;
        }

        return [
            'label' => $label,
            'value' => $value,
            'is_percentage' => in_array($metric, ['completion_rate']),
        ];
    }

    // --- O resto do ficheiro permanece o mesmo ---

    /**
     * Busca dados para gráficos de linha.
     */
    public static function get_line_chart_data( $metric, $filters = [] ) {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        
        $date_query = self::get_date_where_clause($filters, 'activity_started');
        $course_query = self::get_course_where_clause($filters, 'course_id');

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(activity_started) as date, COUNT(activity_id) as count 
                 FROM {$activity_table} 
                 WHERE activity_type = 'course' %s %s
                 GROUP BY DATE(activity_started)
                 ORDER BY date ASC",
                $date_query, $course_query
            ),
            ARRAY_A
        );

        $start_date = new \DateTime($filters['start_date'] ?? '-30 days');
        $end_date = new \DateTime($filters['end_date'] ?? 'today');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

        $labels = [];
        $data_points = [];
        $db_data = array_column($results, 'count', 'date');

        foreach ($period as $date) {
            $formatted_date = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $data_points[] = $db_data[$formatted_date] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => __( 'Novas Inscrições', 'ld-analytics-widgets' ),
                'data' => $data_points,
                'borderColor' => '#3B82F6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'fill' => true,
                'tension' => 0.4,
            ]]
        ];
    }

    /**
     * Busca dados para gráficos de barra.
     */
    public static function get_bar_chart_data( $metric, $filters = [] ) {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        $posts_table = $wpdb->prefix . 'posts';

        $date_query = self::get_date_where_clause($filters, 'ua.activity_started');
        $course_query = self::get_course_where_clause($filters, 'ua.course_id');

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.post_title, COUNT(ua.user_id) as count 
                 FROM {$activity_table} ua
                 JOIN {$posts_table} p ON ua.course_id = p.ID
                 WHERE ua.activity_type = 'course' %s %s
                 GROUP BY ua.course_id
                 ORDER BY count DESC
                 LIMIT 5",
                $date_query, $course_query
            ),
            ARRAY_A
        );

        return [
            'labels' => array_column($results, 'post_title'),
            'datasets' => [[
                'label' => __( 'Inscrições', 'ld-analytics-widgets' ),
                'data' => array_column($results, 'count'),
                'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
            ]]
        ];
    }

    /**
     * Busca dados para tabelas.
     */
    public static function get_table_data( $metric, $filters = [] ) {
        global $wpdb;
        $users_table = $wpdb->prefix . 'users';
        $activity_table = $wpdb->prefix . 'learndash_user_activity';
        
        $date_query = self::get_date_where_clause($filters, 'ua.activity_started');
        $course_query = self::get_course_where_clause($filters, 'ua.course_id');

        $sql = "SELECT u.ID, u.display_name, u.user_email, MAX(ua.activity_started) as last_activity
                FROM {$users_table} u
                JOIN {$activity_table} ua ON u.ID = ua.user_id
                WHERE 1=1 {$date_query} {$course_query}
                GROUP BY u.ID
                ORDER BY last_activity DESC";

        $results = $wpdb->get_results($sql, ARRAY_A);
        
        foreach ($results as $key => $row) {
            $results[$key]['course_count'] = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(DISTINCT course_id) FROM {$activity_table} WHERE user_id = %d AND activity_type = 'course'", $row['ID'])
            );
        }

        return $results;
    }
    
    /**
     * Busca todos os cursos publicados para usar em filtros.
     */
    public static function get_all_courses() {
        $courses = get_posts([
            'post_type' => 'sfwd-courses',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'id=>parent',
        ]);
        
        $options = [];
        foreach ($courses as $id => $post) {
            $options[$id] = get_the_title($id);
        }
        return $options;
    }

    // --- Funções Auxiliares ---
    private static function get_date_where_clause($filters, $column_name) {
        global $wpdb;
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;

        if ($start_date && $end_date) {
            return $wpdb->prepare(
                " AND DATE({$column_name}) BETWEEN %s AND %s",
                $start_date,
                $end_date
            );
        }
        return '';
    }

    private static function get_course_where_clause($filters, $column_name) {
        global $wpdb;
        $course_id = $filters['course_id'] ?? null;

        if ($course_id && $course_id !== 'all') {
            return $wpdb->prepare(" AND {$column_name} = %d", $course_id);
        }
        return '';
    }
}

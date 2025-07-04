<?php
/**
 * Raporlama fonksiyonları - Güncel Hali
 */

// Raporlama için AJAX geri çağırım fonksiyonları
function generate_fitness_pdf_callback() {
    // Güvenlik kontrolü
    if (!wp_verify_nonce($_POST['nonce'], 'fitness_calculator_nonce')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız. Sayfayı yenileyin.',
            'code' => 'security_error'
        ));
        return;
    }
    
    // POST verilerini al ve doğrula
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    
    if (!$data) {
        wp_send_json_error(array(
            'message' => 'Veri eksik veya geçersiz format',
            'code' => 'invalid_data'
        ));
        return;
    }
    
    // Veri doğrulama - minimum gereksinimler
    if (!isset($data['calculation_type']) || 
        (!isset($data['bodyfat_percentage']) && !isset($data['bmr']))) {
        wp_send_json_error(array(
            'message' => 'Hesaplama verileri eksik. Lütfen önce hesaplama yapın.',
            'code' => 'missing_calculation_data'
        ));
        return;
    }
    
    // PDF oluşturma fonksiyonu çağır
    if (function_exists('generate_fitness_pdf')) {
        $pdf_file = generate_fitness_pdf($data);
        
        if ($pdf_file) {
            wp_send_json_success(array(
                'message' => 'PDF raporu başarıyla oluşturuldu',
                'file_url' => $pdf_file,
                'download_ready' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'PDF raporu oluşturulamadı. Lütfen tekrar deneyin.',
                'code' => 'pdf_generation_failed'
            ));
        }
    } else {
        wp_send_json_error(array(
            'message' => 'PDF oluşturma sistemi kullanılamıyor',
            'code' => 'pdf_function_missing'
        ));
    }
    
    wp_die();
}

function send_fitness_email_callback() {
    // Güvenlik kontrolü
    if (!wp_verify_nonce($_POST['nonce'], 'fitness_calculator_nonce')) {
        wp_send_json_error(array(
            'message' => 'Güvenlik kontrolü başarısız. Sayfayı yenileyin.',
            'code' => 'security_error'
        ));
        return;
    }
    
    // POST verilerini al ve doğrula
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    
    // E-posta doğrulama
    if (empty($email)) {
        wp_send_json_error(array(
            'message' => 'E-posta adresi girilmedi',
            'code' => 'email_missing'
        ));
        return;
    }
    
    if (!is_email($email)) {
        wp_send_json_error(array(
            'message' => 'Geçersiz e-posta adresi formatı',
            'code' => 'email_invalid'
        ));
        return;
    }
    
    // Veri doğrulama
    if (!$data) {
        wp_send_json_error(array(
            'message' => 'Rapor verileri eksik',
            'code' => 'data_missing'
        ));
        return;
    }
    
    // Hesaplama verilerinin varlığını kontrol et
    if (!isset($data['calculation_type']) || 
        (!isset($data['bodyfat_percentage']) && !isset($data['bmr']))) {
        wp_send_json_error(array(
            'message' => 'Hesaplama verileri eksik. Lütfen önce hesaplama yapın.',
            'code' => 'calculation_data_missing'
        ));
        return;
    }
    
    // Rate limiting - aynı e-postaya çok sık gönderim kontrolü
    $last_send_time = get_transient('fitness_email_sent_' . md5($email));
    if ($last_send_time && (time() - $last_send_time) < 60) { // 1 dakika
        wp_send_json_error(array(
            'message' => 'Bu e-posta adresine çok sık rapor gönderiyorsunuz. Lütfen 1 dakika bekleyin.',
            'code' => 'rate_limit_exceeded'
        ));
        return;
    }
    
    // E-posta gönderme fonksiyonu çağır
    if (function_exists('send_fitness_report_email')) {
        $sent = send_fitness_report_email($email, $data);
        
        if ($sent) {
            // Başarılı gönderim kaydı
            set_transient('fitness_email_sent_' . md5($email), time(), 300); // 5 dakika cache
            
            wp_send_json_success(array(
                'message' => 'Rapor başarıyla e-posta olarak gönderildi',
                'email_sent' => true,
                'recipient' => $email
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'E-posta gönderilirken bir hata oluştu. Lütfen tekrar deneyin.',
                'code' => 'email_send_failed'
            ));
        }
    } else {
        wp_send_json_error(array(
            'message' => 'E-posta sistemi kullanılamıyor',
            'code' => 'email_function_missing'
        ));
    }
    
    wp_die();
}

/**
 * Rapor verilerini doğrula
 */
function validate_report_data($data) {
    $errors = array();
    
    // Temel alanları kontrol et
    if (!isset($data['calculation_type'])) {
        $errors[] = 'Hesaplama türü belirtilmemiş';
    }
    
    // Kişisel bilgileri kontrol et
    if (!isset($data['age']) || $data['age'] < 18 || $data['age'] > 100) {
        $errors[] = 'Geçersiz yaş değeri';
    }
    
    if (!isset($data['height']) || $data['height'] < 140 || $data['height'] > 220) {
        $errors[] = 'Geçersiz boy değeri';
    }
    
    if (!isset($data['weight']) || $data['weight'] < 30 || $data['weight'] > 200) {
        $errors[] = 'Geçersiz kilo değeri';
    }
    
    // Vücut yağ hesaplaması varsa kontrol et
    if (isset($data['bodyfat_percentage'])) {
        if ($data['bodyfat_percentage'] < 3 || $data['bodyfat_percentage'] > 50) {
            $errors[] = 'Geçersiz vücut yağ oranı';
        }
    }
    
    // Fitness hesaplaması varsa kontrol et
    if (isset($data['bmr'])) {
        if ($data['bmr'] < 800 || $data['bmr'] > 3000) {
            $errors[] = 'Geçersiz BMR değeri';
        }
    }
    
    return $errors;
}

/**
 * Rapor istatistikleri kaydet
 */
function log_report_stats($type, $success = true) {
    $stats = get_option('fitness_calculator_stats', array(
        'pdf_generated' => 0,
        'emails_sent' => 0,
        'total_calculations' => 0,
        'last_updated' => time()
    ));
    
    if ($type === 'pdf' && $success) {
        $stats['pdf_generated']++;
    } elseif ($type === 'email' && $success) {
        $stats['emails_sent']++;
    } elseif ($type === 'calculation') {
        $stats['total_calculations']++;
    }
    
    $stats['last_updated'] = time();
    update_option('fitness_calculator_stats', $stats);
}

/**
 * Rapor istatistiklerini getir
 */
function get_report_stats() {
    return get_option('fitness_calculator_stats', array(
        'pdf_generated' => 0,
        'emails_sent' => 0,
        'total_calculations' => 0,
        'last_updated' => 0
    ));
}

/**
 * Geçici dosyaları temizle (cronjob için)
 */
function cleanup_temp_reports() {
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/fitness-reports';
    
    if (!is_dir($pdf_dir)) {
        return;
    }
    
    $files = glob($pdf_dir . '/*.pdf');
    $current_time = time();
    $cleanup_threshold = 24 * 60 * 60; // 24 saat
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $file_time = filemtime($file);
            if (($current_time - $file_time) > $cleanup_threshold) {
                unlink($file);
            }
        }
    }
}

// Günlük temizlik için cronjob ekle
if (!wp_next_scheduled('fitness_calculator_cleanup')) {
    wp_schedule_event(time(), 'daily', 'fitness_calculator_cleanup');
}

add_action('fitness_calculator_cleanup', 'cleanup_temp_reports');

/**
 * Debug için log fonksiyonu
 */
function fitness_calculator_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Fitness Calculator ' . strtoupper($level) . '] ' . $message);
    }
}
?>
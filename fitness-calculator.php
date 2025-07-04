<?php
/*
Plugin Name: Kalori Hesaplayıcı
Description: Geliştirilmiş BMR ve besin değeri hesaplama eklentisi
Version: 2.3
Author: Katkılı Gıda
Author URI: https://katkiligida.com
*/

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Admin ayarlarını dahil et
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';

// Raporlama ve görselleştirme dosyalarını dahil et
require_once plugin_dir_path(__FILE__) . 'includes/reporting.php';
require_once plugin_dir_path(__FILE__) . 'includes/pdf-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/email-sender.php';
require_once plugin_dir_path(__FILE__) . 'includes/visualization.php';

// Global değişken - shortcode kullanım durumunu takip eder
global $fitness_calculator_loaded;
$fitness_calculator_loaded = false;

// AJAX endpoint'leri tanımla
function register_fitness_calculator_ajax() {
    add_action('wp_ajax_generate_fitness_pdf', 'generate_fitness_pdf_callback');
    add_action('wp_ajax_nopriv_generate_fitness_pdf', 'generate_fitness_pdf_callback');
    
    add_action('wp_ajax_send_fitness_email', 'send_fitness_email_callback');
    add_action('wp_ajax_nopriv_send_fitness_email', 'send_fitness_email_callback');
}
add_action('init', 'register_fitness_calculator_ajax');

// Sadece shortcode kullanıldığında script ve stilleri yükle
function fitness_calculator_conditional_enqueue() {
    global $fitness_calculator_loaded;
    
    // Shortcode kullanılmışsa script'leri yükle
    if ($fitness_calculator_loaded) {
        // Temel CSS
        wp_enqueue_style('fitness-calculator-style', plugins_url('css/style.css', __FILE__), array(), '2.3');
        
        // Temel JavaScript
        wp_enqueue_script('fitness-calculator-script', plugins_url('js/script.js', __FILE__), array('jquery'), '2.3', true);
        
        // Raporlama JavaScript
        wp_enqueue_script('fitness-calculator-reporting', plugins_url('js/reporting.js', __FILE__), array('jquery'), '2.3', true);
        
        // PDF oluşturma için gerekli kütüphane
        wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', array(), '1.4.1', true);
        
        // Dashicons sadece gerekirse
        wp_enqueue_style('dashicons');
        
        // Ayarları JavaScript'e aktar
        $settings = array(
            'bmrFormula' => get_option('fitness_calculator_bmr_formula', 'katch_mcardle'),
            'weightUnit' => get_option('fitness_calculator_weight_unit', 'kg'),
            'heightUnit' => get_option('fitness_calculator_height_unit', 'cm'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => plugins_url('', __FILE__),
            'nonce' => wp_create_nonce('fitness_calculator_nonce')
        );
        
        wp_localize_script('fitness-calculator-script', 'fitnessCalculatorSettings', $settings);
        wp_localize_script('fitness-calculator-reporting', 'fitnessCalculatorSettings', $settings);
    }
}

// Sayfa içeriğini kontrol et ve shortcode varsa script'leri hazırla
function check_for_fitness_shortcodes() {
    global $post, $fitness_calculator_loaded;
    
    if (is_a($post, 'WP_Post')) {
        // Shortcode'ları kontrol et
        if (has_shortcode($post->post_content, 'fitness_calculator') || 
            has_shortcode($post->post_content, 'bodyfat_calculator')) {
            $fitness_calculator_loaded = true;
            
            // Script'leri footer'da yükle
            add_action('wp_footer', 'fitness_calculator_conditional_enqueue', 5);
        }
    }
}
add_action('wp', 'check_for_fitness_shortcodes');

// Admin paneli için ayrı script yükleme
function fitness_calculator_admin_scripts($hook) {
    // Sadece eklenti admin sayfasında yükle
    if ($hook != 'toplevel_page_fitness-calculator') {
        return;
    }
    
    wp_enqueue_style('fitness-calculator-admin', plugins_url('css/admin.css', __FILE__), array(), '2.3');
    wp_enqueue_script('fitness-calculator-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '2.3', true);
}
add_action('admin_enqueue_scripts', 'fitness_calculator_admin_scripts');

// Add menu item to WordPress admin
function fitness_calculator_admin_menu() {
    add_menu_page(
        'Fitness Calculator',
        'Fitness Calculator',
        'manage_options',
        'fitness-calculator',
        'fitness_calculator_admin_page',
        'dashicons-calculator'
    );
}
add_action('admin_menu', 'fitness_calculator_admin_menu');

// Create shortcode - optimized version
function fitness_calculator_shortcode() {
    global $fitness_calculator_loaded;
    $fitness_calculator_loaded = true;
    
    ob_start();
    ?>
    <div class="fitness-calculator-container">
        <div class="calculator-header">
            <h2>Fitness Hesaplama Aracı</h2>
            <p>Vücut değerlerinizi girin ve günlük kalori ihtiyacınızı hesaplayın</p>
        </div>
        
        <div class="calculator-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="basic-info">Temel Bilgiler</button>
                <button class="tab-btn" data-tab="results">Sonuçlar</button>
            </div>
            
            <div class="tab-content active" id="basic-info">
                <form id="fitness-calculator-form">
                    <div class="form-row">
                        <div class="input-group">
                            <label>Cinsiyet</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="gender" value="female" checked>
                                    <span class="radio-text">Kadın</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="gender" value="male">
                                    <span class="radio-text">Erkek</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="age">Yaş</label>
                            <input type="number" id="age" min="18" max="100" value="30">
                        </div>
                        
                        <div class="input-group">
                            <label for="height">Boy (cm)</label>
                            <input type="number" id="height" min="140" max="220" value="170">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="weight">Vücut Ağırlığı (kg)</label>
                            <div class="range-container">
                                <input type="range" id="weight" min="30" max="200" value="70">
                                <div class="range-value"><span id="weight-value">70</span> kg</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="fat">Vücut Yağ Oranı (%)</label>
                            <div class="range-container">
                                <input type="range" id="fat" min="5" max="50" value="20">
                                <div class="range-value"><span id="fat-value">20</span>%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="activity-level">Aktivite Düzeyi</label>
                            <select id="activity-level">
                                <option value="1.2">Sedanter (Masa başı iş, egzersiz yok)</option>
                                <option value="1.375">Hafif Aktif (Haftada 1-3 gün egzersiz)</option>
                                <option value="1.55" selected>Orta Aktif (Haftada 3-5 gün egzersiz)</option>
                                <option value="1.725">Çok Aktif (Haftada 6-7 gün egzersiz)</option>
                                <option value="1.9">Ekstra Aktif (Ağır fiziksel iş/günde 2 antrenman)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="goal">Amacınız</label>
                            <select id="goal">
                                <option value="maintain">Kilo Koruma</option>
                                <option value="lose" selected>Yağ Kaybetme</option>
                                <option value="gain">Kas Kazanma</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="calculate-btn" class="calculate-button">
                            Hesapla <span class="dashicons dashicons-chart-area"></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="tab-content" id="results">
                <div class="results-container">
                    <div class="results-summary">
                        <div class="result-card primary">
                            <h3>Günlük Kalori İhtiyacı</h3>
                            <div class="result-value" id="tdee-result">0</div>
                            <div class="result-unit">kcal/gün</div>
                        </div>
                        
                        <div class="result-card secondary">
                            <h3>Hedef Kalori</h3>
                            <div class="result-value" id="target-calories">0</div>
                            <div class="result-unit">kcal/gün</div>
                        </div>
                        
                        <div class="result-card tertiary">
                            <h3>Bazal Metabolizma Hızı (BMR)</h3>
                            <div class="result-value" id="bmr-result">0</div>
                            <div class="result-unit">kcal/gün</div>
                        </div>
                        
                        <div class="result-card bmi">
                            <h3>Vücut Kitle İndeksi (BMI)</h3>
                            <div class="result-value" id="bmi-result">0</div>
                            <div class="result-unit">kg/m²</div>
                            <div class="result-category" id="bmi-category">-</div>
                        </div>
                    </div>
                    
                    <div class="macros-container">
                        <h3>Makro Besin İhtiyaçları</h3>
                        <div class="macros-grid">
                            <div class="macro-item">
                                <div class="macro-icon"><span class="dashicons dashicons-performance"></span></div>
                                <div class="macro-info">
                                    <div class="macro-name">Protein</div>
                                    <div class="macro-value" id="protein-result">0 gr/gün</div>
                                </div>
                            </div>
                            
                            <div class="macro-item">
                                <div class="macro-icon"><span class="dashicons dashicons-chart-pie"></span></div>
                                <div class="macro-info">
                                    <div class="macro-name">Yağ</div>
                                    <div class="macro-value" id="fat-result">0 gr/gün</div>
                                </div>
                            </div>
                            
                            <div class="macro-item">
                                <div class="macro-icon"><span class="dashicons dashicons-carrot"></span></div>
                                <div class="macro-info">
                                    <div class="macro-name">Karbonhidrat</div>
                                    <div class="macro-value" id="carb-result">0 gr/gün</div>
                                </div>
                            </div>
                            
                            <div class="macro-item">
                                <div class="macro-icon"><span class="dashicons dashicons-water"></span></div>
                                <div class="macro-info">
                                    <div class="macro-name">Su</div>
                                    <div class="macro-value" id="water-result">0 litre/gün</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ideal-weight-container">
                        <h3>İdeal Kilo Analizi</h3>
                        <div class="ideal-weight-grid">
                            <div class="ideal-weight-item">
                                <div class="ideal-weight-icon"><span class="dashicons dashicons-marker"></span></div>
                                <div class="ideal-weight-info">
                                    <div class="ideal-weight-name">İdeal Kilo Aralığı</div>
                                    <div class="ideal-weight-value" id="ideal-weight-range">0 - 0 kg</div>
                                </div>
                            </div>
                            
                            <div class="ideal-weight-item">
                                <div class="ideal-weight-icon"><span class="dashicons dashicons-chart-line"></span></div>
                                <div class="ideal-weight-info">
                                    <div class="ideal-weight-name">Hedef Kilo Farkı</div>
                                    <div class="ideal-weight-value" id="weight-difference">0 kg</div>
                                </div>
                            </div>
                            
                            <div class="ideal-weight-item full-width">
                                <div class="ideal-weight-icon"><span class="dashicons dashicons-info"></span></div>
                                <div class="ideal-weight-info">
                                    <div class="ideal-weight-name">Öneriler</div>
                                    <div class="ideal-weight-value" id="weight-recommendations">Hesaplama yapın</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="back-to-form" class="back-button">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Bilgileri Düzenle
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="show-visualization" class="reporting-button" style="display: none;">
                            <span class="dashicons dashicons-chart-bar"></span> Rapor ve Görselleştirme
                        </button>
                    </div>
                    
                    <div id="visualization-container" style="display: none;">
                        <div class="visualization-header">
                            <h3>Görselleştirme ve Raporlama</h3>
                        </div>
                        
                        <div class="visualization-body">
                            <div id="visualization-content"></div>
                            
                            <div class="sharing-options">
                                <h4>Sonuçlarınızı Paylaşın</h4>
                                
                                <div class="export-options">
                                    <button id="download-pdf" class="pdf-button">
                                        <span class="dashicons dashicons-pdf"></span> PDF Raporu İndir
                                    </button>
                                    
                                    <div class="email-form">
                                        <input type="email" id="report-email" placeholder="E-posta Adresiniz">
                                        <button id="send-email" class="email-button">
                                            <span class="dashicons dashicons-email"></span> E-posta ile Gönder
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="social-sharing">
                                    <h5>Sosyal Medyada Paylaş</h5>
                                    <div class="social-buttons">
                                        <button id="share-facebook" class="social-btn facebook">
                                            <span class="dashicons dashicons-facebook"></span> Facebook
                                        </button>
                                        <button id="share-twitter" class="social-btn twitter">
                                            <span class="dashicons dashicons-twitter"></span> Twitter
                                        </button>
                                        <button id="share-whatsapp" class="social-btn whatsapp">
                                            <span class="dashicons dashicons-whatsapp"></span> WhatsApp
                                        </button>
                                        <button id="copy-link" class="social-btn copy">
                                            <span class="dashicons dashicons-admin-links"></span> Linki Kopyala
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('fitness_calculator', 'fitness_calculator_shortcode');

// Vücut Yağ Oranı Hesaplama Shortcode'u - düzeltilmiş
function bodyfat_calculator_shortcode() {
    global $fitness_calculator_loaded;
    $fitness_calculator_loaded = true;
    
    ob_start();
    ?>
    <div class="bodyfat-calculator-container">
        <div class="calculator-header">
            <h2>Vücut Yağ Oranı Hesaplama</h2>
            <p>Vücut ölçülerinizi girerek yağ oranınızı hesaplayın</p>
        </div>
        
        <div class="calculator-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="measurement-input">Ölçü Girişi</button>
                <button class="tab-btn" data-tab="bodyfat-results">Sonuçlar</button>
            </div>
            
            <div class="tab-content active" id="measurement-input">
                <form id="bodyfat-calculator-form">
                    <div class="form-row">
                        <div class="input-group">
                            <label>Cinsiyet</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="bf-gender" value="female" checked>
                                    <span class="radio-text">Kadın</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="bf-gender" value="male">
                                    <span class="radio-text">Erkek</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="bf-age">Yaş</label>
                            <input type="number" id="bf-age" min="18" max="100" value="30">
                        </div>
                        
                        <div class="input-group">
                            <label for="bf-height">Boy (cm)</label>
                            <input type="number" id="bf-height" min="140" max="220" value="170">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="bf-weight">Vücut Ağırlığı (kg)</label>
                            <input type="number" id="bf-weight" min="30" max="200" value="70" step="0.1">
                        </div>
                        
                        <div class="input-group">
                            <label for="bf-neck">Boyun Çevresi (cm)</label>
                            <input type="number" id="bf-neck" min="20" max="60" value="35" step="0.1">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="bf-waist">Bel Çevresi (cm)</label>
                            <input type="number" id="bf-waist" min="50" max="150" value="80" step="0.1">
                        </div>
                        
                        <div class="input-group female-only">
                            <label for="bf-hip">Kalça Çevresi (cm)</label>
                            <input type="number" id="bf-hip" min="50" max="150" value="90" step="0.1">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="calculate-bodyfat-btn" class="calculate-button">
                            Hesapla <span class="dashicons dashicons-chart-area"></span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="tab-content" id="bodyfat-results">
                <div class="results-container">
                    <div class="results-summary">
                        <div class="result-card primary">
                            <h3>Vücut Yağ Oranı</h3>
                            <div class="result-value" id="bodyfat-result">0</div>
                            <div class="result-unit">%</div>
                        </div>
                        
                        <div class="result-card secondary">
                            <h3>Yağ Kütlesi</h3>
                            <div class="result-value" id="fat-mass">0</div>
                            <div class="result-unit">kg</div>
                        </div>
                        
                        <div class="result-card tertiary">
                            <h3>Yağsız Kütle</h3>
                            <div class="result-value" id="lean-mass">0</div>
                            <div class="result-unit">kg</div>
                        </div>
                    </div>
                    
                    <div class="bodyfat-category-container">
                        <h3>Vücut Yağ Kategorisi</h3>
                        <div class="bodyfat-meter">
                            <div class="category-list">
                                <div class="category-name essential">Temel Yağ</div>
                                <div class="category-name athletic">Atletik</div>
                                <div class="category-name fitness">Fitness</div>
                                <div class="category-name acceptable">Normal</div>
                                <div class="category-name obesity">Obezite</div>
                            </div>
                            <div class="meter-pointer">
                                <div class="pointer-arrow"></div>
                                <div class="pointer-label">%<span id="pointer-value">0</span></div>
                            </div>
                        </div>
                        <div class="category-description" id="category-description">
                            Ölçümlerinizi girerek yağ oranınızı hesaplayın.
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="back-to-bodyfat-form" class="back-button">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Ölçüleri Düzenle
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <button type="button" id="show-visualization" class="reporting-button" style="display: none;">
                            <span class="dashicons dashicons-chart-bar"></span> Rapor ve Görselleştirme
                        </button>
                    </div>
                    
                    <div id="visualization-container" style="display: none;">
                        <div class="visualization-header">
                            <h3>Görselleştirme ve Raporlama</h3>
                        </div>
                        
                        <div class="visualization-body">
                            <div id="visualization-content"></div>
                            
                            <div class="sharing-options">
                                <h4>Sonuçlarınızı Paylaşın</h4>
                                
                                <div class="export-options">
                                    <button id="download-pdf" class="pdf-button">
                                        <span class="dashicons dashicons-pdf"></span> PDF Raporu İndir
                                    </button>
                                    
                                    <div class="email-form">
                                        <input type="email" id="report-email" placeholder="E-posta Adresiniz">
                                        <button id="send-email" class="email-button">
                                            <span class="dashicons dashicons-email"></span> E-posta ile Gönder
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="social-sharing">
                                    <h5>Sosyal Medyada Paylaş</h5>
                                    <div class="social-buttons">
                                        <button id="share-facebook" class="social-btn facebook">
                                            <span class="dashicons dashicons-facebook"></span> Facebook
                                        </button>
                                        <button id="share-twitter" class="social-btn twitter">
                                            <span class="dashicons dashicons-twitter"></span> Twitter
                                        </button>
                                        <button id="share-whatsapp" class="social-btn whatsapp">
                                            <span class="dashicons dashicons-whatsapp"></span> WhatsApp
                                        </button>
                                        <button id="copy-link" class="social-btn copy">
                                            <span class="dashicons dashicons-admin-links"></span> Linki Kopyala
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $output = ob_get_clean();
    
    $output = apply_filters('bodyfat_calculator_output', $output);
    
    return $output;
}
add_shortcode('bodyfat_calculator', 'bodyfat_calculator_shortcode');



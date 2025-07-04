<?php
/**
 * Görselleştirme Fonksiyonları - Güncel Hali
 * 
 * Shortcode'a vücut kompozisyonu görselleştirme ve sosyal medya paylaşımı ekler
 */

/**
 * Bodyfat calculator shortcode'unun çıktısına görselleştirme öğelerini ekler
 */
function add_visualization_elements() {
    // Sosyal medya paylaşımının etkin olup olmadığını kontrol et
    $enable_social_sharing = get_option('fitness_calculator_enable_social_sharing', 1);
    
    // HTML yapısını döndür - bu içerik bodyfat_calculator_shortcode içerisinde kullanılacak
    $html = '
    <div class="visualization-container" style="display: none;">
        <div class="visualization-header">
            <h3>Görselleştirme ve Raporlama</h3>
        </div>
        
        <div class="visualization-body">
            <div class="body-composition-visualization">
                <h4>Vücut Kompozisyonu Analizi</h4>
                <div class="composition-chart">
                    <canvas id="body-composition-canvas" width="300" height="300"></canvas>
                </div>
            </div>
            
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
                </div>';
                
    // Sosyal medya paylaşımı etkinse ekle
    if ($enable_social_sharing) {
        $html .= '
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
                </div>';
    }
    
    $html .= '
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Bodyfat calculator shortcode'una görselleştirme butonunu ekler
 */
function modify_bodyfat_calculator_shortcode($content) {
    // Görselleştirme butonunu ekle
    $button = '<div class="form-row">
        <button type="button" id="show-visualization" class="visualization-button" style="display: none;">
            <span class="dashicons dashicons-visibility"></span> Rapor ve Görselleştirme
        </button>
    </div>';
    
    // Görselleştirme HTML'ini ekle
    $visualization = add_visualization_elements();
    
    // Butonun ve görselleştirmenin sonuçlar sayfasına eklenmesi için konum belirle
    $position = strpos($content, '<div class="form-row">
                        <button type="button" id="back-to-bodyfat-form"');
    
    if ($position !== false) {
        $content = substr_replace($content, $button . $visualization, $position, 0);
    }
    
    return $content;
}

// bodyfat_calculator_shortcode fonksiyonunu filtreleyerek görselleştirme ekle
add_filter('bodyfat_calculator_output', 'modify_bodyfat_calculator_shortcode');

/**
 * Vücut kompozisyonu için basit görselleştirme oluştur
 */
function generate_body_composition_chart($fat_percentage, $lean_percentage) {
    // Canvas tabanlı basit çubuk grafik için JavaScript kodu
    $chart_script = "
    <script>
    function drawBodyCompositionChart() {
        const canvas = document.getElementById('body-composition-canvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        
        // Canvas'ı temizle
        ctx.clearRect(0, 0, width, height);
        
        // Veriler
        const fatPercentage = {$fat_percentage};
        const leanPercentage = {$lean_percentage};
        
        // Renkler
        const fatColor = '#ff4757';
        const leanColor = '#5a67d8';
        
        // Çubuk grafik çiz
        const barWidth = 60;
        const barMaxHeight = 200;
        const startX = (width - barWidth * 2 - 40) / 2;
        const startY = height - 50;
        
        // Yağ çubuğu
        const fatHeight = (fatPercentage / 100) * barMaxHeight;
        ctx.fillStyle = fatColor;
        ctx.fillRect(startX, startY - fatHeight, barWidth, fatHeight);
        
        // Yağsız kütle çubuğu
        const leanHeight = (leanPercentage / 100) * barMaxHeight;
        ctx.fillStyle = leanColor;
        ctx.fillRect(startX + barWidth + 20, startY - leanHeight, barWidth, leanHeight);
        
        // Etiketler
        ctx.fillStyle = '#333';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        
        // Yağ etiketi
        ctx.fillText('Yağ', startX + barWidth/2, startY + 20);
        ctx.fillText(fatPercentage + '%', startX + barWidth/2, startY + 35);
        
        // Yağsız kütle etiketi
        ctx.fillText('Yağsız Kütle', startX + barWidth + 20 + barWidth/2, startY + 20);
        ctx.fillText(leanPercentage + '%', startX + barWidth + 20 + barWidth/2, startY + 35);
        
        // Başlık
        ctx.font = 'bold 16px Arial';
        ctx.fillText('Vücut Kompozisyonu', width/2, 30);
    }
    
    // Sayfa yüklendiğinde ve hesaplama yapıldığında çağır
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(drawBodyCompositionChart, 1000);
    });
    
    // Vücut yağ hesaplandığında tetikle
    jQuery(document).on('vucut_yag_hesaplandi', function() {
        setTimeout(drawBodyCompositionChart, 500);
    });
    </script>";
    
    return $chart_script;
}

/**
 * QR kod oluşturma fonksiyonu
 */
function generate_qr_code($data) {
    // Basit QR kod oluşturmak için Google Charts API kullan
    $qr_data = urlencode(json_encode($data));
    $qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . $qr_data;
    
    return '<img src="' . $qr_url . '" alt="QR Kod" style="max-width: 150px;">';
}

/**
 * Sosyal medya meta etiketlerini ekle
 */
function add_fitness_social_meta_tags() {
    if (is_singular() && (has_shortcode(get_the_content(), 'fitness_calculator') || 
                         has_shortcode(get_the_content(), 'bodyfat_calculator'))) {
        
        $site_name = get_bloginfo('name');
        $site_url = get_permalink();
        $description = 'Fitness hesaplamalarımı yaptım! BMR, TDEE, vücut yağ oranı ve daha fazlasını hesapla.';
        
        echo '<meta property="og:title" content="Fitness Hesaplama Sonuçlarım - ' . $site_name . '">';
        echo '<meta property="og:description" content="' . $description . '">';
        echo '<meta property="og:url" content="' . $site_url . '">';
        echo '<meta property="og:type" content="website">';
        echo '<meta property="og:site_name" content="' . $site_name . '">';
        
        echo '<meta name="twitter:card" content="summary">';
        echo '<meta name="twitter:title" content="Fitness Hesaplama Sonuçlarım">';
        echo '<meta name="twitter:description" content="' . $description . '">';
    }
}
add_action('wp_head', 'add_fitness_social_meta_tags');

/**
 * Sonuç paylaşımı için özel URL oluştur
 */
function create_shareable_url($data) {
    $base_url = get_permalink();
    $share_params = array(
        'fitness_share' => 1,
        'fat' => isset($data['bodyfat_percentage']) ? $data['bodyfat_percentage'] : '',
        'bmr' => isset($data['bmr']) ? $data['bmr'] : '',
        'tdee' => isset($data['tdee']) ? $data['tdee'] : ''
    );
    
    return add_query_arg($share_params, $base_url);
}

/**
 * Paylaşım URL'sini işle
 */
function handle_fitness_share_url() {
    if (isset($_GET['fitness_share']) && $_GET['fitness_share'] == 1) {
        // Paylaşım verilerini al
        $shared_data = array(
            'bodyfat_percentage' => sanitize_text_field($_GET['fat']),
            'bmr' => sanitize_text_field($_GET['bmr']),
            'tdee' => sanitize_text_field($_GET['tdee'])
        );
        
        // JavaScript ile verileri göster
        add_action('wp_footer', function() use ($shared_data) {
            echo '<script>
            jQuery(document).ready(function($) {
                // Paylaşılan verileri göster
                if (typeof displaySharedResults === "function") {
                    displaySharedResults(' . json_encode($shared_data) . ');
                }
            });
            </script>';
        });
    }
}
add_action('template_redirect', 'handle_fitness_share_url');

/**
 * Görselleştirme için ek JavaScript'ler ekle
 */
function add_visualization_scripts() {
    global $fitness_calculator_loaded;
    
    if ($fitness_calculator_loaded) {
        // Basit grafik çizimi için script
        wp_add_inline_script('fitness-calculator-script', '
            function createSimpleChart(canvasId, data) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                
                const ctx = canvas.getContext("2d");
                // Basit çubuk grafik çizimi burada yapılabilir
            }
        ');
    }
}
add_action('wp_footer', 'add_visualization_scripts', 25);

/**
 * Raporlama sayfası için ek CSS
 */
function add_visualization_styles() {
    global $fitness_calculator_loaded;
    
    if ($fitness_calculator_loaded) {
        wp_add_inline_style('fitness-calculator-style', '
            .composition-chart {
                text-align: center;
                margin: 20px 0;
            }
            
            .body-composition-visualization {
                background: white;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .body-composition-visualization h4 {
                margin: 0 0 15px;
                color: #495057;
            }
            
            #body-composition-canvas {
                border: 1px solid #e9ecef;
                border-radius: 4px;
                background: #fafafa;
            }
        ');
    }
}
add_action('wp_footer', 'add_visualization_styles', 20);
?>
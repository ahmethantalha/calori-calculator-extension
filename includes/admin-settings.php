<?php
/**
 * Fitness Calculator Admin Ayarları - Güncel Hali
 */

// Admin sayfasının içeriği
function fitness_calculator_admin_page() {
    // Ayarları kaydetme işlemi
    if (isset($_POST['fitness_calculator_save_settings'])) {
        // Güvenlik kontrolü
        check_admin_referer('fitness_calculator_settings_nonce', 'fitness_calculator_nonce');
        
        // Renk ayarlarını kaydet
        $primary_color = sanitize_hex_color($_POST['primary_color']);
        $secondary_color = sanitize_hex_color($_POST['secondary_color']);
        $tertiary_color = sanitize_hex_color($_POST['tertiary_color']);
        
        // Birim ayarlarını kaydet
        $weight_unit = sanitize_text_field($_POST['weight_unit']);
        $height_unit = sanitize_text_field($_POST['height_unit']);
        
        // Hesaplama formülü ayarını kaydet
        $bmr_formula = sanitize_text_field($_POST['bmr_formula']);
        
        // Yeni özellikler
        $enable_social_sharing = isset($_POST['enable_social_sharing']) ? 1 : 0;
        $enable_email_notifications = isset($_POST['enable_email_notifications']) ? 1 : 0;
        $admin_notification_email = sanitize_email($_POST['admin_notification_email']);
        
        // Ayarları WordPress veritabanına kaydet
        update_option('fitness_calculator_primary_color', $primary_color);
        update_option('fitness_calculator_secondary_color', $secondary_color);
        update_option('fitness_calculator_tertiary_color', $tertiary_color);
        update_option('fitness_calculator_weight_unit', $weight_unit);
        update_option('fitness_calculator_height_unit', $height_unit);
        update_option('fitness_calculator_bmr_formula', $bmr_formula);
        update_option('fitness_calculator_enable_social_sharing', $enable_social_sharing);
        update_option('fitness_calculator_enable_email_notifications', $enable_email_notifications);
        update_option('fitness_calculator_admin_notification_email', $admin_notification_email);
        
        // Başarılı mesajı göster
        echo '<div class="notice notice-success is-dismissible"><p>Ayarlar başarıyla kaydedildi.</p></div>';
    }
    
    // Test e-posta gönderme
    if (isset($_POST['test_email_send'])) {
        check_admin_referer('fitness_calculator_settings_nonce', 'fitness_calculator_nonce');
        
        if (function_exists('test_fitness_email_setup')) {
            $result = test_fitness_email_setup();
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>Test e-postası başarıyla gönderildi!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Test e-postası gönderilemedi. E-posta ayarlarınızı kontrol edin.</p></div>';
            }
        }
    }
    
    // Mevcut ayarları al
    $primary_color = get_option('fitness_calculator_primary_color', '#ff4757');
    $secondary_color = get_option('fitness_calculator_secondary_color', '#5a67d8');
    $tertiary_color = get_option('fitness_calculator_tertiary_color', '#3c366b');
    $weight_unit = get_option('fitness_calculator_weight_unit', 'kg');
    $height_unit = get_option('fitness_calculator_height_unit', 'cm');
    $bmr_formula = get_option('fitness_calculator_bmr_formula', 'katch_mcardle');
    $enable_social_sharing = get_option('fitness_calculator_enable_social_sharing', 1);
    $enable_email_notifications = get_option('fitness_calculator_enable_email_notifications', 1);
    $admin_notification_email = get_option('fitness_calculator_admin_notification_email', get_bloginfo('admin_email'));
    
    // İstatistikleri al
    $stats = function_exists('get_report_stats') ? get_report_stats() : array();
    
    ?>
    <div class="wrap">
        <h1>Fitness Hesaplayıcı Ayarları</h1>
        
        <!-- İstatistikler Dashboard -->
        <?php if (!empty($stats)): ?>
        <div class="fitness-stats-dashboard">
            <h2>Kullanım İstatistikleri</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_calculations']); ?></div>
                    <div class="stat-label">Toplam Hesaplama</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['pdf_generated']); ?></div>
                    <div class="stat-label">PDF Raporu</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['emails_sent']); ?></div>
                    <div class="stat-label">E-posta Gönderimi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['last_updated'] ? date('d.m.Y', $stats['last_updated']) : 'Henüz yok'; ?></div>
                    <div class="stat-label">Son Güncelleme</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="fitness-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general-settings" class="nav-tab nav-tab-active">Genel Ayarlar</a>
                <a href="#appearance-settings" class="nav-tab">Görünüm</a>
                <a href="#calculation-settings" class="nav-tab">Hesaplama</a>
                <a href="#advanced-settings" class="nav-tab">Gelişmiş</a>
                <a href="#shortcodes" class="nav-tab">Kısayol Kodları</a>
                <a href="#system-info" class="nav-tab">Sistem Bilgisi</a>
            </nav>
            
            <form method="post" action="">
                <?php wp_nonce_field('fitness_calculator_settings_nonce', 'fitness_calculator_nonce'); ?>
                
                <div id="general-settings" class="tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Ağırlık Birimi</th>
                            <td>
                                <select name="weight_unit">
                                    <option value="kg" <?php selected($weight_unit, 'kg'); ?>>Kilogram (kg)</option>
                                    <option value="lb" <?php selected($weight_unit, 'lb'); ?>>Pound (lb)</option>
                                </select>
                                <p class="description">Hesaplayıcıda kullanılacak varsayılan ağırlık birimi.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Boy Birimi</th>
                            <td>
                                <select name="height_unit">
                                    <option value="cm" <?php selected($height_unit, 'cm'); ?>>Santimetre (cm)</option>
                                    <option value="inch" <?php selected($height_unit, 'inch'); ?>>İnç (inch)</option>
                                </select>
                                <p class="description">Hesaplayıcıda kullanılacak varsayılan boy birimi.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sosyal Medya Paylaşımı</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_social_sharing" value="1" <?php checked($enable_social_sharing, 1); ?>>
                                    Sosyal medya paylaşım butonlarını etkinleştir
                                </label>
                                <p class="description">Kullanıcıların sonuçlarını Facebook, Twitter, WhatsApp'ta paylaşmasına izin verir.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="appearance-settings" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Ana Renk</th>
                            <td>
                                <input type="color" name="primary_color" value="<?php echo esc_attr($primary_color); ?>">
                                <p class="description">Butonlar ve vurgular için ana renk.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">İkincil Renk</th>
                            <td>
                                <input type="color" name="secondary_color" value="<?php echo esc_attr($secondary_color); ?>">
                                <p class="description">İkincil sonuç kartları için renk.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Üçüncül Renk</th>
                            <td>
                                <input type="color" name="tertiary_color" value="<?php echo esc_attr($tertiary_color); ?>">
                                <p class="description">Üçüncül sonuç kartları için renk.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="calculation-settings" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">BMR Hesaplama Formülü</th>
                            <td>
                                <select name="bmr_formula">
                                    <option value="katch_mcardle" <?php selected($bmr_formula, 'katch_mcardle'); ?>>Katch-McArdle (Önerilen)</option>
                                    <option value="mifflin_st_jeor" <?php selected($bmr_formula, 'mifflin_st_jeor'); ?>>Mifflin-St Jeor</option>
                                    <option value="harris_benedict" <?php selected($bmr_formula, 'harris_benedict'); ?>>Harris-Benedict</option>
                                </select>
                                <p class="description">
                                    <strong>Katch-McArdle:</strong> En doğru sonuç verir, vücut yağ oranını kullanır.<br>
                                    <strong>Mifflin-St Jeor:</strong> Modern ve güvenilir, yaş/cinsiyet tabanlı.<br>
                                    <strong>Harris-Benedict:</strong> Klasik formül, genel kullanım için.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="advanced-settings" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row">E-posta Bildirimleri</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_email_notifications" value="1" <?php checked($enable_email_notifications, 1); ?>>
                                    Admin bildirimlerini etkinleştir
                                </label>
                                <p class="description">Her rapor gönderiminde size bildirim e-postası gelir.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Bildirim E-posta Adresi</th>
                            <td>
                                <input type="email" name="admin_notification_email" value="<?php echo esc_attr($admin_notification_email); ?>" class="regular-text">
                                <p class="description">Bildirimlerin gönderileceği e-posta adresi.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">E-posta Sistemi Testi</th>
                            <td>
                                <button type="submit" name="test_email_send" class="button button-secondary">Test E-postası Gönder</button>
                                <p class="description">E-posta ayarlarınızın çalışıp çalışmadığını test edin.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="shortcodes" class="tab-content">
                    <div class="card">
                        <h2>Kısayol Kodları</h2>
                        <p>Eklentiyi sayfalarınızda kullanmak için aşağıdaki kısayol kodlarını kullanabilirsiniz:</p>
                        
                        <div class="shortcode-example">
                            <h3>🏋️ Fitness Hesaplayıcı</h3>
                            <code>[fitness_calculator]</code>
                            <p>Tam özellikli fitness hesaplayıcı. BMR, TDEE, makro besinler, İdeal kilo analizi içerir.</p>
                            <strong>Özellikler:</strong>
                            <ul>
                                <li>✅ BMR ve TDEE hesaplama</li>
                                <li>✅ BMI analizi</li>
                                <li>✅ Makro besin hesaplama</li>
                                <li>✅ İdeal kilo aralığı</li>
                                <li>✅ PDF rapor</li>
                                <li>✅ E-posta gönderimi</li>
                                <li>✅ Sosyal medya paylaşımı</li>
                            </ul>
                        </div>
                        
                        <div class="shortcode-example">
                            <h3>📏 Vücut Yağ Oranı Hesaplayıcı</h3>
                            <code>[bodyfat_calculator]</code>
                            <p>ABD Donanması yöntemiyle vücut yağ oranı hesaplaması. Boyun, bel ve kalça ölçümlerini kullanır.</p>
                            <strong>Özellikler:</strong>
                            <ul>
                                <li>✅ Hassas vücut yağ oranı</li>
                                <li>✅ Yağ ve yağsız kütle</li>
                                <li>✅ Kategori analizi</li>
                                <li>✅ Görsel gösterge</li>
                                <li>✅ PDF rapor</li>
                                <li>✅ Sosyal medya paylaşımı</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div id="system-info" class="tab-content">
                    <div class="card">
                        <h2>Sistem Bilgileri</h2>
                        <?php 
                        $mail_info = function_exists('check_wp_mail_settings') ? check_wp_mail_settings() : array();
                        ?>
                        <table class="widefat">
                            <tr>
                                <th>WordPress Sürümü</th>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <th>PHP Sürümü</th>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <th>Eklenti Sürümü</th>
                                <td>2.3</td>
                            </tr>
                            <tr>
                                <th>E-posta Fonksiyonu</th>
                                <td><?php echo isset($mail_info['wp_mail_available']) && $mail_info['wp_mail_available'] ? '✅ Aktif' : '❌ Pasif'; ?></td>
                            </tr>
                            <tr>
                                <th>Site E-postası</th>
                                <td><?php echo get_bloginfo('admin_email'); ?></td>
                            </tr>
                            <tr>
                                <th>Upload Dizini</th>
                                <td><?php 
                                $upload_dir = wp_upload_dir();
                                echo $upload_dir['basedir'] . '/fitness-reports';
                                echo is_writable($upload_dir['basedir']) ? ' ✅' : ' ❌ Yazılabilir değil';
                                ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="fitness_calculator_save_settings" class="button-primary" value="Ayarları Kaydet">
                </p>
            </form>
        </div>
    </div>
    
    <style>
    /* Admin panel CSS */
    .fitness-admin-tabs {
        margin-top: 20px;
    }
    
    .tab-content {
        display: none;
        padding: 20px 0;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .card {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 3px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .shortcode-example {
        background: #f9f9f9;
        border-left: 4px solid #ff4757;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .shortcode-example h3 {
        margin-top: 0;
    }
    
    .shortcode-example code {
        display: inline-block;
        font-size: 14px;
        background: #f1f1f1;
        padding: 5px 10px;
        border-radius: 3px;
        margin-bottom: 10px;
    }
    
    .shortcode-example ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    
    .shortcode-example li {
        margin: 5px 0;
    }
    
    /* İstatistikler */
    .fitness-stats-dashboard {
        background: white;
        border: 1px solid #ccd0d4;
        border-radius: 3px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }
    
    .stat-card {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 20px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #ff4757;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 14px;
    }
    
    .widefat th {
        width: 200px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Tab işlevselliği
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Sekme bağlantılarını güncelle
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Sekme içeriğini güncelle
            $('.tab-content').removeClass('active');
            $($(this).attr('href')).addClass('active');
        });
    });
    </script>
    <?php
}

// Özel CSS oluşturma
function fitness_calculator_custom_css() {
    $primary_color = get_option('fitness_calculator_primary_color', '#ff4757');
    $secondary_color = get_option('fitness_calculator_secondary_color', '#5a67d8');
    $tertiary_color = get_option('fitness_calculator_tertiary_color', '#3c366b');
    
    // Ayarlardan alınan renklerle dinamik CSS oluştur
    $custom_css = "
        .calculate-button, .tab-btn.active:after, input[type='range']::-webkit-slider-thumb {
            background-color: {$primary_color};
        }
        .calculate-button:hover {
            background-color: " . darken_color($primary_color, 10) . ";
        }
        .result-card.primary {
            background-color: {$primary_color};
        }
        .result-card.secondary {
            background-color: {$secondary_color};
        }
        .result-card.tertiary {
            background-color: {$tertiary_color};
        }
        .macro-icon .dashicons, .macro-value, .calculator-header h2,
        .ideal-weight-icon .dashicons, .ideal-weight-value {
            color: {$primary_color};
        }
        .pointer-arrow {
            border-bottom-color: {$primary_color};
        }
        .pointer-label {
            background-color: {$primary_color};
        }
    ";
    
    wp_add_inline_style('fitness-calculator-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'fitness_calculator_custom_css', 20);

// Renk koyulaştırma yardımcı fonksiyonu
function darken_color($hex, $percent) {
    // Hex rengini RGB bileşenlerine dönüştür
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    $rgb = array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    );
    
    // Her bileşeni koyulaştır
    foreach ($rgb as &$color) {
        $color = max(0, min(255, $color - ($color * ($percent / 100))));
    }
    
    // RGB'yi HEX'e geri dönüştür
    return sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
}
?>
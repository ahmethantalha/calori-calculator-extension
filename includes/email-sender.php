<?php
/**
 * E-posta Gönderme Fonksiyonları - Güncel Hali
 */

/**
 * Fitness raporu içeren bir e-posta gönderir
 * 
 * @param string $to_email Alıcı e-posta adresi
 * @param array $data Rapor verileri
 * @return boolean Gönderim başarılı mı
 */
function send_fitness_report_email($to_email, $data) {
    $subject = 'Fitness ve Vücut Kompozisyon Raporunuz';
    
    // HTML içerikli e-posta oluştur
    $message = '<html><body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;">';
    $message .= '<div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">';
    $message .= '<h2 style="color: #ff4757;">Fitness ve Vücut Kompozisyon Raporu</h2>';
    $message .= '<p style="color: #6c757d;">Tarih: ' . date('d/m/Y') . '</p>';
    $message .= '</div>';
    $message .= '<div style="padding: 20px; border: 1px solid #e9ecef;">';
    
    // Kişisel bilgiler
    $message .= '<h3 style="color: #495057; border-bottom: 1px solid #e9ecef; padding-bottom: 10px;">Kişisel Bilgiler</h3>';
    
    $gender_text = 'Belirtilmemiş';
    if (isset($data['gender'])) {
        $gender_text = ($data['gender'] == 'male' || $data['gender'] == 'M') ? 'Erkek' : 'Kadın';
    }
    
    $message .= '<p><strong>Cinsiyet:</strong> ' . $gender_text . '</p>';
    $message .= '<p><strong>Yaş:</strong> ' . (isset($data['age']) ? $data['age'] : '0') . ' yıl</p>';
    $message .= '<p><strong>Boy:</strong> ' . (isset($data['height']) ? $data['height'] : '0') . ' cm</p>';
    $message .= '<p><strong>Ağırlık:</strong> ' . (isset($data['weight']) ? $data['weight'] : '0') . ' kg</p>';
    
    // Ölçümler (varsa)
    if (isset($data['neck']) && $data['neck'] > 0) {
        $message .= '<p><strong>Boyun Çevresi:</strong> ' . $data['neck'] . ' cm</p>';
    }
    if (isset($data['waist']) && $data['waist'] > 0) {
        $message .= '<p><strong>Bel Çevresi:</strong> ' . $data['waist'] . ' cm</p>';
    }
    if (isset($data['hip']) && $data['hip'] > 0 && ($data['gender'] == 'female' || $data['gender'] == 'F')) {
        $message .= '<p><strong>Kalça Çevresi:</strong> ' . $data['hip'] . ' cm</p>';
    }
    
    // Hesaplama sonuçları
    $message .= '<h3 style="color: #495057; border-bottom: 1px solid #e9ecef; padding-bottom: 10px;">Hesaplama Sonuçları</h3>';
    
    // Vücut yağ sonuçları (varsa)
    if (isset($data['bodyfat_percentage']) && $data['bodyfat_percentage'] > 0) {
        $message .= '<h4 style="color: #ff4757;">Vücut Kompozisyonu</h4>';
        $message .= '<p><strong>Vücut Yağ Oranı:</strong> <span style="color: #ff4757; font-weight: bold;">' . $data['bodyfat_percentage'] . '%</span></p>';
        
        if (isset($data['fat_mass'])) {
            $message .= '<p><strong>Yağ Kütlesi:</strong> ' . $data['fat_mass'] . ' kg</p>';
        }
        
        if (isset($data['lean_mass'])) {
            $message .= '<p><strong>Yağsız Kütle:</strong> ' . $data['lean_mass'] . ' kg</p>';
        }
        
        if (isset($data['category_name'])) {
            $message .= '<p><strong>Vücut Yağ Kategorisi:</strong> ' . $data['category_name'] . '</p>';
        }
        
        // BMI bilgileri - Sadece fitness hesaplayıcıdan
        if (isset($data['bmi']) && $data['bmi'] > 0 && isset($data['bmr']) && $data['bmr'] > 0) {
            $message .= '<p><strong>BMI:</strong> <span style="color: #6f42c1; font-weight: bold;">' . $data['bmi'] . ' kg/m²</span></p>';
            
            if (isset($data['bmi_category'])) {
                $message .= '<p><strong>BMI Kategorisi:</strong> ' . $data['bmi_category'] . '</p>';
            }
        }
    }
    
    // Fitness sonuçları (varsa)
    if (isset($data['bmr']) && $data['bmr'] > 0) {
        $message .= '<h4 style="color: #28a745;">Kalori ve Makrolar</h4>';
        $message .= '<p><strong>BMR:</strong> ' . $data['bmr'] . ' kcal/gün</p>';
        
        if (isset($data['tdee'])) {
            $message .= '<p><strong>TDEE:</strong> ' . $data['tdee'] . ' kcal/gün</p>';
        }
        
        if (isset($data['target_calories'])) {
            $message .= '<p><strong>Hedef Kalori:</strong> <span style="color: #28a745; font-weight: bold;">' . $data['target_calories'] . ' kcal/gün</span></p>';
        }
        
        // Makro besinler
        if (isset($data['protein']) || isset($data['fat']) || isset($data['carbs'])) {
            $message .= '<h5>Günlük Makro Besin İhtiyaçları:</h5>';
            if (isset($data['protein'])) $message .= '<p><strong>Protein:</strong> ' . $data['protein'] . '</p>';
            if (isset($data['fat'])) $message .= '<p><strong>Yağ:</strong> ' . $data['fat'] . '</p>';
            if (isset($data['carbs'])) $message .= '<p><strong>Karbonhidrat:</strong> ' . $data['carbs'] . '</p>';
            if (isset($data['water'])) $message .= '<p><strong>Su:</strong> ' . $data['water'] . '</p>';
        }
        
        // İdeal kilo analizi (varsa)
        if (isset($data['ideal_weight_range']) && $data['ideal_weight_range'] != '0 - 0 kg') {
            $message .= '<h4 style="color: #28a745;">İdeal Kilo Analizi</h4>';
            $message .= '<p><strong>İdeal Kilo Aralığı:</strong> <span style="color: #28a745; font-weight: bold;">' . $data['ideal_weight_range'] . '</span></p>';
            
            if (isset($data['weight_difference']) && $data['weight_difference'] != '0 kg') {
                $message .= '<p><strong>Hedef Kilo Farkı:</strong> ' . $data['weight_difference'] . '</p>';
            }
            
            if (isset($data['weight_recommendations']) && !empty($data['weight_recommendations'])) {
                $message .= '<p><strong>Öneriler:</strong> ' . $data['weight_recommendations'] . '</p>';
            }
        }
    }
    
    // Kategori açıklaması (varsa)
    if (isset($data['category_description']) && !empty($data['category_description'])) {
        $message .= '<h3 style="color: #495057; border-bottom: 1px solid #e9ecef; padding-bottom: 10px;">Kategori Açıklaması</h3>';
        $message .= '<p>' . nl2br($data['category_description']) . '</p>';
    }
    
    $message .= '<div style="background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-radius: 5px; text-align: center;">';
    $message .= '<p style="margin: 0; color: #6c757d; font-size: 14px;">Bu rapor <a href="' . get_bloginfo('url') . '" style="color: #ff4757; text-decoration: none;">' . get_bloginfo('name') . '</a> tarafından oluşturulmuştur.</p>';
    $message .= '</div>';
    $message .= '</div>';
    $message .= '</body></html>';
    
    // E-posta başlıkları
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
    );
    
    // WordPress'in e-posta fonksiyonunu kullan
    $result = wp_mail($to_email, $subject, $message, $headers);
    
    // Hata durumunda loglama
    if (!$result) {
        error_log('E-posta gönderme hatası: ' . $to_email . ' adresine gönderim başarısız.');
    }
    
    return $result;
}

/**
 * E-posta ayarlarını test etmek için basit fonksiyon
 */
function test_fitness_email_setup() {
    $test_data = array(
        'gender' => 'male',
        'age' => 25,
        'height' => 175,
        'weight' => 70,
        'bodyfat_percentage' => 15.5,
        'fat_mass' => 10.9,
        'lean_mass' => 59.1,
        'category_name' => 'Fitness',
        'category_description' => 'Sağlıklı bir vücut yağ oranına sahipsiniz.',
        'bmr' => 1650,
        'tdee' => 2280,
        'target_calories' => 1940,
        'bmi' => 22.9,
        'bmi_category' => 'Normal',
        'protein' => '140 gr/gün',
        'fat' => '70 gr/gün',
        'carbs' => '240 gr/gün',
        'water' => '2.31 litre/gün',
        'ideal_weight_range' => '65 - 75 kg',
        'weight_difference' => 'İdeal aralıkta',
        'weight_recommendations' => 'Mükemmel! Kilonuz ideal aralıkta. Mevcut beslenme ve egzersiz rutininizi sürdürün.'
    );
    
    $admin_email = get_bloginfo('admin_email');
    return send_fitness_report_email($admin_email, $test_data);
}

/**
 * WordPress mail ayarlarını kontrol et
 */
function check_wp_mail_settings() {
    $info = array(
        'wp_mail_available' => function_exists('wp_mail'),
        'admin_email' => get_bloginfo('admin_email'),
        'site_name' => get_bloginfo('name'),
        'site_url' => get_bloginfo('url'),
        'php_mail_available' => function_exists('mail'),
        'smtp_settings' => array(
            'host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined',
            'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'Not defined'
        )
    );
    
    return $info;
}

/**
 * E-posta template'i özelleştirilebilir hale getir
 */
function get_email_template($data) {
    $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/email-template.html';
    
    if (file_exists($template_path)) {
        $template = file_get_contents($template_path);
        
        // Şablon değişkenlerini değiştir
        $replacements = array(
            '{{site_name}}' => get_bloginfo('name'),
            '{{site_url}}' => get_bloginfo('url'),
            '{{date}}' => date('d/m/Y'),
            '{{gender}}' => isset($data['gender']) ? ($data['gender'] == 'male' ? 'Erkek' : 'Kadın') : 'Belirtilmemiş',
            '{{age}}' => isset($data['age']) ? $data['age'] : '0',
            '{{height}}' => isset($data['height']) ? $data['height'] : '0',
            '{{weight}}' => isset($data['weight']) ? $data['weight'] : '0'
        );
        
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }
        
        return $template;
    }
    
    // Varsayılan template bulunamadıysa null döndür
    return null;
}
?>
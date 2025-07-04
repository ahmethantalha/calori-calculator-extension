<?php
/**
 * PDF Oluşturma Fonksiyonları - Güncel Hali
 */

// TCPDF kütüphanesini yükle
require_once plugin_dir_path(dirname(__FILE__)) . 'lib/tcpdf/tcpdf.php';

/**
 * Fitness verileriyle PDF raporu oluşturur
 * 
 * @param array $data Hesaplama sonuçlarını içeren veri dizisi
 * @return string|false PDF dosyasının URL'si veya başarısız olursa false
 */
function generate_fitness_pdf($data) {
    // Uploads dizininde bir klasör oluştur
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/fitness-reports';
    
    // Dizin yoksa oluştur
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }
    
    // Dosya adı oluştur
    $file_name = 'fitness_report_' . time() . '.pdf';
    $file_path = $pdf_dir . '/' . $file_name;
    
    // Yeni bir PDF dokümanı oluştur
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Doküman bilgilerini ayarla
    $pdf->SetCreator('Fitness Calculator');
    $pdf->SetAuthor(get_bloginfo('name'));
    $pdf->SetTitle('Fitness Raporu');
    
    // Başlık, sayfa numarası vb. kaldır
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Kenarlıkları ayarla
    $pdf->SetMargins(15, 15, 15);
    
    // Font ayarla
    $pdf->SetFont('dejavusans', '', 12);
    
    // Yeni sayfa ekle
    $pdf->AddPage();
    
    // Logo ekle (varsa)
    $logo_path = plugin_dir_path(dirname(__FILE__)) . 'images/logo.png';
    if (file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 15, 30, '', 'PNG');
    }
    
    // Başlık
    $pdf->SetY(25);
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'Fitness ve Vücut Kompozisyon Raporu', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Tarih
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 10, 'Tarih: ' . date('d/m/Y'), 0, 1, 'R');
    $pdf->Ln(5);
    
    // Temel bilgiler
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 10, 'Kişisel Bilgiler', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 12);
    
    // Cinsiyet kontrolü
    $gender_text = 'Belirtilmemiş';
    if (isset($data['gender'])) {
        $gender_text = ($data['gender'] == 'male' || $data['gender'] == 'M') ? 'Erkek' : 'Kadın';
    }
    
    $pdf->Cell(60, 10, 'Cinsiyet: ', 0, 0, 'L');
    $pdf->Cell(0, 10, $gender_text, 0, 1, 'L');
    
    $pdf->Cell(60, 10, 'Yaş: ', 0, 0, 'L');
    $pdf->Cell(0, 10, (isset($data['age']) ? $data['age'] : '0') . ' yıl', 0, 1, 'L');
    
    $pdf->Cell(60, 10, 'Boy: ', 0, 0, 'L');
    $pdf->Cell(0, 10, (isset($data['height']) ? $data['height'] : '0') . ' cm', 0, 1, 'L');
    
    $pdf->Cell(60, 10, 'Ağırlık: ', 0, 0, 'L');
    $pdf->Cell(0, 10, (isset($data['weight']) ? $data['weight'] : '0') . ' kg', 0, 1, 'L');
    
    // Ölçümler (varsa)
    if (isset($data['neck']) && $data['neck'] > 0) {
        $pdf->Cell(60, 10, 'Boyun Çevresi: ', 0, 0, 'L');
        $pdf->Cell(0, 10, $data['neck'] . ' cm', 0, 1, 'L');
    }
    
    if (isset($data['waist']) && $data['waist'] > 0) {
        $pdf->Cell(60, 10, 'Bel Çevresi: ', 0, 0, 'L');
        $pdf->Cell(0, 10, $data['waist'] . ' cm', 0, 1, 'L');
    }
    
    if (isset($data['hip']) && $data['hip'] > 0 && ($data['gender'] == 'female' || $data['gender'] == 'F')) {
        $pdf->Cell(60, 10, 'Kalça Çevresi: ', 0, 0, 'L');
        $pdf->Cell(0, 10, $data['hip'] . ' cm', 0, 1, 'L');
    }
    
    // Sonuçlar bölümü
    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 10, 'Hesaplama Sonuçları', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 12);
    
    // Vücut yağ sonuçları (varsa)
    if (isset($data['bodyfat_percentage']) && $data['bodyfat_percentage'] > 0) {
        $pdf->Cell(60, 10, 'Vücut Yağ Oranı: ', 0, 0, 'L');
        $pdf->SetTextColor(255, 0, 0); // Kırmızı renk
        $pdf->Cell(0, 10, $data['bodyfat_percentage'] . '%', 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0); // Siyaha geri dön
        
        if (isset($data['fat_mass'])) {
            $pdf->Cell(60, 10, 'Yağ Kütlesi: ', 0, 0, 'L');
            $pdf->Cell(0, 10, $data['fat_mass'] . ' kg', 0, 1, 'L');
        }
        
        if (isset($data['lean_mass'])) {
            $pdf->Cell(60, 10, 'Yağsız Kütle: ', 0, 0, 'L');
            $pdf->Cell(0, 10, $data['lean_mass'] . ' kg', 0, 1, 'L');
        }
        
        // Kategori bilgisi
        if (isset($data['category_name'])) {
            $pdf->Cell(60, 10, 'Vücut Yağ Kategorisi: ', 0, 0, 'L');
            $pdf->Cell(0, 10, $data['category_name'], 0, 1, 'L');
        }
        
        // BMI bilgileri - Sadece fitness hesaplayıcıdan
        if (isset($data['bmi']) && $data['bmi'] > 0 && isset($data['bmr']) && $data['bmr'] > 0) {
            $pdf->Cell(60, 10, 'BMI: ', 0, 0, 'L');
            $pdf->SetTextColor(111, 66, 193); // BMI rengi (mor)
            $pdf->Cell(0, 10, $data['bmi'] . ' kg/m²', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0); // Siyaha geri dön
            
            if (isset($data['bmi_category'])) {
                $pdf->Cell(60, 10, 'BMI Kategorisi: ', 0, 0, 'L');
                $pdf->Cell(0, 10, $data['bmi_category'], 0, 1, 'L');
            }
        }
    }
    
    // Fitness hesaplamaları (varsa)
    if (isset($data['bmr']) && $data['bmr'] > 0) {
        $pdf->Ln(3);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Kalori ve Makro Besinler', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        
        $pdf->Cell(60, 10, 'BMR: ', 0, 0, 'L');
        $pdf->Cell(0, 10, $data['bmr'] . ' kcal/gün', 0, 1, 'L');
        
        if (isset($data['tdee'])) {
            $pdf->Cell(60, 10, 'TDEE: ', 0, 0, 'L');
            $pdf->Cell(0, 10, $data['tdee'] . ' kcal/gün', 0, 1, 'L');
        }
        
        if (isset($data['target_calories'])) {
            $pdf->Cell(60, 10, 'Hedef Kalori: ', 0, 0, 'L');
            $pdf->SetTextColor(0, 150, 0); // Yeşil renk
            $pdf->Cell(0, 10, $data['target_calories'] . ' kcal/gün', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }
        
        // Makro besinler
        if (isset($data['protein']) || isset($data['fat']) || isset($data['carbs'])) {
            $pdf->Ln(3);
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(0, 10, 'Günlük Makro Besin İhtiyaçları:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            
            if (isset($data['protein'])) {
                $pdf->Cell(60, 8, 'Protein: ', 0, 0, 'L');
                $pdf->Cell(0, 8, $data['protein'], 0, 1, 'L');
            }
            
            if (isset($data['fat'])) {
                $pdf->Cell(60, 8, 'Yağ: ', 0, 0, 'L');
                $pdf->Cell(0, 8, $data['fat'], 0, 1, 'L');
            }
            
            if (isset($data['carbs'])) {
                $pdf->Cell(60, 8, 'Karbonhidrat: ', 0, 0, 'L');
                $pdf->Cell(0, 8, $data['carbs'], 0, 1, 'L');
            }
            
            if (isset($data['water'])) {
                $pdf->Cell(60, 8, 'Su: ', 0, 0, 'L');
                $pdf->Cell(0, 8, $data['water'], 0, 1, 'L');
            }
        }
        
        // İdeal kilo analizi (varsa)
        if (isset($data['ideal_weight_range']) && $data['ideal_weight_range'] != '0 - 0 kg') {
            $pdf->Ln(5);
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 10, 'İdeal Kilo Analizi', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            
            $pdf->Cell(60, 8, 'İdeal Kilo Aralığı: ', 0, 0, 'L');
            $pdf->SetTextColor(0, 150, 0); // Yeşil renk
            $pdf->Cell(0, 8, $data['ideal_weight_range'], 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            
            if (isset($data['weight_difference']) && $data['weight_difference'] != '0 kg') {
                $pdf->Cell(60, 8, 'Hedef Kilo Farkı: ', 0, 0, 'L');
                $pdf->Cell(0, 8, $data['weight_difference'], 0, 1, 'L');
            }
            
            if (isset($data['weight_recommendations']) && !empty($data['weight_recommendations'])) {
                $pdf->Ln(2);
                $pdf->Cell(60, 8, 'Öneriler: ', 0, 0, 'L');
                $pdf->MultiCell(0, 8, $data['weight_recommendations'], 0, 'L');
            }
        }
    }
    
    // Kategori açıklaması (varsa)
    if (isset($data['category_description']) && !empty($data['category_description'])) {
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Kategori Açıklaması', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->MultiCell(0, 8, $data['category_description'], 0, 'L');
    }
    
    // Site bilgileri
    $pdf->Ln(10);
    $pdf->SetFont('dejavusans', 'I', 10);
    $pdf->Cell(0, 10, 'Bu rapor ' . get_bloginfo('name') . ' tarafından oluşturulmuştur.', 0, 1, 'C');
    $pdf->Cell(0, 10, get_bloginfo('url'), 0, 1, 'C');
    
    // PDF'i kaydet
    try {
        $pdf->Output($file_path, 'F');
        
        // Dosya URL'sini döndür
        if (file_exists($file_path)) {
            $file_url = $upload_dir['baseurl'] . '/fitness-reports/' . $file_name;
            return $file_url;
        } else {
            return false;
        }
    } catch (Exception $e) {
        error_log('PDF oluşturma hatası: ' . $e->getMessage());
        return false;
    }
}
?>

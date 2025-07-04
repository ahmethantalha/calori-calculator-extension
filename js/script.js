jQuery(document).ready(function($) {
    // Tab navigation - fitness calculator
    $(document).on('click', '.fitness-calculator-container .tab-btn', function() {
        const tabId = $(this).data('tab');
        
        // Toggle active class on buttons
        $(this).closest('.calculator-tabs').find('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Toggle tab content
        $(this).closest('.calculator-tabs').find('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Back button functionality - fitness calculator
    $(document).on('click', '#back-to-form', function() {
        $('.fitness-calculator-container .tab-btn[data-tab="basic-info"]').click();
    });
    
    // Range sliders value update
    $('#weight, #fat').on('input', function() {
        const id = $(this).attr('id');
        const value = $(this).val();
        
        // Update displayed value
        $('#' + id + '-value').text(value);
        
        // Visual feedback on slider
        const percent = ((value - $(this).attr('min')) / ($(this).attr('max') - $(this).attr('min'))) * 100;
        $(this).css('background', 'linear-gradient(to right, #ff4757 0%, #ff4757 ' + percent + '%, #ddd ' + percent + '%, #ddd 100%)');
    });
    
    // Initialize sliders
    $('#weight, #fat').each(function() {
        const percent = (($(this).val() - $(this).attr('min')) / ($(this).attr('max') - $(this).attr('min'))) * 100;
        $(this).css('background', 'linear-gradient(to right, #ff4757 0%, #ff4757 ' + percent + '%, #ddd ' + percent + '%, #ddd 100%)');
    });
    
    // Calculate button functionality - fitness calculator
    $(document).on('click', '#calculate-btn', function() {
        calculateAll();
        $('.fitness-calculator-container .tab-btn[data-tab="results"]').click();
        
        // Hesaplama tamamlandıktan sonra rapor butonunu göster
        setTimeout(function() {
            $('#show-visualization').show();
            $(document).trigger('calculation_completed');
        }, 1000); // Animasyonların tamamlanması için bekle
    });
    
    // Get settings from wp_localize_script
    function getSettings(key, defaultValue) {
        if (typeof fitnessCalculatorSettings !== 'undefined' && fitnessCalculatorSettings[key]) {
            return fitnessCalculatorSettings[key];
        }
        return defaultValue;
    }
    
    // Calculate functions
    function calculateBMR() {
        const weight = parseFloat($('#weight').val());
        const height = parseFloat($('#height').val());
        const age = parseFloat($('#age').val());
        const gender = $('input[name="gender"]:checked').val();
        const fatPercentage = parseFloat($('#fat').val());
        
        // Admin ayarlarından hangi formülün kullanılacağını al
        const bmrFormula = getSettings('bmrFormula', 'katch_mcardle');
        
        let bmr = 0;
        
        switch(bmrFormula) {
            case 'katch_mcardle':
                // Katch-McArdle Formula (yağsız vücut kütlesi kullanarak)
                const leanMass = weight * (1 - (fatPercentage / 100));
                bmr = Math.round(370 + (21.6 * leanMass));
                break;
                
            case 'mifflin_st_jeor':
                // Mifflin-St Jeor Formula
                if (gender === 'male') {
                    bmr = Math.round((10 * weight) + (6.25 * height) - (5 * age) + 5);
                } else {
                    bmr = Math.round((10 * weight) + (6.25 * height) - (5 * age) - 161);
                }
                break;
                
            case 'harris_benedict':
                // Harris-Benedict Formula
                if (gender === 'male') {
                    bmr = Math.round(88.362 + (13.397 * weight) + (4.799 * height) - (5.677 * age));
                } else {
                    bmr = Math.round(447.593 + (9.247 * weight) + (3.098 * height) - (4.330 * age));
                }
                break;
                
            default:
                // Varsayılan olarak Katch-McArdle formülünü kullan
                const defaultLeanMass = weight * (1 - (fatPercentage / 100));
                bmr = Math.round(370 + (21.6 * defaultLeanMass));
        }
        
        return bmr;
    }

    // BMI hesaplama fonksiyonu - Sadece fitness hesaplayıcı için
    function calculateBMI() {
        const weight = parseFloat($('#weight').val());
        const height = parseFloat($('#height').val());
        
        if (!weight || !height) return { bmi: 0, category: 'Veri eksik', color: '#6c757d' };
        
        // BMI = kg / m²
        const heightInMeters = height / 100;
        const bmi = weight / (heightInMeters * heightInMeters);
        
        // BMI kategorilerini belirle
        let category, color;
        if (bmi < 18.5) {
            category = 'Zayıf';
            color = '#17a2b8';
        } else if (bmi < 25) {
            category = 'Normal';
            color = '#28a745';
        } else if (bmi < 30) {
            category = 'Fazla Kilolu';
            color = '#ffc107';
        } else {
            category = 'Obez';
            color = '#dc3545';
        }
        
        return {
            bmi: Math.round(bmi * 10) / 10,
            category: category,
            color: color
        };
    }

    function calculateTDEE(bmr) {
        const activityLevel = parseFloat($('#activity-level').val());
        return Math.round(bmr * activityLevel);
    }

    function calculateTargetCalories(tdee) {
        const goal = $('#goal').val();
        
        let targetCalories = tdee;
        if (goal === 'lose') targetCalories = Math.round(tdee * 0.85); // 15% deficit instead of fixed 500
        if (goal === 'gain') targetCalories = Math.round(tdee * 1.15); // 15% surplus instead of fixed 500
        
        return targetCalories;
    }

    function calculateMacros(calories) {
        const weight = parseFloat($('#weight').val());
        const goal = $('#goal').val();
        
        let proteinMultiplier, fatMultiplier;
        
        switch(goal) {
            case 'lose':
                proteinMultiplier = 2.2; // Higher protein for muscle preservation
                fatMultiplier = 0.8;
                break;
            case 'gain':
                proteinMultiplier = 2.0; // High protein for muscle gain
                fatMultiplier = 1.0; // More fat for hormonal health
                break;
            default: // maintain
                proteinMultiplier = 1.8;
                fatMultiplier = 0.9;
        }
        
        const protein = Math.round(weight * proteinMultiplier);
        const fat = Math.round(weight * fatMultiplier);
        
        // Calculate carbs from remaining calories
        // Protein = 4 cal/g, Fat = 9 cal/g, Carbs = 4 cal/g
        const proteinCalories = protein * 4;
        const fatCalories = fat * 9;
        const carbCalories = calories - proteinCalories - fatCalories;
        const carbs = Math.max(0, Math.round(carbCalories / 4));
        
        return {
            protein: protein,
            fat: fat,
            carbs: carbs
        };
    }

    function calculateWater() {
        // More precise water calculation based on weight and activity
        const weight = parseFloat($('#weight').val());
        const activityLevel = parseFloat($('#activity-level').val());
        
        // Base calculation + activity adjustment
        let waterMultiplier = 0.033; // Base: 33ml per kg of body weight
        
        if (activityLevel > 1.55) {
            waterMultiplier += 0.007; // Add more for high activity
        }
        
        return (weight * waterMultiplier).toFixed(2);
    }

    // İdeal kilo hesaplama fonksiyonu
    function calculateIdealWeight() {
        const height = parseFloat($('#height').val());
        const weight = parseFloat($('#weight').val());
        const gender = $('input[name="gender"]:checked').val();
        
        if (!height || !weight) return { range: '0 - 0', difference: '0', recommendations: 'Veri eksik' };
        
        // Robinson Formülü (1983) - En yaygın kullanılan
        let idealWeight;
        const heightInInches = height / 2.54; // cm to inches
        
        if (gender === 'male') {
            // Erkek: 52 kg + 1.9 kg per inch over 5 feet
            idealWeight = 52 + (1.9 * Math.max(0, heightInInches - 60));
        } else {
            // Kadın: 49 kg + 1.7 kg per inch over 5 feet
            idealWeight = 49 + (1.7 * Math.max(0, heightInInches - 60));
        }
        
        // BMI tabanlı sağlıklı kilo aralığı (BMI 18.5-24.9)
        const heightInMeters = height / 100;
        const minWeight = 18.5 * (heightInMeters * heightInMeters);
        const maxWeight = 24.9 * (heightInMeters * heightInMeters);
        
        // Aralığı oluştur
        const rangeMin = Math.round(Math.min(idealWeight * 0.95, minWeight));
        const rangeMax = Math.round(Math.max(idealWeight * 1.05, maxWeight));
        const range = rangeMin + ' - ' + rangeMax + ' kg';
        
        // Mevcut kilo ile ideal kilo arasındaki fark
        const midPoint = (rangeMin + rangeMax) / 2;
        const difference = weight - midPoint;
        let differenceText;
        
        if (Math.abs(difference) < 2) {
            differenceText = 'İdeal aralıkta';
        } else if (difference > 0) {
            differenceText = '+' + Math.round(difference) + ' kg';
        } else {
            differenceText = Math.round(difference) + ' kg';
        }
        
        // Öneriler
        let recommendations;
        if (Math.abs(difference) < 2) {
            recommendations = 'Mükemmel! Kilonuz ideal aralıkta. Mevcut beslenme ve egzersiz rutininizi sürdürün.';
        } else if (difference > 5) {
            recommendations = 'Kilo verme hedefi. Haftada 0.5-1 kg yavaş ve sürdürülebilir kilo kaybı önerilir.';
        } else if (difference < -5) {
            recommendations = 'Kilo alma hedefi. Sağlıklı kilo alımı için beslenme uzmanına danışın.';
        } else if (difference > 0) {
            recommendations = 'Hafif kilo verme. Beslenme kalitesini artırın ve aktiviteyi çoğaltın.';
        } else {
            recommendations = 'Hafif kilo alma. Sağlıklı karbonhidrat ve protein alımını artırın.';
        }
        
        return {
            range: range,
            difference: differenceText,
            recommendations: recommendations
        };
    }

    // Animasyon fonksiyonu - optimize edilmiş
    function animateNumberCount(element, targetValue) {
        const $el = $(element);
        const startValue = parseFloat($el.text()) || 0;
        const duration = 800; // Daha hızlı animasyon
        const startTime = Date.now();
        const isDecimal = (targetValue % 1 !== 0);
        
        function updateValue() {
            const currentTime = Date.now();
            const elapsed = currentTime - startTime;
            
            if (elapsed < duration) {
                const progress = elapsed / duration;
                let currentValue = startValue + (targetValue - startValue) * progress;
                
                // Decimal veya integer olarak formatla
                if (isDecimal) {
                    currentValue = currentValue.toFixed(1);
                } else {
                    currentValue = Math.round(currentValue);
                }
                
                $el.text(currentValue);
                requestAnimationFrame(updateValue);
            } else {
                // Son değer (targetValue ile aynı format)
                if (isDecimal) {
                    $el.text(parseFloat(targetValue).toFixed(1));
                } else {
                    $el.text(Math.round(targetValue));
                }
            }
        }
        
        updateValue();
    }

    function calculateAll() {
        const bmr = calculateBMR();
        const tdee = calculateTDEE(bmr);
        const targetCalories = calculateTargetCalories(tdee);
        const macros = calculateMacros(targetCalories);
        const water = calculateWater();
        const bmiData = calculateBMI();
        const idealWeightData = calculateIdealWeight();
        
        // Animate number updates
        animateNumberCount('#bmr-result', bmr);
        animateNumberCount('#tdee-result', tdee);
        animateNumberCount('#target-calories', targetCalories);
        
        // BMI sonucunu göster
        animateNumberCount('#bmi-result', bmiData.bmi);
        $('#bmi-category').text(bmiData.category).css('color', bmiData.color);
        
        // Update macros
        $('#protein-result').text(macros.protein + ' gr/gün');
        $('#fat-result').text(macros.fat + ' gr/gün');
        $('#carb-result').text(macros.carbs + ' gr/gün');
        $('#water-result').text(water + ' litre/gün');
        
        // İdeal kilo sonuçlarını göster
        $('#ideal-weight-range').text(idealWeightData.range);
        $('#weight-difference').text(idealWeightData.difference);
        $('#weight-recommendations').text(idealWeightData.recommendations);
    }

    // Gender selection
    $('input[name="gender"]').change(function() {
        // You could add customization based on gender here if needed
    });
    
    // Birim dönüşüm fonksiyonları
    function lbToKg(lb) {
        return lb * 0.45359237;
    }

    function kgToLb(kg) {
        return kg * 2.20462262;
    }

    function inchToCm(inch) {
        return inch * 2.54;
    }

    function cmToInch(cm) {
        return cm * 0.393701;
    }
    
    // Mark range inputs as user-changed when adjusted
    $('#fat').on('change', function() {
        $(this).data('user-changed', true);
    });

    //=================================================================================
    // Vücut Yağ Oranı Hesaplayıcı JS Kodu - Optimize Edilmiş
    //=================================================================================
    
    // Cinsiyet seçimine bağlı olarak kalça ölçüsü alanını göster/gizle
    function toggleGenderFields() {
        const gender = $('input[name="bf-gender"]:checked').val();
        if (gender === 'female') {
            $('.female-only').show();
        } else {
            $('.female-only').hide();
        }
    }
    
    // Sayfa yüklendiğinde çalıştır
    toggleGenderFields();
    
    // Cinsiyet değiştiğinde çalıştır
    $(document).on('change', 'input[name="bf-gender"]', function() {
        toggleGenderFields();
    });
    
    // Tab navigation - vücut yağ hesaplayıcı
    $(document).on('click', '.bodyfat-calculator-container .tab-btn', function() {
        const tabId = $(this).data('tab');
        
        // Toggle active class on buttons
        $(this).closest('.calculator-tabs').find('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Toggle tab content
        $(this).closest('.calculator-tabs').find('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Back button functionality - vücut yağ hesaplayıcı
    $(document).on('click', '#back-to-bodyfat-form', function() {
        $('.bodyfat-calculator-container .tab-btn[data-tab="measurement-input"]').click();
    });
    
    // Hesapla butonu - vücut yağ hesaplayıcı
    $(document).on('click', '#calculate-bodyfat-btn', function() {
        calculateBodyFat();
        $('.bodyfat-calculator-container .tab-btn[data-tab="bodyfat-results"]').click();
        
        // Hesaplama tamamlandıktan sonra rapor butonunu göster
        setTimeout(function() {
            $('#show-visualization').show();
        }, 1000); // Animasyonların tamamlanması için bekle
    });
    
    // Vücut yağ oranı hesaplama
    function calculateBodyFat() {
        const gender = $('input[name="bf-gender"]:checked').val();
        const age = parseFloat($('#bf-age').val());
        const weight = parseFloat($('#bf-weight').val());
        const height = parseFloat($('#bf-height').val());
        const neck = parseFloat($('#bf-neck').val());
        const waist = parseFloat($('#bf-waist').val());
        const hip = gender === 'female' ? parseFloat($('#bf-hip').val()) : 0;
        
        let bodyFatPercentage = 0;
        
        // ABD Donanması yöntemi
        if (gender === 'male') {
            // Erkek formülü
            bodyFatPercentage = 495 / (1.0324 - 0.19077 * Math.log10(waist - neck) + 0.15456 * Math.log10(height)) - 450;
        } else {
            // Kadın formülü
            bodyFatPercentage = 495 / (1.29579 - 0.35004 * Math.log10(waist + hip - neck) + 0.22100 * Math.log10(height)) - 450;
        }
        
        // NaN kontrolü
        if (isNaN(bodyFatPercentage)) {
            console.error('Yağ oranı hesaplanamadı. Girilen değerleri kontrol edin.');
            bodyFatPercentage = 0;
            alert("Lütfen tüm ölçüm değerlerini doğru girdiğinizden emin olun.");
            return;
        }
        
        // Sonuçları yuvarlama ve sınırlama
        bodyFatPercentage = Math.max(3, Math.min(45, bodyFatPercentage));
        bodyFatPercentage = parseFloat(bodyFatPercentage.toFixed(1));
        
        // Yağ kütlesi ve yağsız kütle hesaplama
        const fatMass = (weight * bodyFatPercentage / 100).toFixed(1);
        const leanMass = (weight - fatMass).toFixed(1);
        
        // Sonuçları gösterme
        animateNumberCount('#bodyfat-result', bodyFatPercentage);
        animateNumberCount('#fat-mass', fatMass);
        animateNumberCount('#lean-mass', leanMass);
        
        // Kategori belirleme ve gösterme
        updateBodyFatCategory(bodyFatPercentage, gender);
        
        // Sonuçları hesapla
        var results = {
            bodyFatPercentage: bodyFatPercentage,
            fatMass: fatMass,
            leanMass: leanMass
        };
        
        // Olay tetikle
        $(document).trigger('vucut_yag_hesaplandi', [results]);
    }
    
    // Vücut yağ kategorisini belirleme ve gösterge çubuğunu güncelleme
    function updateBodyFatCategory(bodyFatPercentage, gender) {
        let category = '';
        let description = '';
        let position = 0;
        
        // Erkek kategorileri
        if (gender === 'male') {
            if (bodyFatPercentage < 6) {
                category = 'essential';
                description = 'Temel Yağ Seviyesi: %2-5 - Bu seviye atletler için bile çok düşüktür ve uzun süre korunması sağlık açısından risklidir.';
                position = mapRange(bodyFatPercentage, 2, 5, 0, 20);
            } else if (bodyFatPercentage < 14) {
                category = 'athletic';
                description = 'Atletik: %6-13 - Bu seviye sporcular ve fitness tutkunları için idealdir. Kaslar belirgin ve damarlar görünürdür.';
                position = mapRange(bodyFatPercentage, 6, 13, 20, 40);
            } else if (bodyFatPercentage < 18) {
                category = 'fitness';
                description = 'Fitness: %14-17 - Sağlıklı bir yağ seviyesidir. Kaslar hala belirgindir ama damarlar daha az görünürdür.';
                position = mapRange(bodyFatPercentage, 14, 17, 40, 60);
            } else if (bodyFatPercentage < 25) {
                category = 'acceptable';
                description = 'Normal: %18-24 - Çoğu erkek için normal aralıktır. Kaslar daha az belirgindir.';
                position = mapRange(bodyFatPercentage, 18, 24, 60, 80);
            } else {
                category = 'obesity';
                description = 'Obezite: %25 ve üzeri - Sağlık riskleri artmıştır. Kilo vermek sağlık için faydalı olacaktır.';
                position = mapRange(bodyFatPercentage, 25, 40, 80, 100);
            }
        } 
        // Kadın kategorileri
        else {
            if (bodyFatPercentage < 16) {
                category = 'essential';
                description = 'Temel Yağ Seviyesi: %10-15 - Bu seviye kadın atletler için bile düşüktür ve uzun süre korunması sağlık açısından risklidir.';
                position = mapRange(bodyFatPercentage, 10, 15, 0, 20);
            } else if (bodyFatPercentage < 21) {
                category = 'athletic';
                description = 'Atletik: %16-20 - Bu seviye kadın sporcular ve fitness tutkunları için idealdir. Kas hatları belirgindir.';
                position = mapRange(bodyFatPercentage, 16, 20, 20, 40);
            } else if (bodyFatPercentage < 25) {
                category = 'fitness';
                description = 'Fitness: %21-24 - Sağlıklı bir yağ seviyesidir. Kaslar hala belirgindir.';
                position = mapRange(bodyFatPercentage, 21, 24, 40, 60);
            } else if (bodyFatPercentage < 32) {
                category = 'acceptable';
                description = 'Normal: %25-31 - Çoğu kadın için normal aralıktır.';
                position = mapRange(bodyFatPercentage, 25, 31, 60, 80);
            } else {
                category = 'obesity';
                description = 'Obezite: %32 ve üzeri - Sağlık riskleri artmıştır. Kilo vermek sağlık için faydalı olacaktır.';
                position = mapRange(bodyFatPercentage, 32, 45, 80, 100);
            }
        }
        
        // Açıklama metnini güncelle
        $('#category-description').text(description);
        
        // İşaretçi pozisyonunu güncelle (CSS üzerinden)
        $('.meter-pointer').css('left', position + '%');
        $('#pointer-value').text(bodyFatPercentage);
        
        // Kategori vurgula
        $('.category-name').removeClass('active');
        $('.category-name.' + category).addClass('active');
    }
    
    // Bir aralıktaki sayıyı başka bir aralığa dönüştüren yardımcı fonksiyon
    function mapRange(value, inMin, inMax, outMin, outMax) {
        return (value - inMin) * (outMax - outMin) / (inMax - inMin) + outMin;
    }
});

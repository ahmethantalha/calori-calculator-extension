/**
 * Fitness Calculator - Raporlama JavaScript (Production)
 */
jQuery(document).ready(function($) {
    
    // PDF indirme
    $(document).on('click', '#download-pdf', function() {
        const resultsData = collectResults();
        
        if (!resultsData || Object.keys(resultsData).length === 0) {
            alert('PDF oluşturmak için önce hesaplama yapın.');
            return;
        }
        
        $.ajax({
            url: fitnessCalculatorSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_fitness_pdf',
                nonce: fitnessCalculatorSettings.nonce,
                data: JSON.stringify(resultsData)
            },
            beforeSend: function() {
                $('#download-pdf').prop('disabled', true).text('PDF Oluşturuluyor...');
            },
            success: function(response) {
                if (response.success && response.data && response.data.file_url) {
                    window.open(response.data.file_url, '_blank');
                } else {
                    alert('PDF oluşturulurken bir hata oluştu.');
                }
            },
            error: function() {
                alert('Sunucu ile iletişim kurulamadı.');
            },
            complete: function() {
                $('#download-pdf').prop('disabled', false).text('PDF Raporu İndir');
            }
        });
    });
    
    // E-posta gönderme
    $(document).on('click', '#send-email', function() {
        const email = $('#report-email').val();
        
        if (!email || !validateEmail(email)) {
            alert('Lütfen geçerli bir e-posta adresi girin.');
            $('#report-email').focus();
            return;
        }
        
        const resultsData = collectResults();
        
        if (!resultsData || Object.keys(resultsData).length === 0) {
            alert('E-posta göndermek için önce hesaplama yapın.');
            return;
        }
        
        $.ajax({
            url: fitnessCalculatorSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_fitness_email',
                nonce: fitnessCalculatorSettings.nonce,
                email: email,
                data: JSON.stringify(resultsData)
            },
            beforeSend: function() {
                $('#send-email').prop('disabled', true).text('Gönderiliyor...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Rapor e-posta olarak gönderildi!');
                    $('#report-email').val('');
                } else {
                    alert('E-posta gönderilirken bir hata oluştu.');
                }
            },
            error: function() {
                alert('Sunucu ile iletişim kurulamadı.');
            },
            complete: function() {
                $('#send-email').prop('disabled', false).text('E-posta ile Gönder');
            }
        });
    });
    
    // Hesaplama tamamlandığında rapor butonunu göster
    $(document).on('calculation_completed', function() {
        $('#show-visualization').show();
    });
    
    $(document).on('vucut_yag_hesaplandi', function(event, data) {
        $('#show-visualization').show();
    });
    
    // Sosyal medya paylaşım butonları
    $(document).on('click', '#share-facebook', function() {
        shareOnFacebook();
    });
    
    $(document).on('click', '#share-twitter', function() {
        shareOnTwitter();
    });
    
    $(document).on('click', '#share-whatsapp', function() {
        shareOnWhatsApp();
    });
    
    $(document).on('click', '#copy-link', function() {
        copyToClipboard();
    });
    
    // Görselleştirme butonuna tıklama
    $(document).on('click', '#show-visualization', function() {
        $('#visualization-container').toggle();
        
        if ($('#visualization-container').is(':visible') && $('#visualization-content').is(':empty')) {
            createVisualization();
        }
    });
    
    // Sonuçları toplama fonksiyonu
    function collectResults() {
        const data = {
            date: new Date().toISOString(),
            calculation_type: ''
        };
        
        // Hangi tip hesaplama yapıldığını kontrol et
        const bodyFatResult = $('#bodyfat-result').text().trim();
        const bmrResult = $('#bmr-result').text().trim();
        
        // Vücut yağ hesaplama sonuçları
        if (bodyFatResult && bodyFatResult !== '0' && bodyFatResult !== '') {
            data.calculation_type = 'bodyfat';
            
            const genderElement = $('input[name="bf-gender"]:checked');
            data.gender = genderElement.length > 0 ? genderElement.val() : 'female';
            
            data.age = parseInt($('#bf-age').val()) || 30;
            data.height = parseFloat($('#bf-height').val()) || 170;
            data.weight = parseFloat($('#bf-weight').val()) || 70;
            data.neck = parseFloat($('#bf-neck').val()) || 0;
            data.waist = parseFloat($('#bf-waist').val()) || 0;
            data.hip = parseFloat($('#bf-hip').val()) || 0;
            data.bodyfat_percentage = parseFloat(bodyFatResult) || 0;
            data.fat_mass = parseFloat($('#fat-mass').text()) || 0;
            data.lean_mass = parseFloat($('#lean-mass').text()) || 0;
            data.category_name = getCurrentCategory();
            data.category_description = $('#category-description').text() || '';
        }
        
        // Fitness hesaplama sonuçları
        if (bmrResult && bmrResult !== '0' && bmrResult !== '') {
            data.calculation_type = data.calculation_type ? 'both' : 'fitness';
            
            if (!data.gender) {
                const genderElement = $('input[name="gender"]:checked');
                data.gender = genderElement.length > 0 ? genderElement.val() : 'female';
            }
            
            if (!data.age) data.age = parseInt($('#age').val()) || 30;
            if (!data.height) data.height = parseFloat($('#height').val()) || 170;
            if (!data.weight) data.weight = parseFloat($('#weight').val()) || 70;
            
            data.bmr = parseFloat(bmrResult) || 0;
            data.tdee = parseFloat($('#tdee-result').text()) || 0;
            data.target_calories = parseFloat($('#target-calories').text()) || 0;
            
            // BMI verileri - Sadece fitness hesaplayıcıdan
            data.bmi = parseFloat($('#bmi-result').text()) || 0;
            data.bmi_category = $('#bmi-category').text() || '';
            
            data.protein = $('#protein-result').text() || '';
            data.fat = $('#fat-result').text() || '';
            data.carbs = $('#carb-result').text() || '';
            data.water = $('#water-result').text() || '';
            
            // İdeal kilo verileri
            data.ideal_weight_range = $('#ideal-weight-range').text() || '';
            data.weight_difference = $('#weight-difference').text() || '';
            data.weight_recommendations = $('#weight-recommendations').text() || '';
        }
        
        if (!data.bodyfat_percentage && !data.bmr) {
            return null;
        }
        
        return data;
    }
    
    // Mevcut kategoriyi al
    function getCurrentCategory() {
        const bodyFatPercentage = parseFloat($('#bodyfat-result').text());
        const gender = $('input[name="bf-gender"]:checked').val();
        
        if (!bodyFatPercentage || !gender) return 'Hesaplanmadı';
        
        if (gender === 'male') {
            if (bodyFatPercentage < 6) return 'Temel Yağ';
            if (bodyFatPercentage < 14) return 'Atletik';
            if (bodyFatPercentage < 18) return 'Fitness';
            if (bodyFatPercentage < 25) return 'Normal';
            return 'Obezite';
        } else {
            if (bodyFatPercentage < 16) return 'Temel Yağ';
            if (bodyFatPercentage < 21) return 'Atletik';
            if (bodyFatPercentage < 25) return 'Fitness';
            if (bodyFatPercentage < 32) return 'Normal';
            return 'Obezite';
        }
    }
    
    // E-posta doğrulama
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Görselleştirme oluşturma
    function createVisualization() {
        const results = collectResults();
        
        if (!results || (!results.bodyfat_percentage && !results.bmr)) {
            $('#visualization-content').html('<p>Görselleştirme için önce hesaplama yapın.</p>');
            return;
        }
        
        let html = '<div class="results-visualization">';
        html += '<h3>Hesaplama Sonuçları Özeti</h3>';
        html += '<div class="results-summary-text">';
        
        if (results.bodyfat_percentage && results.bodyfat_percentage > 0) {
            html += '<div class="result-section">';
            html += '<h4>Vücut Kompozisyonu</h4>';
            html += '<p><strong>Vücut Yağ Oranı:</strong> ' + results.bodyfat_percentage + '%</p>';
            html += '<p><strong>Yağ Kütlesi:</strong> ' + results.fat_mass + ' kg</p>';
            html += '<p><strong>Yağsız Kütle:</strong> ' + results.lean_mass + ' kg</p>';
            html += '<p><strong>Kategori:</strong> ' + results.category_name + '</p>';
            html += '</div>';
        }
        
        if (results.bmr && results.bmr > 0) {
            html += '<div class="result-section">';
            html += '<h4>Kalori ve Makrolar</h4>';
            html += '<p><strong>BMR:</strong> ' + results.bmr + ' kcal/gün</p>';
            html += '<p><strong>TDEE:</strong> ' + results.tdee + ' kcal/gün</p>';
            html += '<p><strong>Hedef Kalori:</strong> ' + results.target_calories + ' kcal/gün</p>';
            
            if (results.bmi && results.bmi > 0) {
                html += '<p><strong>BMI:</strong> ' + results.bmi + ' kg/m² (' + results.bmi_category + ')</p>';
            }
            
            if (results.protein) html += '<p><strong>Protein:</strong> ' + results.protein + '</p>';
            if (results.fat) html += '<p><strong>Yağ:</strong> ' + results.fat + '</p>';
            if (results.carbs) html += '<p><strong>Karbonhidrat:</strong> ' + results.carbs + '</p>';
            if (results.water) html += '<p><strong>Su:</strong> ' + results.water + '</p>';
            
            // İdeal kilo bilgileri
            if (results.ideal_weight_range && results.ideal_weight_range !== '0 - 0 kg') {
                html += '<h5>İdeal Kilo Analizi:</h5>';
                html += '<p><strong>İdeal Kilo Aralığı:</strong> ' + results.ideal_weight_range + '</p>';
                if (results.weight_difference) html += '<p><strong>Hedef Kilo Farkı:</strong> ' + results.weight_difference + '</p>';
                if (results.weight_recommendations) html += '<p><strong>Öneriler:</strong> ' + results.weight_recommendations + '</p>';
            }
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
        
        $('#visualization-content').html(html);
    }
    
    // LocalStorage kaydetme
    function saveToLocalStorage() {
        try {
            const results = collectResults();
            if (!results || (!results.bodyfat_percentage && !results.bmr)) {
                return;
            }
            
            let saved = JSON.parse(localStorage.getItem('fitness_results') || '[]');
            saved.unshift(results);
            saved = saved.slice(0, 5);
            localStorage.setItem('fitness_results', JSON.stringify(saved));
        } catch (e) {
            // LocalStorage kullanılamıyor, sessizce geç
        }
    }
    
    // Hesaplama tamamlandığında kaydet
    $(document).on('calculation_completed', function() {
        setTimeout(saveToLocalStorage, 1000);
    });
    
    $(document).on('vucut_yag_hesaplandi', function() {
        setTimeout(saveToLocalStorage, 1000);
    });
    
    // Sosyal medya paylaşım fonksiyonları
    function generateShareText() {
        const results = collectResults();
        
        if (!results) {
            return 'Fitness hesaplamalarımı yaptım!';
        }
        
        let shareText = 'Fitness hesaplamalarımı yaptım! 💪\n\n';
        
        if (results.bodyfat_percentage && results.bodyfat_percentage > 0) {
            shareText += `🎯 Vücut Yağ Oranım: ${results.bodyfat_percentage}%\n`;
            shareText += `⚖️ Yağsız Kütle: ${results.lean_mass} kg\n`;
        }
        
        if (results.bmr && results.bmr > 0) {
            shareText += `🔥 Günlük Kalori İhtiyacım: ${results.tdee} kcal\n`;
            shareText += `🎯 Hedef Kalori: ${results.target_calories} kcal\n`;
        }
        
        if (results.ideal_weight_range && results.ideal_weight_range !== '0 - 0 kg') {
            shareText += `📊 İdeal Kilo Aralığım: ${results.ideal_weight_range}\n`;
        }
        
        shareText += '\n#fitness #sağlık #beslenme';
        
        return shareText;
    }
    
    function getCurrentPageUrl() {
        return window.location.href;
    }
    
    function shareOnFacebook() {
        const shareText = generateShareText();
        const pageUrl = getCurrentPageUrl();
        const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}&quote=${encodeURIComponent(shareText)}`;
        
        window.open(facebookUrl, 'facebook-share', 'width=600,height=400,scrollbars=yes,resizable=yes');
    }
    
    function shareOnTwitter() {
        const shareText = generateShareText();
        const pageUrl = getCurrentPageUrl();
        const twitterText = shareText.length > 240 ? shareText.substring(0, 200) + '...' : shareText;
        const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(twitterText)}&url=${encodeURIComponent(pageUrl)}`;
        
        window.open(twitterUrl, 'twitter-share', 'width=600,height=400,scrollbars=yes,resizable=yes');
    }
    
    function shareOnWhatsApp() {
        const shareText = generateShareText();
        const pageUrl = getCurrentPageUrl();
        const whatsappText = shareText + '\n\n' + pageUrl;
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(whatsappText)}`;
        
        window.open(whatsappUrl, '_blank');
    }
    
    function copyToClipboard() {
        const shareText = generateShareText();
        const pageUrl = getCurrentPageUrl();
        const fullText = shareText + '\n\n' + pageUrl;
        
        // Modern tarayıcılar için Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(fullText).then(function() {
                showCopySuccess();
            }).catch(function() {
                fallbackCopyToClipboard(fullText);
            });
        } else {
            // Eski tarayıcılar için fallback
            fallbackCopyToClipboard(fullText);
        }
    }
    
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopySuccess();
        } catch (err) {
            alert('Kopyalama başarısız oldu. Lütfen manuel olarak kopyalayın.');
        }
        
        document.body.removeChild(textArea);
    }
    
    function showCopySuccess() {
        const copyBtn = $('#copy-link');
        const originalText = copyBtn.html();
        
        copyBtn.html('<span class="dashicons dashicons-yes"></span> Kopyalandı!');
        copyBtn.addClass('copied');
        
        setTimeout(function() {
            copyBtn.html(originalText);
            copyBtn.removeClass('copied');
        }, 2000);
    }
});

<?php
session_start();

// التحقق: إذا اكتمل العرض سابقاً، يتم التحويل فوراً إلى الصفحة الرئيسية.
if (isset($_SESSION['splashed_complete']) && $_SESSION['splashed_complete'] === true) {
    header('Location: main.php');
    exit();
}

// تثبيت المتغير للإشارة إلى أن الحركة ستبدأ الآن (وسيتم التحويل بعد الانتهاء).
$_SESSION['splashed_complete'] = true; 
?>
<!DOCTYPE html>
<html lang="en" dir="ltr"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athar Graduate</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/vanilla-tilt@1.8.1/dist/vanilla-tilt.min.js"></script>
    
    <style>
    body {
        margin: 0; padding: 0; display: flex; justify-content: center; align-items: center;
        min-height: 100vh; width: 100vw; overflow: hidden; font-family: 'Arial', sans-serif;
        background-color: #a8cee1ff; color: #2c3e50; 
    }
    .split-cover-left, .split-cover-right {
        position: fixed; top: 0; width: 50vw; height: 100vh; background-color: #c8e4eb; z-index: 99; 
    }
    .split-cover-left { left: 0; }
    .split-cover-right { right: 0; }
    .hero-content-wrapper {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex;
        justify-content: center; align-items: center; transform-style: preserve-3d;
        opacity: 0; text-align: center; cursor: pointer; z-index: 10;
    }
    .content-inner {
        transform-style: preserve-3d; max-width: 1000px; padding: 20px;
    }
    .main-icon {
        font-size: 6em; margin-bottom: 30px; transform: translateZ(30px); color: #f64040ff;
        filter: drop-shadow(0 0 10px rgba(246, 64, 64, 0.5)); 
    }
    .main-title {
        font-size: 5.5em; margin-bottom: 30px; font-weight: bold; line-height: 1.1;
        transform-style: preserve-3d; color: #2c3e50;
    }
    /* ⬅️ يجب أن تكون العناصر المدمجة (الحروف) 'inline-block' للتحكم في حركتها */
    .main-title .char { 
        display: inline-block; opacity: 0; transform: translateY(-50px); /* ⬅️ الإعداد الأولي للحركة */
    }
    .main-description {
        font-size: 1.5em; margin-top: 30px; line-height: 1.5; transform: translateZ(15px); 
        opacity: 0; max-width: 700px; margin-left: auto; margin-right: auto; color: #f64040ff; 
    }
    </style>
</head>
<body>

    <div class="split-cover-left" id="coverLeft"></div>
    <div class="split-cover-right" id="coverRight"></div>

    <div class="hero-content-wrapper" id="heroWrapper">
        <div class="content-inner">
            <div class="main-icon"></div>
            <h1 class="main-title" id="mainTitle">Athar Graduate</h1>
            <p class="main-description">
                Elevating the future of graduates with the power of modern technology and innovative design.
            </p>
        </div>
    </div>

    <script>
        const coverLeft = document.getElementById('coverLeft');
        const coverRight = document.getElementById('coverRight');
        const heroContentWrapper = document.getElementById('heroWrapper');
        const mainTitle = document.getElementById('mainTitle');
        const mainIcon = document.querySelector('.main-icon');
        const mainDescription = document.querySelector('.main-description');
        
        // --- دالة مساعدة لتقسيم النص إلى حروف (Character Split) ---
        function splitTextIntoChars(element) {
            const text = element.textContent;
            element.innerHTML = ''; 
            const chars = [];
            
            // يتم تقسيم النص إلى حروف، مع معالجة المسافات
            text.split('').forEach(charText => {
                const charSpan = document.createElement('span');
                
                if (charText === ' ') {
                    // للحفاظ على المسافات بين الكلمات بشكل صحيح
                    charSpan.innerHTML = '&nbsp;'; 
                } else {
                    charSpan.textContent = charText;
                }
                
                charSpan.style.display = 'inline-block';
                charSpan.classList.add('char'); // ⬅️ إضافة كلاس جديد
                element.appendChild(charSpan);
                chars.push(charSpan);
            });
            return chars;
        }

        const titleChars = splitTextIntoChars(mainTitle); // ⬅️ الآن نقوم بتقسيم الحروف

        // --- تشغيل GSAP Timeline ---
        const masterTimeline = gsap.timeline({
            delay: 0.5,
            onComplete: () => {
                // التحويل إلى الصفحة الرئيسية بعد ثانية واحدة من انتهاء الحركة
                setTimeout(() => {
                    window.location.href = 'main.php'; 
                }, 1000); 
            }
        });

        // 1. حركة الستار المنقسم
        masterTimeline.to(coverLeft, { x: "-100%", duration: 1.5, ease: "power3.inOut" })
        .to(coverRight, { x: "100%", duration: 1.5, ease: "power3.inOut" }, "<") 

        // 2. ظهور المحتوى الكلي
        .to(heroContentWrapper, { opacity: 1, duration: 0.01 }, "-=0.8") 

        // 3. ظهور الأيقونة والوصف (بدون تغيير)
        .from([mainIcon, mainDescription], {
            opacity: 0,
            y: 30,
            duration: 0.8,
            ease: "power2.out",
            stagger: 0.2
        }, "-=0.8")

        // 4. ظهور الحروف (التأثير الجديد)
        .to(titleChars, { // ⬅️ استهداف مصفوفة الحروف الجديدة
            opacity: 1,
            y: 0, // ⬅️ ينزل الحرف من الأعلى (حيث transform: translateY(-50px) كان الإعداد الأولي)
            duration: 0.4, // ⬅️ زمن حركة كل حرف
            ease: "back.out(1.7)", // ⬅️ تأثير الارتداد
            stagger: 0.05, // ⬅️ فاصل زمني قصير بين كل حرف (لتحقيق تأثير LTR)
        }, "-=0.5");
        
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Athar Graduate Platform Reveal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/vanilla-tilt@1.8.1/dist/vanilla-tilt.min.js"></script>
    
    <style>
        /* 1. تنسيقات CSS الأساسية (Blue/Red Theme) */
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            width: 100vw;
            overflow: hidden; 
            font-family: 'Arial', sans-serif;
            
            /* خلفية الصفحة: أزرق فاتح/سماوي (#a8cee1ff) */
            background-color: #a8cee1ff; 
            /* لون النص الداكن ليظهر على الخلفية الفاتحة */
            color: #2c3e50; 
        }

        /* 2. طبقات الستار المنقسم (الستارة المغطية) */
        .split-cover-left, .split-cover-right {
            position: fixed;
            top: 0;
            width: 50vw; 
            height: 100vh;
            /* لون الستار: أزرق فاتح جدًا (#c8e4eb) */
            background-color: #c8e4eb; 
            z-index: 99; 
        }

        .split-cover-left { left: 0; }
        .split-cover-right { right: 0; }
        
        /* 3. Hero Content Wrapper - المحتوى الرئيسي (المكشوف) */
        .hero-content-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            transform-style: preserve-3d;
            /* يبدأ مخفياً تحت الستار */
            opacity: 0; 
            text-align: center;
            cursor: pointer;
            z-index: 10;
        }

        .content-inner {
            transform-style: preserve-3d;
            max-width: 1000px; 
            padding: 20px;
        }

        .main-icon {
            font-size: 6em; 
            margin-bottom: 30px;
            transform: translateZ(30px); 
            /* لون لهجة: أحمر ساطع (#f64040ff) */
            color: #f64040ff;
            filter: drop-shadow(0 0 10px rgba(246, 64, 64, 0.5)); 
        }

        .main-title {
            font-size: 5.5em; 
            margin-bottom: 30px;
            font-weight: bold; 
            line-height: 1.1;
            transform-style: preserve-3d;
            /* لون النص الداكن */
            color: #2c3e50;
        }

        /* تنسيقات كلمات العنوان للتحريك الفردي */
        .main-title .word {
            display: inline-block;
            opacity: 0; 
            transform: translateY(20px);
        }

        .main-description {
            font-size: 1.5em; 
            margin-top: 30px;
            line-height: 1.5;
            transform: translateZ(15px); 
            opacity: 0; 
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            /* لون لهجة: أحمر ساطع (#f64040ff) */
            color: #f64040ff; 
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
                A digital platform specialized in facilitating the request and management of academic recommendation letters between students and faculty.
            </p>
        </div>
    </div>

    <script>
        // 4. كود JavaScript لتنفيذ الحركة
        
        const coverLeft = document.getElementById('coverLeft');
        const coverRight = document.getElementById('coverRight');
        const heroContentWrapper = document.getElementById('heroWrapper');
        const mainTitle = document.getElementById('mainTitle');
        const mainIcon = document.querySelector('.main-icon');
        const mainDescription = document.querySelector('.main-description');
        
        // --- دالة مساعدة لتقسيم النص إلى كلمات (Word Split) ---
        function splitTextIntoWords(element) {
            const text = element.textContent;
            element.innerHTML = ''; 
            const words = [];
            
            // استخدام التعبير العادي للتعامل مع الفراغات المتعددة بشكل أفضل
            text.split(/\s+/).forEach(wordText => {
                if (wordText) {
                    const wordSpan = document.createElement('span');
                    wordSpan.textContent = wordText;
                    wordSpan.style.display = 'inline-block';
                    wordSpan.classList.add('word');
                    element.appendChild(wordSpan);
                    words.push(wordSpan);
                    
                    element.appendChild(document.createTextNode(' '));
                }
            });
            return words;
        }

        // 1. تقسيم العنوان إلى كلمات
        const titleWords = splitTextIntoWords(mainTitle); 

        // --- تشغيل GSAP Timeline (Split & Slide) ---
        const masterTimeline = gsap.timeline({
            delay: 0.5, // تأخير بسيط قبل البدء
            
            // 🔥🔥 أمر الانتقال عند اكتمال الحركة بالكامل
            onComplete: () => {
                // تأخير لمدة 1000 مللي ثانية (ثانية واحدة) بعد انتهاء الحركة 
                setTimeout(() => {
                    window.location.href = 'main.php'; // الانتقال إلى main.php
                }, 1000); 
            }
        });

        // 1. حركة الستار المنقسم (Split Cover Slide Out)
        masterTimeline.to(coverLeft, {
            x: "-100%", 
            duration: 1.5,
            ease: "power3.inOut"
        })
        .to(coverRight, {
            x: "100%", 
            duration: 1.5,
            ease: "power3.inOut"
        }, "<") 

        // 2. ظهور المحتوى الكلي (Fade In)
        .to(heroContentWrapper, {
            opacity: 1,
            duration: 0.01, 
        }, "-=0.8") 

        // 3. ظهور العناصر (Slide Up and Stagger)
        .from([mainIcon, mainDescription], {
            opacity: 0,
            y: 30,
            duration: 0.8,
            ease: "power2.out",
            stagger: 0.2
        }, "-=0.8")

        // 4. ظهور الكلمات بطريقة مبهرة (Staggered Word Reveal)
        .to(titleWords, {
            opacity: 1,
            y: 0, 
            duration: 0.6,
            ease: "back.out(1.7)", 
            stagger: 0.1, 
        }, "-=0.5");
        
    </script>
</body>
</html>
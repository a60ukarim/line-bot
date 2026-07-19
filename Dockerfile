# 1. استخدام النسخة الرسمية لـ PHP 8.2 مع سيرفر Apache المدمج
FROM php:8.2-apache

# 2. تفعيل مود الـ Rewrite في Apache (مهم جداً للـ Routing والـ APIs)
RUN a2enmod rewrite

# 3. نسخ كل ملفات المشروع (بما فيها index.php) إلى مجلد السيرفر الافتراضي
COPY . /var/www/html/

# 4. فتح المنفذ 80 اللي بيشتغل عليه Apache افتراضياً
EXPOSE 80

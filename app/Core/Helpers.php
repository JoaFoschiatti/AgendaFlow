<?php

namespace App\Core;

class Helpers
{
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
    
    public static function formatPrice(float $price): string
    {
        return '$' . number_format($price, 2, ',', '.');
    }
    
    public static function formatDate($date, string $format = 'd/m/Y'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        return $date->format($format);
    }
    
    public static function formatDateSpanish($date, string $format = 'd/m/Y'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        // Get the formatted date
        $formattedDate = $date->format($format);
        
        // Replace English day names with Spanish
        $englishDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $spanishDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $formattedDate = str_replace($englishDays, $spanishDays, $formattedDate);
        
        // Replace English month names with Spanish
        $englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $spanishMonths = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $formattedDate = str_replace($englishMonths, $spanishMonths, $formattedDate);
        
        return $formattedDate;
    }
    
    public static function formatDateTime($date, string $format = 'd/m/Y H:i'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date, new \DateTimeZone('America/Argentina/Cordoba'));
        }
        return $date->format($format);
    }
    
    public static function getDayName(int $day): string
    {
        $days = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];
        
        return $days[$day] ?? '';
    }
    
    public static function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
        
        return $months[$month] ?? '';
    }
    
    public static function slug(string $text): string
    {
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        
        // Remove leading/trailing hyphens
        $text = trim($text, '-');
        
        // Convert to lowercase
        return strtolower($text);
    }
    
    public static function generateWhatsAppLink(string $phone, string $message = ''): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add Argentina country code if not present
        if (!str_starts_with($phone, '54')) {
            // Remove leading 0 if present
            $phone = ltrim($phone, '0');
            
            // Add country code
            $phone = '54' . $phone;
        }
        
        $message = urlencode($message);
        
        return "https://wa.me/{$phone}" . ($message ? "?text={$message}" : '');
    }
    
    public static function timeAgo($datetime): string
    {
        $now = new \DateTime();
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) {
            return 'hace ' . $diff->y . ' ' . ($diff->y == 1 ? 'año' : 'años');
        }
        if ($diff->m > 0) {
            return 'hace ' . $diff->m . ' ' . ($diff->m == 1 ? 'mes' : 'meses');
        }
        if ($diff->d > 0) {
            return 'hace ' . $diff->d . ' ' . ($diff->d == 1 ? 'día' : 'días');
        }
        if ($diff->h > 0) {
            return 'hace ' . $diff->h . ' ' . ($diff->h == 1 ? 'hora' : 'horas');
        }
        if ($diff->i > 0) {
            return 'hace ' . $diff->i . ' ' . ($diff->i == 1 ? 'minuto' : 'minutos');
        }
        
        return 'hace unos segundos';
    }
    
    public static function getTrialDaysRemaining(string $trialEndsAt): int
    {
        $now = new \DateTime();
        $trialEnd = new \DateTime($trialEndsAt);
        
        if ($now >= $trialEnd) {
            return 0;
        }
        
        $diff = $now->diff($trialEnd);
        return $diff->days + 1; // Include today
    }
    
    public static function isTrialExpired(string $trialEndsAt): bool
    {
        $now = new \DateTime();
        $trialEnd = new \DateTime($trialEndsAt);
        
        return $now > $trialEnd;
    }
}
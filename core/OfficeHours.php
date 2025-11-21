<?php
/**
 * Office Hours & Holiday System
 * Mengecek status kantor (Buka/Tutup/Istirahat/Libur)
 * Support input hari dalam Bahasa Indonesia atau Inggris
 * * @author BTIKP Kalsel
 * @version 1.2
 */

class OfficeHours {
    private $db;
    private $settings = [];
    
    // Mapping English -> Indonesia (Untuk Output Tampilan)
    private $engToIndoMap = [
        'monday'    => 'Senin',
        'tuesday'   => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday'  => 'Kamis',
        'friday'    => 'Jumat',
        'saturday'  => 'Sabtu',
        'sunday'    => 'Minggu'
    ];

    // Mapping Indonesia -> English (Untuk Logika Sistem)
    private $indoToEngMap = [
        'senin'     => 'monday',
        'selasa'    => 'tuesday',
        'rabu'      => 'wednesday',
        'kamis'     => 'thursday',
        'jumat'     => 'friday',
        'sabtu'     => 'saturday',
        'minggu'    => 'sunday'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
    }
    
    /**
     * Load settings from database
     */
    private function loadSettings() {
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings WHERE `group` = 'office'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->settings[$row['key']] = $row['value'];
        }
    }

    /**
     * Normalisasi nama hari ke format Inggris (lowercase)
     * Contoh: "Senin" -> "monday", "Monday" -> "monday"
     */
    private function normalizeDayToEnglish($day) {
        $day = strtolower(trim($day));
        // Jika input adalah bahasa indonesia, translate ke inggris
        if (isset($this->indoToEngMap[$day])) {
            return $this->indoToEngMap[$day];
        }
        // Jika tidak, asumsikan sudah inggris
        return $day;
    }
    
    /**
     * Get current office status
     * @return array|null
     */
    public function getStatus() {
        // Check if office status display is enabled
        if (empty($this->settings['office_show_status']) || $this->settings['office_show_status'] != '1') {
            return null;
        }
        
        // Get current datetime (PHP selalu return English)
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $currentDayEng = strtolower($now->format('l')); // monday, tuesday, etc
        $currentTime = $now->format('H:i');
        $currentDate = $now->format('Y-m-d');
        
        // Siapkan nama hari untuk tampilan (Indo)
        $displayDay = $this->engToIndoMap[$currentDayEng] ?? ucfirst($currentDayEng);

        // Check if today is holiday
        if ($this->isHoliday($currentDate)) {
            return [
                'status' => 'holiday',
                'message' => 'Hari Libur',
                'detail' => 'Libur Nasional',
                'badge' => 'bg-red-600',
                'icon' => 'fa-calendar-times',
                'day' => $displayDay
            ];
        }
        
        // Ambil setting hari kerja (Bisa "monday,tuesday" ATAU "senin,selasa")
        $rawDays = explode(',', $this->settings['office_working_days'] ?? 'monday,tuesday,wednesday,thursday,friday');
        
        // Normalisasi hari kerja ke Inggris agar bisa dicocokkan
        $workingDaysEng = [];
        foreach($rawDays as $d) {
            $workingDaysEng[] = $this->normalizeDayToEnglish($d);
        }
        
        // Logika: Cek apakah hari ini (Inggris) ada di array hari kerja (Inggris)
        if (!in_array($currentDayEng, $workingDaysEng)) {
            return [
                'status' => 'closed',
                'message' => 'Tutup',
                'detail' => 'Hari Libur Mingguan',
                'badge' => 'bg-gray-600',
                'icon' => 'fa-door-closed',
                'day' => $displayDay
            ];
        }
        
        // Get office hours
        $startTime = $this->settings['office_start_time'] ?? '08:00';
        $endTime = $this->settings['office_end_time'] ?? '16:00';
        $breakStart = $this->settings['office_break_start'] ?? '12:00';
        $breakEnd = $this->settings['office_break_end'] ?? '13:00';
        
        // Check if currently on break time
        if ($currentTime >= $breakStart && $currentTime < $breakEnd) {
            return [
                'status' => 'break',
                'message' => 'Istirahat',
                'detail' => 'Jam Istirahat',
                'badge' => 'bg-yellow-600',
                'icon' => 'fa-mug-hot',
                'day' => $displayDay
            ];
        }
        
        // Check if office is open
        if ($currentTime >= $startTime && $currentTime < $endTime) {
            return [
                'status' => 'open',
                'message' => 'Buka',
                'detail' => 'Jam Kerja',
                'badge' => 'bg-green-600',
                'icon' => 'fa-door-open',
                'day' => $displayDay
            ];
        }
        
        // Office is closed (outside working hours)
        return [
            'status' => 'closed',
            'message' => 'Tutup',
            'detail' => 'Di Luar Jam Kerja',
            'badge' => 'bg-gray-600',
            'icon' => 'fa-door-closed',
            'day' => $displayDay
        ];
    }
    
    /**
     * Check if given date is holiday
     */
    public function isHoliday($date) {
        $holidays = $this->settings['office_holiday_dates'] ?? '';
        if (empty($holidays)) {
            return false;
        }
        $holidayList = explode(',', $holidays);
        $holidayList = array_map('trim', $holidayList);
        return in_array($date, $holidayList);
    }
    
    /**
     * Get formatted working hours text (Output Bahasa Indonesia)
     */
    public function getFormattedWorkingHours() {
        $days = $this->settings['office_working_days'] ?? 'monday,tuesday,wednesday,thursday,friday';
        $startTime = $this->settings['office_start_time'] ?? '08:00';
        $endTime = $this->settings['office_end_time'] ?? '16:00';
        
        $daysArray = explode(',', $days);
        $daysArray = array_map('trim', $daysArray);
        
        // Konversi input (Indo/Ing) -> Inggris -> Indonesia
        $daysIndo = array_map(function($day) {
            $eng = $this->normalizeDayToEnglish($day);
            return $this->engToIndoMap[$eng] ?? ucfirst($day);
        }, $daysArray);
        
        // Format tampilan: "Senin - Jumat"
        if (count($daysIndo) > 1) {
            // Mengambil hari pertama dan terakhir dari array
            $daysText = reset($daysIndo) . ' - ' . end($daysIndo);
        } else {
            $daysText = implode(', ', $daysIndo);
        }
        
        return $daysText . ', ' . substr($startTime, 0, 5) . ' - ' . substr($endTime, 0, 5) . ' WIB';
    }
}
?>
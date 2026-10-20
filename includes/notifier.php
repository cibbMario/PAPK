<?php
// includes/notifier.php
// Modul Notifikasi Terpadu (In-App, WhatsApp Gateway & Telegram) PAPK TNI AU

require_once __DIR__ . '/../config/koneksi.php';

if (!function_exists('getGatewaySetting')) {
    function getGatewaySetting($key, $default = '') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT key_value FROM gateway_settings WHERE key_name = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('setGatewaySetting')) {
    function setGatewaySetting($key, $value, $description = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO gateway_settings (key_name, key_value, description) 
                                   VALUES (:k, :v, :d) 
                                   ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)");
            return $stmt->execute([':k' => $key, ':v' => $value, ':d' => $description]);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('sendNotificationToUser')) {
    /**
     * Kirim notifikasi ke user tertentu (In-App dan WhatsApp)
     */
    function sendNotificationToUser($userId, $title, $message, $type = 'info') {
        global $pdo;
        $result = ['in_app' => false, 'whatsapp' => false];

        // 1. Simpan In-App Notification
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (:uid, :type, :msg, 0)");
            $stmt->execute([
                ':uid'  => $userId,
                ':type' => $type,
                ':msg'  => "[$title] " . $message
            ]);
            $result['in_app'] = true;
        } catch (Exception $e) {
            // Ignore in-app error
        }

        // 2. Ambil data telepon anggota dari akun user
        try {
            $stmtUser = $pdo->prepare("SELECT u.id, u.username, m.name, m.phone, m.rank 
                                      FROM users u 
                                      LEFT JOIN members m ON u.member_id = m.id 
                                      WHERE u.id = :uid LIMIT 1");
            $stmtUser->execute([':uid' => $userId]);
            $usr = $stmtUser->fetch();

            if ($usr && !empty($usr['phone'])) {
                $recipientName = $usr['name'] ?: $usr['username'];
                $result['whatsapp'] = dispatchWhatsApp($usr['phone'], $recipientName, $title, $message);
            }
        } catch (Exception $e) {
            // Ignore fetch error
        }

        return $result;
    }
}

if (!function_exists('broadcastNotificationToAll')) {
    /**
     * Siaran pengumuman ke seluruh perwira aktif (In-App & WhatsApp)
     */
    function broadcastNotificationToAll($title, $message, $category = 'pengumuman') {
        global $pdo;
        $count = 0;

        try {
            // Ambil semua user aktif beserta anggota
            $stmt = $pdo->query("SELECT u.id as user_id, u.username, m.name, m.phone, m.rank 
                                 FROM users u 
                                 LEFT JOIN members m ON u.member_id = m.id 
                                 WHERE u.status = 'approved'");
            $users = $stmt->fetchAll();

            $sendWa = (getGatewaySetting('notify_announcement', '1') === '1');

            foreach ($users as $u) {
                // In-App
                try {
                    $ins = $pdo->prepare("INSERT INTO notifications (user_id, type, message, is_read) VALUES (:uid, :type, :msg, 0)");
                    $ins->execute([
                        ':uid'  => $u['user_id'],
                        ':type' => $category,
                        ':msg'  => "[$title] " . $message
                    ]);
                } catch (Exception $e) {}

                // WhatsApp Gateway
                if ($sendWa && !empty($u['phone'])) {
                    $recipientName = $u['name'] ?: $u['username'];
                    dispatchWhatsApp($u['phone'], $recipientName, $title, $message);
                }
                $count++;
            }
        } catch (Exception $e) {
            // Ignore broadcast error
        }

        return $count;
    }
}

if (!function_exists('dispatchWhatsApp')) {
    /**
     * Engine pengiriman WhatsApp (Live API / Simulation Log)
     */
    function dispatchWhatsApp($targetPhone, $recipientName, $title, $message) {
        global $pdo;

        // Normalisasi nomor HP ke format Indonesia (62xxx)
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $mode = getGatewaySetting('wa_mode', 'simulation');
        $token = getGatewaySetting('wa_api_token', '');
        $sender = getGatewaySetting('wa_sender_number', '0811-9876-PAPK');

        // Template pesan WhatsApp dinas
        $waBody = "🇮🇩 *KEMENTERIAN PERTAHANAN / MABESAU*\n";
        $waBody .= "*Sistem Informasi Perwira Karier (PAPK)*\n";
        $waBody .= "===============================\n";
        $waBody .= "Kepada Yth: *" . addslashes($recipientName) . "*\n\n";
        $waBody .= "📌 *NOTIFIKASI: " . strtoupper($title) . "*\n";
        $waBody .= $message . "\n\n";
        $waBody .= "-------------------------------\n";
        $waBody .= "_Pesan resmi otomatis dikirim melalui Gateway PAPK TNI AU._\n";
        $waBody .= "_Waktu kirim: " . date('d-m-Y H:i:s') . " WIB_";

        $status = 'simulated';

        if ($mode === 'live' && !empty($token)) {
            // Pengiriman via API Fonnte / Wablas
            $provider = getGatewaySetting('wa_provider', 'fonnte');
            if ($provider === 'fonnte') {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.fonnte.com/send',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => [
                        'target' => $cleanPhone,
                        'message' => $waBody,
                        'countryCode' => '62'
                    ],
                    CURLOPT_HTTPHEADER => [
                        "Authorization: $token"
                    ],
                ]);
                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                $status = ($httpCode == 200) ? 'sent' : 'failed';
            }
        }

        // Catat log pengiriman di tabel notification_logs
        try {
            $stmtLog = $pdo->prepare("INSERT INTO notification_logs (channel, recipient, recipient_name, title, message, status) 
                                      VALUES ('whatsapp', :rec, :name, :title, :msg, :st)");
            $stmtLog->execute([
                ':rec'   => $cleanPhone,
                ':name'  => $recipientName,
                ':title' => $title,
                ':msg'   => $waBody,
                ':st'    => $status
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

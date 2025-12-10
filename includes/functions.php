<?php
if (!defined('ABSPATH')) exit;


function sav_panel_url($path=''){ $b = home_url('/service-agent-management'); return trailingslashit($b).ltrim($path,'/'); }

add_action('init', function(){
  if (class_exists('SAV_DB')) { SAV_DB::ensure(); }
  add_rewrite_rule('^service-agent-management/?$','index.php?sav_page=login','top');
  add_rewrite_rule('^service-agent-management/dashboard/?$','index.php?sav_page=dashboard','top');
  // add_rewrite_rule('^agent/([a-z0-9\-]{8,64})/?$','index.php?agent_token=$matches[1]','top');
  add_rewrite_rule('^service-agent/([^/]+)/?$', 'index.php?agent_token=$matches[1]', 'top');
  add_filter('query_vars', function($vars){ $vars[]='sav_page'; $vars[]='agent_token'; return $vars; });
});

add_action('template_redirect', function(){
  $page = get_query_var('sav_page');
  $token= get_query_var('agent_token');
  if ($page){
    if ($page==='dashboard' && !SAV_Auth::is_logged_in()){ wp_safe_redirect(home_url('/service-agent-management')); exit; }
    $file = SAV_PATH.'templates/'.$page.'.php';
    if (file_exists($file)){ include $file; exit; }
  }
  if (!empty($token)){
    $file = SAV_PATH.'templates/public-agent.php';
    if (file_exists($file)){ include $file; exit; }
  }
});

// CRUD handler
add_action('init', function(){
  if (!isset($_POST['sav_action'])) return;
  if (!SAV_Auth::is_logged_in()) return;
  check_admin_referer('sav_form_nonce','sav_form_nonce');
  global $wpdb;
  $agents=$wpdb->prefix.'service_agents';
  $admins=$wpdb->prefix.'service_agents_admins';

  $upload_photo = function($field){
    if (empty($_FILES[$field]['name'])) return null;
    require_once ABSPATH.'wp-admin/includes/file.php';
    $res = wp_handle_upload($_FILES[$field], ['test_form'=>false]);
    return (is_array($res) && empty($res['error'])) ? $res['url'] : null;
  };

  switch ($_POST['sav_action']) {
  case 'agent_save':
    $id = intval($_POST['id'] ?? 0);
    
    // فقط sanitize تاریخ
    $valid_until = !empty($_POST['valid_until']) ? sanitize_text_field($_POST['valid_until']) : null;
    if ($valid_until && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_until)) {
        $_SESSION['sav_error'] = 'فرمت تاریخ نامعتبر است.';
        wp_safe_redirect(home_url('/service-agent-management/dashboard'));
        exit;
    }

    $data = [
        'person_name'     => sanitize_text_field($_POST['person_name'] ?? ''),
        'province'        => sanitize_text_field($_POST['province'] ?? ''),
        'area'            => sanitize_text_field($_POST['area'] ?? ''),
        'agency_name'     => sanitize_text_field($_POST['agency_name'] ?? ''),
        'agency_code'     => sanitize_text_field($_POST['agency_code'] ?? ''),
        'agency_phone'    => sanitize_text_field($_POST['agency_phone'] ?? ''),
        'national_id'     => sanitize_text_field($_POST['national_id'] ?? ''),
        'person_phone'    => sanitize_text_field($_POST['person_phone'] ?? ''),
        'activity_desc'   => sanitize_text_field($_POST['activity_desc'] ?? ''),
        'status'          => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'inactive',
        'valid_until'     => $valid_until,
    ];

    // --- آپلود تصویر ---
    $old_photo = $id ? $wpdb->get_var($wpdb->prepare("SELECT photo_url FROM `{$wpdb->prefix}service_agents` WHERE id=%d", $id)) : '';

    if (!empty($_FILES['photo']['name'])) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = ['test_form' => false];
        $movefile = wp_handle_upload($_FILES['photo'], $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // حذف عکس قبلی
            if ($old_photo && file_exists($old_photo)) {
                @unlink($old_photo);
            }

            // انتقال به پوشه سفارشی
            $upload_dir = wp_upload_dir();
            $custom_dir = $upload_dir['basedir'] . '/sepehr-service-agents-pics';
            $filename = wp_unique_filename($custom_dir, basename($movefile['file']));
            $new_file = $custom_dir . '/' . $filename;

            if (rename($movefile['file'], $new_file)) {
                $data['photo_url'] = $upload_dir['baseurl'] . '/sepehr-service-agents-pics/' . $filename;
            } else {
                $data['photo_url'] = $movefile['url']; // fallback
            }
        } else {
            $_SESSION['sav_error'] = 'خطا در آپلود تصویر: ' . ($movefile['error'] ?? 'نامشخص');
            wp_safe_redirect(home_url('/service-agent-management/dashboard'));
            exit;
        }
    } else {
        $data['photo_url'] = $old_photo; // نگه داشتن عکس قبلی
    }

    // ذخیره در دیتابیس
    if ($id) {
        $wpdb->update("{$wpdb->prefix}service_agents", $data, ['id' => $id]);
    } else {
        $wpdb->insert("{$wpdb->prefix}service_agents", array_merge($data, ['token' => wp_generate_uuid4()]));
        $id = $wpdb->insert_id;
    }

    wp_safe_redirect(home_url('/service-agent-management/dashboard?edit=' . $id));
    exit;

    case 'agent_delete':
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $photo_url = $wpdb->get_var($wpdb->prepare("SELECT photo_url FROM `{$wpdb->prefix}service_agents` WHERE id=%d", $id));
        if ($photo_url) {
            $upload_dir = wp_upload_dir();
            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo_url);
            if (file_exists($local_path)) {
                @unlink($local_path);
            }
        }
        $wpdb->delete("{$wpdb->prefix}service_agents", ['id' => $id]);
    }
    wp_safe_redirect(home_url('/service-agent-management/dashboard'));
    exit;

    case 'user_save':
      if (!SAV_Auth::current_user() || !SAV_Auth::user_can('manage_users')) { wp_die('Forbidden'); }
      $id = intval($_POST['id'] ?? 0);
      $data=[
        'username' => sanitize_user($_POST['username'] ?? ''),
        'full_name'=> sanitize_text_field($_POST['full_name'] ?? ''),
        'role'     => in_array(($_POST['role'] ?? 'editor'), ['admin','editor'], true) ? $_POST['role'] : 'editor',
        'status'   => in_array(($_POST['status'] ?? 'active'), ['active','inactive'], true) ? $_POST['status'] : 'active',
      ];
      if (!empty($_POST['password'])) { $data['password'] = wp_hash_password($_POST['password']); }
      if ($id>0) { $wpdb->update($admins,$data,['id'=>$id]); } else { $wpdb->insert($admins,$data); }
      if (!empty($wpdb->last_error)) { $_SESSION['sav_error']='DB Error: '.$wpdb->last_error; }
      wp_safe_redirect(home_url('/service-agent-management/dashboard')); exit;

    case 'user_delete':
      if (!SAV_Auth::current_user() || !SAV_Auth::user_can('manage_users')) { wp_die('Forbidden'); }
      $id = intval($_POST['id'] ?? 0);
      if ($id>0) { $wpdb->delete($admins,['id'=>$id]); }
      if (!empty($wpdb->last_error)) { $_SESSION['sav_error']='DB Error: '.$wpdb->last_error; }
      wp_safe_redirect(home_url('/service-agent-management/dashboard')); exit;
  }
});

function sav_qr_img($data,$size='220x220'){ $e=rawurlencode($data); return "https://api.qrserver.com/v1/create-qr-code/?data={$e}&size={$size}"; }



// ایمپورت از TSV (Tab-Separated Values) - سازگار با فایل فعلی شما
function sav_import_agents_from_csv($file_path) {
    global $wpdb;
    $table = $wpdb->prefix . 'service_agents';

    if (!file_exists($file_path)) {
        return ['success' => false, 'error' => 'فایل پیدا نشد'];
    }

    $content = file_get_contents($file_path);
    if ($content === false) {
        return ['success' => false, 'error' => 'نمی‌توان فایل را خواند'];
    }

    // حذف BOM
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

    // تبدیل Tab واقعی به \t و سپس split
    $lines = explode("\n", $content);
    $imported = 0;

    $upload_dir = wp_upload_dir();
    $custom_dir = $upload_dir['basedir'] . '/sepehr-service-agents-pics';

    foreach ($lines as $i => $line) {
        if ($i == 0) continue; // رد کردن هدر
        $line = trim($line);
        if (empty($line)) continue;

        // جدا کردن با Tab واقعی (نه \t در متن)
        $data = preg_split('/\t/', $line);

        // اگر Tab واقعی نباشه، با کاما امتحان کن
        if (count($data) === 1 && strpos($line, ',') !== false) {
            $data = str_getcsv($line);
        }

        if (count($data) === 1 && strpos($line, "\x1b") !== false) {
            $data = explode("\x1b", $line);
        }

        // حداقل 10 ستون باید باشه
        if (count($data) < 10) continue;

        $province       = trim($data[0] ?? '');
        $area           = trim($data[1] ?? '');
        $agency_name    = trim($data[2] ?? '');
        $agency_code    = trim($data[3] ?? '');
        $agency_phone   = trim($data[4] ?? '');
        $person_name    = trim($data[5] ?? '');
        $national_id    = trim($data[6] ?? '');
        $person_phone   = trim($data[7] ?? '');
        $valid_until    = trim($data[8] ?? '');
        $activity_desc  = trim($data[9] ?? '');

        if (empty($person_name) || empty($national_id)) continue;

        $valid_until_miladi = null;
        if ($valid_until && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_until)) {
            $valid_until_miladi = $valid_until;
        }

        // تولید photo_url
        $photo_filename = $national_id . '.jpg';
        $photo_path = $custom_dir . '/' . $photo_filename;
        $photo_url = file_exists($photo_path)
            ? $upload_dir['baseurl'] . '/sepehr-service-agents-pics/' . $photo_filename
            : '';

        $record = [
            'person_name'    => sanitize_text_field($person_name),
            'province'       => sanitize_text_field($province),
            'area'           => sanitize_text_field($area),
            'agency_name'    => sanitize_text_field($agency_name),
            'agency_code'    => sanitize_text_field($agency_code),
            'agency_phone'   => sanitize_text_field($agency_phone),
            'national_id'    => sanitize_text_field($national_id),
            'person_phone'   => sanitize_text_field($person_phone),
            'valid_until'    => $valid_until_miladi,
            'activity_desc'  => sanitize_text_field($activity_desc),
            'status'         => 'active',
            'token'          => wp_generate_uuid4(),
            'photo_url'      => $photo_url,
        ];

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE national_id = %s", $national_id));
        if ($exists) {
            $wpdb->update($table, $record, ['id' => $exists]);
        } else {
            $wpdb->insert($table, $record);
        }

        $imported++;
    }

    return ['success' => true, 'count' => $imported];
}
// تابع تبدیل شمسی به میلادی (ساده)
function j2g($jy, $jm, $jd) {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4) + $jd +
            ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * floor($days / 146097); $days %= 146097;
    if ($days > 36524) { $gy += 100 * floor(--$days / 36524); $days %= 36524; if ($days >= 365) $days++; }
    $gy += 4 * floor($days / 1461); $days %= 1461;
    if ($days > 365) { $gy += floor(($days - 1) / 365); $days = ($days - 1) % 365; }
    $gd = $days + 1;
    $sal = ((($gy % 4 == 0) && ($gy % 100 != 0)) || ($gy % 400 == 0)) ?
        [0,31,29,31,30,31,30,31,31,30,31,30,31] : [0,31,28,31,30,31,30,31,31,30,31,30,31];
    $gm = 1; for (; $gm <= 12 && $gd > $sal[$gm]; $gm++) $gd -= $sal[$gm];
    return ['y' => $gy, 'm' => $gm, 'd' => $gd];
}

// هندل آپلود CSV
add_action('init', function() {
   if (isset($_POST['sav_import_csv']) && SAV_Auth::is_logged_in() && SAV_Auth::user_can('manage_agents')) {
    check_admin_referer('sav_import_csv', 'sav_import_nonce');
    
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/sepehr-service-agents-pics';
        $target_file = $target_dir . '/agents-import.csv';
        
        error_log("Trying to upload CSV: tmp_name = " . $_FILES['csv_file']['tmp_name']);
        
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $target_file)) {
            error_log("CSV uploaded to: " . $target_file);
            $result = sav_import_agents_from_csv($target_file);
            if ($result['success']) {
                $_SESSION['sav_message'] = "موفقیت: {$result['count']} نماینده ایمپورت شد.";
            } else {
                error_log("Import error: " . $result['error']);
                $_SESSION['sav_error'] = "خطا: " . $result['error'];
            }
        } else {
            error_log("Upload failed. Error code: " . $_FILES['csv_file']['error']);
            $_SESSION['sav_error'] = "خطا در آپلود فایل. کد خطا: " . $_FILES['csv_file']['error'];
        }
    } else {
        error_log("No CSV file uploaded");
        $_SESSION['sav_error'] = "هیچ فایلی آپلود نشد";
    }
    wp_safe_redirect(home_url('/service-agent-management/dashboard'));
    exit;
}
});
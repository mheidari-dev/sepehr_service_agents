<?php defined('ABSPATH') || exit;
if (!is_user_logged_in() || !current_user_can('manage_service_agents')) { wp_safe_redirect(home_url('/service-agent-management')); exit; }
global $wpdb;

$agents = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}service_agents` ORDER BY id DESC");
$users = get_users([
  'capability' => 'manage_service_agents',
  'orderby'    => 'ID',
  'order'      => 'DESC',
]);
$u = wp_get_current_user();
$sav_notice = sav_current_notice('message');
$sav_error  = sav_current_notice('error');

/** ویرایش نماینده */
$edit = null;
if (isset($_GET['edit'])) {
  $edit_id = intval($_GET['edit']);
  $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}service_agents` WHERE id=%d", $edit_id));
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>پنل مدیریت نمایندگان</title>

  <!-- CSS اصلی -->
  <link rel="stylesheet" href="<?php echo esc_url(SAV_URL . 'assets/css/dashboard.css'); ?>">

  <!-- Bootstrap 5 + DataTables + Responsive -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">

  <!-- Persian Datepicker -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
</head>
<body class="bg-light">

<div class="container-fluid py-4 sav-container">

  <!-- هدر -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">پنل مدیریت نمایندگان</h4>
    <div>
      سلام، <strong><?php echo esc_html($u->display_name ?: $u->user_login); ?></strong>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/service-agent-management'))); ?>" class="btn btn-outline-danger btn-sm mx-2">خروج</a>
    </div>
  </div>

  <!-- تب‌ها -->
  <ul class="nav nav-tabs mb-4" id="savTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-target="#tab-agents">نمایندگان</button>
    </li>
    <?php if (current_user_can('manage_service_agent_users')): ?>
    <li class="nav-item">
      <button class="nav-link" data-bs-target="#tab-users">کاربران</button>
    </li>
    <?php endif; ?>
  </ul>

    <!-- تب نمایندگان -->
    <?php if ($sav_notice): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php echo esc_html($sav_notice); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($sav_error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?php echo esc_html($sav_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>


  <div class="tab-pane fade show active" id="tab-agents">
<div class="card mb-4">
  <div class="card-header bg-info text-white">ایمپورت از فایل CSV</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?php wp_nonce_field('sav_import_csv', 'sav_import_nonce'); ?>
      <div class="row g-3 align-items-center">
        <div class="col-auto">
          <input type="file" name="csv_file" accept=".csv" required class="form-control form-control-sm">
        </div>
        <div class="col-auto">
          <button type="submit" name="sav_import_csv" class="btn btn-success btn-sm">ایمپورت</button>
        </div>
        <div class="col">
          <small class="text-muted">
            فایل CSV با ستون‌های: نام, استان, محدوده, نام نمایندگی, کد نمایندگی, تلفن دفتر, کد ملی, تلفن پرسنل, اعتبار (شمسی), شرح
          </small>
        </div>
      </div>
    </form>
  </div>
</div>
    <!-- فرم افزودن/ویرایش -->
    <details class="card mb-4" <?php echo $edit ? 'open' : ''; ?>>
      <summary class="card-header bg-primary text-white">
        <?php echo $edit ? 'ویرایش نماینده' : 'افزودن نماینده جدید'; ?>
      </summary>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3" id="sav-agent-form">
          <?php wp_nonce_field('sav_form_nonce', 'sav_form_nonce'); ?>
          <input type="hidden" name="sav_action" value="agent_save">
          <input type="hidden" name="id" value="<?php echo $edit ? intval($edit->id) : ''; ?>">

          <div class="col-md-6"><label class="form-label">نام پرسنل *</label>
            <input name="person_name" class="form-control" required value="<?php echo $edit ? esc_attr($edit->person_name) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">استان</label>
            <input name="province" class="form-control" value="<?php echo $edit ? esc_attr($edit->province) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">محدوده فعالیت</label>
            <input name="area" class="form-control" value="<?php echo $edit ? esc_attr($edit->area) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">نام نمایندگی</label>
            <input name="agency_name" class="form-control" value="<?php echo $edit ? esc_attr($edit->agency_name) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">کد نمایندگی</label>
            <input name="agency_code" class="form-control" value="<?php echo $edit ? esc_attr($edit->agency_code) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">تلفن دفتر</label>
            <input name="agency_phone" class="form-control" value="<?php echo $edit ? esc_attr($edit->agency_phone) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">کد ملی</label>
            <input name="national_id" class="form-control" maxlength="10" value="<?php echo $edit ? esc_attr($edit->national_id) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">تلفن پرسنل</label>
            <input name="person_phone" class="form-control" value="<?php echo $edit ? esc_attr($edit->person_phone) : ''; ?>">
          </div>

          <!-- تاریخ شمسی با دیت‌پیکر -->
          <div class="col-md-6">
            <label class="form-label">اعتبار تا تاریخ (شمسی)</label>
            <input type="text" id="valid_until" name="valid_until" class="form-control persian-datepicker"
                   placeholder="مثال: ۱۴۰۵/۰۸/۰۱"
                   value="<?php echo ($edit && $edit->valid_until) ? esc_attr($edit->valid_until) : ''; ?>">
            <small class="text-muted">تقویم شمسی — تاریخ میلادی ذخیره می‌شود</small>
          </div>

          <div class="col-md-6"><label class="form-label">شرح فعالیت</label>
            <input name="activity_desc" class="form-control" value="<?php echo $edit ? esc_attr($edit->activity_desc) : ''; ?>">
          </div>
          <div class="col-md-6"><label class="form-label">وضعیت</label>
            <select name="status" class="form-select">
              <option value="active" <?php selected($edit->status ?? '', 'active'); ?>>فعال</option>
              <option value="inactive" <?php selected($edit->status ?? '', 'inactive'); ?>>غیرفعال</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">عکس (اختیاری)</label>
            <?php if ($edit && !empty($edit->photo_url)): ?>
              <div class="mb-2"><img src="<?php echo esc_url($edit->photo_url); ?>" class="sav-avatar rounded" style="width:60px;height:60px;"></div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*" class="form-control">
          </div>

          <div class="col-12">
            <button class="btn btn-success" type="submit"><?php echo $edit ? 'ذخیره تغییرات' : 'ذخیره نماینده'; ?></button>
            <?php if ($edit): ?>
              <a href="<?php echo home_url('/service-agent-management/dashboard'); ?>" class="btn btn-secondary">انصراف</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </details>

    <!-- جدول نمایندگان با DataTables -->
    <div class="card">
      <div class="card-header bg-dark text-white">لیست نمایندگان</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0" id="agents-table">
            <thead class="table-dark">
              <tr>
                <th>#</th>
                <th>عکس</th>
                <th>نام</th>
                <th>نمایندگی</th>
                <th>محدوده</th>
                <th>استان</th>
                <th>کد ملی</th>
                <th>کد نمایندگی</th>
                <th>وضعیت</th>
                <th>اعتبار</th>
                <th>عملیات</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agents as $a):
                $url = home_url('/service-agent/' . $a->token);
                $qr  = sav_qr_img($url, '120x120');
              ?>
                <tr>
                  <td><?php echo intval($a->id); ?></td>
                  <td><?php if ($a->photo_url): ?><img src="<?php echo esc_url($a->photo_url); ?>" class="sav-avatar rounded" style="width:40px;height:40px;"><?php endif; ?></td>
                  <td><?php echo esc_html($a->person_name); ?></td>
                  <td><?php echo esc_html($a->agency_name); ?></td>
                  <td><?php echo esc_html($a->area); ?></td>
                  <td><?php echo esc_html($a->province); ?></td>
                  <td><?php echo esc_html($a->national_id); ?></td>
                  <td><?php echo esc_html($a->agency_code); ?></td>
                  <td>
                    <span class="badge <?php echo $a->status === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                      <?php echo $a->status === 'active' ? 'فعال' : 'غیرفعال'; ?>
                    </span>
                  </td>
                  <td><?php echo $a->valid_until ? esc_html(jdate('Y/m/d', strtotime($a->valid_until))) : '—'; ?></td>
                  
                  <td>
                    <a href="<?php echo esc_url(add_query_arg(['sav_page'=>'dashboard','edit'=>intval($a->id)], home_url('/service-agent-management/dashboard'))); ?>" class="btn btn-sm btn-primary">ویرایش</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('حذف شود؟')">
                      <?php wp_nonce_field('sav_form_nonce', 'sav_form_nonce'); ?>
                      <input type="hidden" name="sav_action" value="agent_delete">
                      <input type="hidden" name="id" value="<?php echo intval($a->id); ?>">
                      <button class="btn btn-sm btn-danger">حذف</button>
                    </form>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="btn btn-sm btn-info">نمایش</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- تب کاربران -->
  <?php if (current_user_can('manage_service_agent_users')): ?>
  <div class="tab-pane fade" id="tab-users">
    <div class="card">
      <div class="card-header">مدیریت کاربران پنل</div>
      <div class="card-body">
        <form method="post" class="row g-3 mb-4">
          <?php wp_nonce_field('sav_form_nonce', 'sav_form_nonce'); ?>
          <input type="hidden" name="sav_action" value="user_save">
          <div class="col-md-3"><input name="username" class="form-control" placeholder="نام کاربری *" required></div>
          <div class="col-md-3"><input name="full_name" class="form-control" placeholder="نام کامل"></div>
          <div class="col-md-2">
            <select name="role" class="form-select">
              <option value="service_agent_manager">مدیر نمایندگان</option>
              <option value="editor">ویرایشگر</option>
              <option value="administrator">مدیرکل</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select"><option value="active">فعال</option><option value="inactive">غیرفعال</option></select>
          </div>
          <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="رمز عبور"></div>
          <div class="col-md-2"><button class="btn btn-success w-100">ذخیره</button></div>
        </form>

        <table class="table table-bordered">
          <thead><tr><th>#</th><th>نام کاربری</th><th>نام کامل</th><th>نقش</th><th>وضعیت</th><th>آخرین ورود</th><th>حذف</th></tr></thead>
          <tbody>
            <?php foreach ($users as $x): ?>
            <tr>
              <td><?php echo intval($x->ID); ?></td>
              <td><?php echo esc_html($x->user_login); ?></td>
              <td><?php echo esc_html($x->display_name ?: $x->user_login); ?></td>
              <td><?php echo esc_html(implode(', ', $x->roles)); ?></td>
              <?php $status = get_user_meta($x->ID, 'sav_user_status', true) ?: 'active'; ?>
              <td><?php echo esc_html($status); ?></td>
              <?php $last_login = get_user_meta($x->ID, 'sav_last_login', true); ?>
              <td>
                <?php echo $last_login ? esc_html(jdate('Y/m/d', strtotime(substr($last_login, 0, 10)))) : '—'; ?>
              </td>
              <td>
                <form method="post" onsubmit="return confirm('حذف شود؟')">
                  <?php wp_nonce_field('sav_form_nonce', 'sav_form_nonce'); ?>
                  <input type="hidden" name="sav_action" value="user_delete">
                  <input type="hidden" name="id" value="<?php echo intval($x->ID); ?>">
                  <button class="btn btn-sm btn-danger">حذف</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>

<script>
// تب‌ها
document.querySelectorAll('#savTabs .nav-link').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('#savTabs .nav-link').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
    tab.classList.add('active');
    document.querySelector(tab.dataset.bsTarget).classList.add('show', 'active');
  });
});

// دیت‌پیکر شمسی + تبدیل به میلادی
jQuery(function($) {
  const $input = $('#valid_until');

  if ($input.val() && /^\d{4}-\d{2}-\d{2}$/.test($input.val())) {
    const [y, m, d] = $input.val().split('-').map(Number);
    $input.val(new persianDate([y, m, d]).toLocale('fa').format('YYYY/MM/DD'));
  }

  $input.persianDatepicker({
    format: 'YYYY/MM/DD',
    altField: '#valid_until',
    altFormat: 'YYYY-MM-DD',
    initialValue: false,
    calendar: { persian: { locale: 'fa' } },
    onSelect: function(unix) {
      const pd = new persianDate(unix);
      const j2g = (jy, jm, jd) => {
        jy += 1595; let days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        let gy = 400 * Math.floor(days / 146097); days %= 146097;
        if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
        gy += 4 * Math.floor(days / 1461); days %= 1461;
        if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        let gd = days + 1;
        const sal = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? [0,31,29,31,30,31,30,31,31,30,31,30,31] : [0,31,28,31,30,31,30,31,31,30,31,30,31];
        let gm = 1; for (; gm <= 12 && gd > sal[gm]; gm++) gd -= sal[gm];
        return { y: gy, m: gm, d: gd };
      };
      const g = j2g(pd.year(), pd.month(), pd.date());
      $('#valid_until').val(`${g.y}-${String(g.m).padStart(2,'0')}-${String(g.d).padStart(2,'0')}`);
    }
  });

  $input.on('change', function() {
    const val = $(this).val().trim();
    const match = val.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
    if (match && parseInt(match[1]) >= 1300) {
      const j2g = (jy, jm, jd) => { /* همان تابع بالا */ };
      const g = j2g(parseInt(match[1]), parseInt(match[2]), parseInt(match[3]));
      $(this).val(`${g.y}-${String(g.m).padStart(2,'0')}-${String(g.d).padStart(2,'0')}`);
    }
  });
});

// DataTables
jQuery(function($) {
  $('#agents-table').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/fa.json' },
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    responsive: true,
    columnDefs: [
      { targets: [1, 10], orderable: false } // عکس، QR، عملیات
    ],
    order: [[0, 'desc']]
  });
});
</script>

</body>
</html>
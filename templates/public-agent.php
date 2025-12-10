<?php
defined('ABSPATH') || exit;

global $wpdb;

$token = get_query_var('agent_token');

// گرفتن نماینده
$agent = null;
if (!empty($token)) {
    $agent = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}service_agents` WHERE `token`=%s",
            $token
        )
    );
}

// محاسبه اعتبار
$valid  = false;
$reason = '';
if ($agent) {
    $status_ok = ($agent->status === 'active');
    $now_ts = current_time('timestamp');
    $date_ok = true;
    if (!empty($agent->valid_until)) {
        $date_ok = ( strtotime($agent->valid_until . ' 23:59:59') >= $now_ts );
    }
    $valid = ($status_ok && $date_ok);
    if (!$status_ok) {
        $reason = 'وضعیت این نماینده غیرفعال است.';
    } elseif (!$date_ok) {
        $reason = 'اعتبار این نماینده به پایان رسیده است.';
    }
} else {
    $reason = 'نماینده‌ای با این شناسه پیدا نشد.';
}

// QR
$url = home_url('/service-agent/' . sanitize_text_field($token));
$qr  = sav_qr_img($url, '240x240');

// تاریخ نمایش
$valid_label = ($agent && $agent->valid_until) ? esc_html(jdate('Y/m/d', strtotime($agent->valid_until))) : '—';

get_header();
?>
<div class="agent-wrapper" style="max-width:1100px;margin:30px auto;padding:0 12px;">
 <style>
  .agent-shell{
    background:#f4f5f8;
    border-radius:20px;
    padding:16px;
    box-shadow:0 4px 20px rgba(0,0,0,.03);
  }
  .agent-card{
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    border:1px solid #e4e6ef;
  }
  .agent-topbar{
    background:#eef0f4;
    padding:14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
  }
  .agent-topbar h2{
    margin:0;
    font-size:19px;
    font-weight:600;
    color:#1f2937;
  }
  .agent-topbar .logo img{
    width:180px;
    max-width:180px;
  }
  .agent-body{
    padding:18px;
    display:grid;
    grid-template-columns: 0.95fr 1.35fr 0.7fr;
    gap:16px;
  }
  .agent-box{
    background:#fff;
    border:1px solid #edf0f5;
    border-radius:14px;
    padding:14px 14px 10px;
    height:100%;
  }
  .agent-box-title{
    font-weight:700;
    margin-bottom:10px;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .agent-meta{display:flex; flex-direction:column; gap:8px;}
  .agent-meta-row{
    display:flex;
    justify-content:space-between;
    gap:6px;
    font-size:14px;
    color:#0f172a;
  }
  .agent-meta-row b{color:#6b7280; font-weight:600;}

  .agent-middle{
    display:grid;
    grid-template-columns: 160px 1fr;
    gap:14px;
    align-items:flex-start;
  }
  .agent-photo{
    width:160px;
    max-width:160px;
    height:200px;
    border-radius:16px;
    border:1px solid #e5e7ef;
    object-fit:cover;
    background:#f7f7f7;
  }
  .agent-status-badge{
    display:inline-block;
    margin-top:10px;
    padding:8px 22px;
    border-radius:999px;
    font-weight:700;
    font-size:15px;
    border:1px solid #0f7a55;
    background:#eefaf3;
    color:#0f7a55;
  }
  .agent-status-badge.danger{
    background:#feeeee;
    color:#b01d1d;
    border-color:#b01d1d;
  }
  .agent-fields{display:grid; gap:6px;}
  .pill{
    direction:ltr;
    display:inline-block;
    background:#ff5d5d;
    color:#fff;
    padding:4px 12px;
    border-radius:999px;
    font-weight:600;
    font-size:13px;
  }
  .badge-date{
    display:inline-block;
    padding:6px 24px;
    border:1px solid #0f7a55;
    border-radius:999px;
    background:#fff;
    color:#0f7a55;
    font-weight:700;
  }
  .badge-date.danger{
    border-color:#b01d1d;
    color:#b01d1d;
    background:#fff5f5;
  }
  .agent-qr-box{
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f9fafb;
    border-radius:12px;
    border:1px solid #eef0f4;
    padding:16px;
  }
  .agent-bottom{
    margin-top:16px;
    display:grid;
    gap:12px;
  }
  .agent-contact{
    background:#fff;
    border:1px solid #e4e6ef;
    border-radius:14px;
    padding:12px 16px;
    display:flex;
    gap:12px;
    align-items:center;
    justify-content:flex-start;
  }
  .agent-contact-label{min-width:120px; color:#6b7280;}
  .agent-contact .pill{background:#e60012; color:#fff; font-weight:700;}
  .agent-error{
    background:#fff0f0;
    border:1px solid #e58a8a;
    border-radius:12px;
    padding:10px 15px;
    color:#a40000;
    font-weight:600;
  }

  /* تبلت */
  @media (max-width: 992px){
    .agent-body{
      grid-template-columns:1fr 1fr;
    }
  }

  /* موبایل */
  @media (max-width: 700px){
    /* هدر: لوگو بالا، متن زیرش و وسط */
    .agent-topbar{
      flex-direction:column;
      justify-content:center;
      text-align:center;
    }
    .agent-topbar .logo{
      order:1;
    }
    .agent-topbar h2{
      order:2;
      font-size: 14px;
      text-align:center;
    }

    .agent-body{
      grid-template-columns:1fr;
    }
    /* ستون مشخصات: عکس و دکمه وسط */
    .agent-middle{
      grid-template-columns:1fr;
      text-align:center;
    }
    .agent-photo{
      margin:0 auto;
    }
    .agent-status-badge{
      display:inline-block;
      margin:10px auto 0;
    }
    /* فیلدها زیر عکس */
    .agent-fields{
      text-align:right;
      margin-top:10px;
    }
    /* QR هم وسط */
    .agent-qr-box{
      justify-content:center;
    }
  }
</style>


  <div class="agent-shell">
    <div class="agent-card">
      <div class="agent-topbar">
        <h2>احراز هویت نمایندگان خدمات پس از فروش</h2>
        <div class="logo">
          <img src="<?php echo esc_url( SAV_URL . 'assets/img/logo_black.png' ); ?>" alt="Sepehr Electric">
        </div>
      </div>

      <div class="agent-body">
        <?php if (!$agent): ?>
          <div class="agent-error">
            <?php echo esc_html($reason); ?>
          </div>
        <?php else: ?>
          <!-- ستون 1: اطلاعات نمایندگی -->
          <div class="agent-box">
            <div class="agent-box-title">اطلاعات نمایندگی</div>
            <div class="agent-meta">
              <div class="agent-meta-row">
                <b>کد نمایندگی:</b>
                <span><?php echo esc_html($agent->agency_code ?: '—'); ?></span>
              </div>
              <div class="agent-meta-row">
                <b>نام نمایندگی:</b>
                <span><?php echo esc_html($agent->agency_name ?: '—'); ?></span>
              </div>
              <div class="agent-meta-row">
                <b>تلفن دفتر:</b>
                <span class="pill"><?php echo esc_html($agent->agency_phone ?: '—'); ?></span>
              </div>
            </div>
          </div>

          <!-- ستون 2: عکس + وضعیت + اطلاعات شخص -->
          <div class="agent-box">
            <div class="agent-box-title">مشخصات نماینده</div>
            <div class="agent-middle">
              <div>
                <?php if (!empty($agent->photo_url)): ?>
                  <img class="agent-photo" src="<?php echo esc_url($agent->photo_url); ?>" alt="">
                <?php else: ?>
                  <div class="agent-photo" style="display:flex;align-items:center;justify-content:center;color:#999;">PHOTO</div>
                <?php endif; ?>

                <div>
                  <?php if ($valid): ?>
                    <span class="agent-status-badge">تأیید شده</span>
                  <?php else: ?>
                    <span class="agent-status-badge danger">نامعتبر</span>
                  <?php endif; ?>
                </div>

                <?php if (!$valid && $reason): ?>
                  <div class="agent-error" style="margin-top:10px;"><?php echo esc_html($reason); ?></div>
                <?php endif; ?>
              </div>
              <div class="agent-fields">
                <div class="agent-meta-row"><b>نام:</b> <?php echo esc_html($agent->person_name ?: '—'); ?></div>
                <div class="agent-meta-row"><b>کدملی:</b> <?php echo esc_html($agent->national_id ?: '—'); ?></div>
                <div class="agent-meta-row"><b>موبایل:</b> <span class="pill"><?php echo esc_html($agent->person_phone ?: '—'); ?></span></div>
                <div class="agent-meta-row"><b>شرح فعالیت:</b> <?php echo esc_html($agent->activity_desc ?: '—'); ?></div>
                <div class="agent-meta-row"><b>محدوده فعالیت:</b> <?php echo esc_html($agent->area ?: '—'); ?></div>
                <div class="agent-meta-row"><b>اعتبار تا:</b>
                  <span class="badge-date <?php echo $valid ? '' : 'danger'; ?>">
                    <?php echo esc_html($valid_label); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ستون 3: QR -->
          <div class="agent-box">
            <div class="agent-box-title">QR نماینده</div>
            <div class="agent-qr-box">
              <img src="<?php echo esc_url($qr); ?>" alt="QR" style="width:180px;max-width:100%;">
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- کارت پایین: اطلاعات تماس -->
    <div class="agent-bottom">
      <div class="agent-contact">
        <div class="agent-contact-label">دفتر مرکزی سپهر الکتریک:</div>
        <div><span class="pill">021 - 82115</span></div>
      </div>
      <div class="agent-contact">
        <div class="agent-contact-label">خدمات پس از فروش:</div>
        <div><span class="pill">021 - 82117000</span></div>
      </div>
    </div>
  </div>
</div>
<?php
get_footer();

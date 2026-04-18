<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../controllers/PermohonanEmelController.php';

$controller = new PermohonanEmelController();
$user = $controller->user;

function h_modal($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<style>
.nav-tabs { border-bottom: 2px solid #e5e7eb; }
.nav-tabs .nav-link { border: none; color: #6b7280; font-weight: 500; padding: .7rem 1.2rem; }
.nav-tabs .nav-link.active { color: #2563eb; border-bottom: 3px solid #2563eb; background: transparent; }
.table td { vertical-align: middle; padding: .7rem .8rem; }
.table input.form-control { border-radius: 6px; }
.form-check { margin-right: 10px; }
#emailTabsContent .tab-pane { min-height: 540px; max-height: 540px; overflow-y: auto; padding-right: 5px; }
.card-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
.card { border-radius: 10px; }
</style>
<form id="formPermohonanEmel">
  <input type="hidden" name="draft_id" id="draft_id">

  <ul class="nav nav-tabs mb-3" id="emailTabs">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPemohon" type="button"><?= __('email_tab_pemohon') ?></button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEmail" type="button"><?= __('email_tab_email') ?></button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConfirm" type="button"><?= __('email_tab_confirm') ?></button>
    </li>
  </ul>

  <div class="tab-content" id="emailTabsContent">
    <div class="tab-pane fade show active" id="tabPemohon">
      <div class="card border shadow-sm">
        <div class="card-header fw-semibold">
          <i class="ri-user-line me-1"></i>
          <?= __('email_tab_pemohon') ?>
        </div>
        <div class="card-body">
          <table class="table table-bordered align-middle mb-0">
            <tr>
              <td width="30%"><?= __('email_field_full_name') ?> :</td>
              <td colspan="3"><input class="form-control" value="<?= h_modal($user['f_nama'] ?? '') ?>" readonly></td>
            </tr>
            <tr>
              <td><?= __('email_field_position') ?> :</td>
              <td colspan="3"><input class="form-control" value="<?= h_modal($user['f_jawatan'] ?? '') ?>" readonly></td>
            </tr>
            <tr>
              <td><?= __('email_field_taraf_jawatan') ?> :</td>
              <td colspan="3">
                <div class="d-flex flex-wrap gap-3">
                  <label class="form-check"><input class="form-check-input" type="radio" name="taraf_jawatan" value="Tetap" required> <?= __('email_taraf_tetap') ?></label>
                  <label class="form-check"><input class="form-check-input" type="radio" name="taraf_jawatan" value="Pinjaman"> <?= __('email_taraf_pinjaman') ?></label>
                  <label class="form-check"><input class="form-check-input" type="radio" name="taraf_jawatan" value="Sambilan"> <?= __('email_taraf_sambilan') ?></label>
                  <label class="form-check"><input class="form-check-input" type="radio" name="taraf_jawatan" value="Kontrak"> <?= __('email_taraf_kontrak') ?></label>
                  <label class="form-check"><input class="form-check-input" type="radio" name="taraf_jawatan" value="Sementara"> <?= __('email_taraf_sementara') ?></label>
                </div>
              </td>
            </tr>
            <tr>
              <td><?= __('email_field_department') ?> :</td>
              <td colspan="3"><input class="form-control" value="<?= h_modal($user['f_namajabatan'] ?? '') ?>" readonly></td>
            </tr>
            <tr>
              <td><?= __('email_field_phone_office') ?> :</td>
              <td>
                <input type="tel" name="no_tel_pejabat" class="form-control" placeholder="<?= __('email_phone_office_placeholder') ?>" pattern="[0-9\-]{9,12}" required>
              </td>
              <td><?= __('email_field_phone_mobile') ?> :</td>
              <td>
                <input type="text" name="no_tel_bimbit" class="form-control" placeholder="<?= __('email_phone_mobile_placeholder') ?>" pattern="[0-9\-]{9,12}" required>
              </td>
            </tr>
            <tr>
              <td><?= __('email_field_alternative_email') ?> :</td>
              <td><input type="email" name="alternative_email" class="form-control" placeholder="<?= h_modal(__('email_placeholder_alternative_email')) ?>" required></td>
              <td><?= __('email_field_staff_id') ?> :</td>
              <td><input class="form-control" value="<?= h_modal($user['f_stafID'] ?? '') ?>" readonly></td>
            </tr>
          </table>
        </div>
      </div>
      <div class="text-end mt-3">
        <button type="button" class="btn btn-primary" id="btnNext1"><?= __('email_btn_next') ?> <i class="ri-arrow-right-line ms-1"></i></button>
      </div>
    </div>

    <div class="tab-pane fade" id="tabEmail">
      <div class="card border shadow-sm">
        <div class="card-header fw-semibold">
          <i class="ri-mail-line me-1"></i>
          <?= __('email_tab_email') ?>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?= __('email_field_requested_email') ?></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-at-line"></i></span>
                <input type="text" name="email_dipohon" class="form-control" placeholder="<?= h_modal(__('email_requested_email_placeholder')) ?>" required>
              </div>
              <small class="text-muted"><?= __('email_format_note') ?></small>
            </div>
            <div class="col-md-12">
              <label class="form-label"><?= __('email_field_purpose') ?></label>
              <textarea name="tujuan" class="form-control" rows="4" placeholder="<?= __('email_purpose_placeholder') ?>" required></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-secondary" id="btnPrev2"><i class="ri-arrow-left-line me-1"></i> <?= __('email_btn_back') ?></button>
        <button type="button" class="btn btn-primary" id="btnNext2"><?= __('email_btn_next') ?> <i class="ri-arrow-right-line ms-1"></i></button>
      </div>
    </div>

    <div class="tab-pane fade" id="tabConfirm">
      <div class="card border shadow-sm">
        <div class="card-header fw-semibold">
          <i class="ri-shield-check-line text-success me-1"></i>
          <?= __('email_declaration_title') ?>
        </div>
        <div class="card-body">
          <div class="alert border">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="akuanPemohon" name="akuan" required>
              <label class="form-check-label" for="akuanPemohon"><?= __('email_declaration_text') ?></label>
            </div>
          </div>
          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label"><?= __('email_field_applicant_name') ?></label>
              <input class="form-control" value="<?= h_modal($user['f_nama'] ?? '') ?>" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= __('email_field_application_date') ?></label>
              <input class="form-control" value="<?= date('d/m/Y') ?>" readonly>
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-secondary" id="btnPrev3"><i class="ri-arrow-left-line me-1"></i> <?= __('email_btn_back') ?></button>
        <button type="submit" class="btn btn-success"><i class="ri-send-plane-fill me-1"></i> <?= __('email_btn_confirm_submit') ?></button>
      </div>
    </div>
  </div>
</form>
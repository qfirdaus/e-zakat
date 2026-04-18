            <!-- Tab 2: Pangkalan Data -->
            <div class="tab-pane fade <?= ($_GET['tab'] ?? '') === 'db' ? 'show active' : '' ?>" id="db-tab" role="tabpanel">
              <form method="post" id="form-db-aktif" autocomplete="off" data-no-loader="1" novalidate onsubmit="return window.__tetapanAjaxSubmit(event, this, 'btn-simpan-db');">
                <input type="hidden" name="submit_db" value="1">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="card general-settings-card">
                  <div class="card-header general-settings-header-info">
                    <div class="d-flex align-items-center">
                      <div class="general-settings-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="ri-database-2-line fs-5"></i>
                      </div>
                      <div>
                        <h5 class="mb-1 fw-semibold text-info"><?= __('config_tab_db') ?? 'Database' ?></h5>
                        <small class="text-muted"><?= __('config_tab_db_container_sub') ?? 'Manage Sybase runtime selection and view the main MySQL connection details.' ?></small>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                <ul class="nav nav-pills general-subtabs" id="dbSubtabNav" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="db-subtab-sybase-tab" data-bs-toggle="tab" data-bs-target="#db-subtab-sybase" type="button" role="tab" aria-controls="db-subtab-sybase" aria-selected="true">
                      <i class="ri-database-2-line me-1"></i> <?= __('config_tab_db_subtab_sybase') ?? 'Sybase' ?>
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="db-subtab-mysql-tab" data-bs-toggle="tab" data-bs-target="#db-subtab-mysql" type="button" role="tab" aria-controls="db-subtab-mysql" aria-selected="false">
                      <i class="ri-server-line me-1"></i> <?= __('config_tab_db_subtab_mysql') ?? 'MySQL' ?>
                    </button>
                  </li>
                </ul>
                <div class="tab-content">
                  <div class="tab-pane fade show active general-subtab-pane" id="db-subtab-sybase" role="tabpanel" aria-labelledby="db-subtab-sybase-tab">
                <div class="general-settings-note mb-3">
                  <i class="ri-database-2-line me-2"></i><?= __('config_tab_db_sybase_subtab_note') ?? 'Urus pemilihan runtime Sybase, mode operasi, dan ringkasan sambungan aktif dalam satu paparan.' ?>
                </div>
                <div class="row gx-3 gy-0 align-items-start">
                  <div class="col-lg-7">
                    <div class="row gy-0">
                      <div class="col-12">
                        <div class="card db-settings-card">
                      <div class="card-header db-settings-header-warning">
                        <div class="d-flex align-items-center">
                          <div class="db-settings-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="ri-database-2-line fs-5"></i>
                          </div>
                          <div>
                            <h5 class="mb-1 fw-semibold text-warning"><?= __('config_tab_db_header') ?? 'Sybase Environment' ?></h5>
                            <small class="text-muted"><?= __('config_tab_db_header_sub') ?? 'Choose the active environment for the staff connection' ?></small>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive db-settings-table dt-standard-shell">
                          <table class="table table-hover align-middle mb-0">
                          <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px">
                                  <i class="ri-radio-button-line text-muted"></i>
                                </th>
                                <th style="width:220px" class="fw-semibold"><?= __('config_tab_db_sybase_sambungan') ?? 'Environment' ?></th>
                                <th class="fw-semibold"><?= __('config_tab_db_sybase_keterangan') ?? 'Keterangan' ?></th>
                            </tr>
                          </thead>
                          <tbody>
                              <tr class="db-option-row <?= ($dbRenderEnvironment === 'production') ? 'table-primary is-selected' : '' ?>" data-db-radio="#sybase_environment_production">
                                <td class="text-center">
                                  <div class="form-check">
                                    <input class="db-radio" type="radio" name="sybase_environment" id="sybase_environment_production"
                                      value="production" <?= ($dbRenderEnvironment === 'production') ? 'checked="checked"' : '' ?>>
                                  </div>
                              </td>
                                <td>
                                  <label class="form-check-label fw-bold cursor-pointer" for="sybase_environment_production">
                                    <?= __('config_tab_db_environment_production') ?? 'Production' ?>
                                  </label>
                                </td>
                                <td>
                                  <span class="badge bg-success-subtle text-success me-2"><i class="ri-checkbox-circle-line"></i></span>
                                  <?= __('config_tab_db_environment_production_desc') ?? 'Use production Sybase staff connection for live system operations.' ?>
                                </td>
                            </tr>
                              <tr class="db-option-row <?= ($dbRenderEnvironment === 'development') ? 'table-primary is-selected' : '' ?>" data-db-radio="#sybase_environment_development">
                              <td class="text-center">
                                  <div class="form-check">
                                    <input class="db-radio" type="radio" name="sybase_environment" id="sybase_environment_development"
                                      value="development" <?= ($dbRenderEnvironment === 'development') ? 'checked="checked"' : '' ?>>
                                  </div>
                              </td>
                                <td>
                                  <label class="form-check-label fw-bold cursor-pointer" for="sybase_environment_development">
                                    <?= __('config_tab_db_environment_development') ?? 'Development' ?>
                                  </label>
                                </td>
                                <td>
                                  <span class="badge bg-info-subtle text-info me-2"><i class="ri-flask-line"></i></span>
                                  <?= __('config_tab_db_environment_development_desc') ?? 'Use development Sybase staff connection for testing and staging work.' ?>
                                </td>
                            </tr>
                          </tbody>
                        </table>
                        </div>
                      </div>
                        </div>
                      </div>

                      <div class="col-12">
                        <div class="card db-settings-card">
                      <div class="card-header db-settings-header-success">
                        <div class="d-flex align-items-center">
                          <div class="db-settings-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="ri-server-line fs-5"></i>
                          </div>
                          <div>
                            <h5 class="mb-1 fw-semibold text-success"><?= __('config_tab_db_mode_header') ?? 'Operational Mode' ?></h5>
                            <small class="text-muted"><?= __('config_tab_db_mode_header_sub') ?? 'Choose which Sybase domains are enabled for the system' ?></small>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive db-settings-table dt-standard-shell">
                          <table class="table table-hover align-middle mb-0">
                          <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px">
                                  <i class="ri-radio-button-line text-muted"></i>
                                </th>
                                <th style="width:220px" class="fw-semibold"><?= __('config_tab_db_mode_column') ?? 'Mode' ?></th>
                                <th class="fw-semibold"><?= __('config_tab_db_mode_desc_column') ?? 'Description' ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr class="db-option-row <?= ($dbRenderOperationalMode === 'staff_only') ? 'table-primary is-selected' : '' ?>" data-db-radio="#sybase_operational_mode_staff_only">
                                <td class="text-center">
                                  <div class="form-check">
                                      <input class="db-radio" type="radio" name="sybase_operational_mode" id="sybase_operational_mode_staff_only"
                                      value="staff_only" <?= ($dbRenderOperationalMode === 'staff_only') ? 'checked="checked"' : '' ?>>
                                  </div>
                                </td>
                                <td>
                                  <label class="form-check-label fw-bold cursor-pointer" for="sybase_operational_mode_staff_only">
                                    <?= __('config_tab_db_mode_staff_only') ?? 'Staff Only' ?>
                                  </label>
                                </td>
                                <td>
                                  <span class="badge bg-secondary-subtle text-secondary me-2"><i class="ri-user-line"></i></span>
                                  <?= __('config_tab_db_mode_staff_only_desc') ?? 'Only staff domain is used. Student connection remains disabled.' ?>
                                </td>
                            </tr>
                            <tr class="db-option-row <?= ($dbRenderOperationalMode === 'staff_student') ? 'table-primary is-selected' : '' ?>" data-db-radio="#sybase_operational_mode_staff_student">
                                <td class="text-center">
                                  <div class="form-check">
                                      <input class="db-radio" type="radio" name="sybase_operational_mode" id="sybase_operational_mode_staff_student"
                                      value="staff_student" <?= ($dbRenderOperationalMode === 'staff_student') ? 'checked="checked"' : '' ?>>
                                  </div>
                                </td>
                                <td>
                                  <label class="form-check-label fw-bold cursor-pointer" for="sybase_operational_mode_staff_student">
                                    <?= __('config_tab_db_mode_staff_student') ?? 'Staff + Student' ?>
                                  </label>
                                </td>
                                <td>
                                  <span class="badge bg-primary-subtle text-primary me-2"><i class="ri-links-line"></i></span>
                                  <?= __('config_tab_db_mode_staff_student_desc') ?? 'Staff domain stays active and student domain is also enabled for future transactions.' ?>
                                </td>
                            </tr>
                          </tbody>
                        </table>
                        </div>
                      </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-5">
                    <div class="card db-settings-card">
                      <div class="card-header db-settings-header-success">
                        <div class="d-flex align-items-center">
                          <div class="db-settings-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="ri-links-line fs-5"></i>
                          </div>
                          <div>
                            <h5 class="mb-1 fw-semibold text-primary"><?= __('config_tab_db_runtime_header') ?? 'Current Runtime Summary' ?></h5>
                            <small class="text-muted"><?= __('config_tab_db_runtime_header_sub') ?? 'This summary shows how the current runtime will behave after the settings are saved.' ?></small>
                          </div>
                        </div>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive db-settings-table dt-standard-shell">
                          <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                              <tr>
                                <th style="width:220px" class="fw-semibold"><?= __('config_tab_db_runtime_field') ?? 'Component' ?></th>
                                <th class="fw-semibold"><?= __('config_tab_db_runtime_value') ?? 'Runtime Value' ?></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td><strong><?= __('config_tab_db_runtime_staff') ?? 'Sybase Staff' ?></strong></td>
                                <td>
                                  <code class="text-primary" id="db-runtime-staff"><?= htmlspecialchars($runtimeStaffBase, ENT_QUOTES, 'UTF-8') ?></code>
                                </td>
                              </tr>
                              <tr>
                                <td><strong><?= __('config_tab_db_runtime_student') ?? 'Sybase Student' ?></strong></td>
                                <td id="db-runtime-student-cell">
                                  <?php if ($dbRenderOperationalMode === 'staff_student'): ?>
                                    <code class="text-primary" id="db-runtime-student"><?= htmlspecialchars($studentRuntimeLabel, ENT_QUOTES, 'UTF-8') ?></code>
                                  <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary" id="db-runtime-student"><?= htmlspecialchars($studentRuntimeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                  <?php endif; ?>
                                </td>
                              </tr>
                              <tr>
                                <td><strong><?= __('config_tab_db_runtime_environment') ?? 'Environment' ?></strong></td>
                                <td id="db-runtime-environment"><?= $dbRenderEnvironment === 'development' ? __('config_tab_db_environment_development') ?? 'Development' : __('config_tab_db_environment_production') ?? 'Production' ?></td>
                              </tr>
                              <tr>
                                <td><strong><?= __('config_tab_db_runtime_mode') ?? 'Operational Mode' ?></strong></td>
                                <td id="db-runtime-mode"><?= $dbRenderOperationalMode === 'staff_student' ? __('config_tab_db_mode_staff_student') ?? 'Staff + Student' : __('config_tab_db_mode_staff_only') ?? 'Staff Only' ?></td>
                              </tr>
                              <tr>
                                <td><strong><?= __('config_tab_db_mysql') ?? 'MySQL' ?></strong></td>
                                <td><?= __('config_tab_db_mysql_header') ?? 'This connection is always active for the main system.' ?></td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>

                    </div>
                  </div>

                  <div class="tab-pane fade general-subtab-pane" id="db-subtab-mysql" role="tabpanel" aria-labelledby="db-subtab-mysql-tab">
                    <div class="general-settings-note mb-3">
                      <i class="ri-server-line me-2"></i><?= __('config_tab_db_mysql_subtab_note') ?? 'Paparan ini menunjukkan sambungan MySQL utama yang sentiasa aktif untuk sistem.' ?>
                    </div>
                    <div class="row g-3">
                      <div class="col-12">
                        <div class="card db-settings-card">
                          <div class="card-header db-settings-header-success">
                            <div class="d-flex align-items-center">
                              <div class="db-settings-icon bg-success bg-opacity-10 text-success me-3">
                                <i class="ri-server-line fs-5"></i>
                              </div>
                              <div>
                                <h5 class="mb-1 fw-semibold text-success"><?= __('config_tab_db_mysql') ?? 'MySQL (Always Active)' ?></h5>
                                <small class="text-muted"><?= __('config_tab_db_mysql_sub') ?? 'Always active connection' ?></small>
                              </div>
                            </div>
                          </div>
                          <div class="card-body">
                            <div class="db-settings-alert" style="border-color:rgba(16,185,129,.24);background:rgba(16,185,129,.06);">
                              <i class="ri-information-line me-2"></i><?= __('config_tab_db_mysql_header') ?? 'This connection is always active for the main system.' ?>
                            </div>
                            <div class="table-responsive db-settings-table dt-standard-shell">
                              <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                  <tr>
                                    <th style="width:220px" class="fw-semibold"><?= __('config_tab_db_mysql_sambungan') ?? 'Field' ?></th>
                                    <th class="fw-semibold"><?= __('config_tab_db_mysql_keterangan') ?? 'Information' ?></th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <td><strong><?= __('config_tab_db_mysql_driver') ?? 'Driver' ?></strong></td>
                                    <td><code class="text-primary"><?= htmlspecialchars($mysqlDriver, ENT_QUOTES, 'UTF-8') ?></code></td>
                                  </tr>
                                  <tr>
                                    <td><strong><?= __('config_tab_db_mysql_host') ?? 'Host' ?></strong></td>
                                    <td><?= htmlspecialchars($mysqlHost, ENT_QUOTES, 'UTF-8') ?></td>
                                  </tr>
                                  <tr>
                                    <td><strong><?= __('config_tab_db_mysql_database') ?? 'Database' ?></strong></td>
                                    <td><?= htmlspecialchars($mysqlDatabase, ENT_QUOTES, 'UTF-8') ?></td>
                                  </tr>
                                  <tr>
                                    <td><strong><?= __('config_tab_db_mysql_user') ?? 'User' ?></strong></td>
                                    <td><?= htmlspecialchars($mysqlUser, ENT_QUOTES, 'UTF-8') ?></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                  </div>
                </div>

                <div class="db-settings-actions d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div class="text-muted small">
                    <i class="ri-database-2-line me-1"></i> <?= __('config_tab_db_actions_note') ?? 'Pastikan pilihan Sybase diuji dan disahkan sebelum disimpan.' ?>
                  </div>
                  <button type="submit" class="btn btn-primary px-4" id="btn-simpan-db">
                    <i class="ri-save-3-line me-2"></i> <?= __('config_tab_db_simpan_tetapan_db') ?? 'Simpan Tetapan Pangkalan Data' ?>
                  </button>
                </div>
              </form>
            </div>

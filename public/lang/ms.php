<?php
return [

/* =====================================================
 * DASHBOARD (dash_)
 * ===================================================== */

'breadcrumb_home'               => 'Papan Pemuka',
'common_version'               => 'Versi',
'common_version_short'         => 'Ver.',

// New dashboard-specific keys (dashboard_ prefix)
'dashboard_title'               => 'Papan Pemuka',
'dashboard_breadcrumb'          => 'Papan Pemuka',

/* Site title (used in <title>) */
'title' => 'UPNM | Sistem Pengurusan Projek - UPNM30',



/* =====================================================
 * LOGIN & AUTH (login_, config_login_)
 * ===================================================== */

// Tajuk & navigasi
'login_title'              => 'Log Masuk',
'login.title'              => 'Log Masuk',
'login_heading'            => 'Log masuk',
'login_welcome'            => 'Selamat Datang',

'login_nav.home'           => 'Laman Utama',
'login_nav.faq'            => 'Soalan Lazim',
'login_nav.directory'      => 'Direktori UPNM',

// Maklumat & bantuan
'login_contact_title'      => 'Maklumat & Hubungi',
'login_info'               => 'Selamat datang. Sila log masuk untuk meneruskan.',
'login_contact'            => 'Sekiranya anda menghadapi sebarang masalah, sila hubungi pentadbir sistem.',

// Medan borang
'login_staffid'            => 'ID Staf',
'login_userid_label'       => 'Login ID',
'login_userid_placeholder' => 'login - no staf, no matrik @ e-mail',
'login_userid_placeholder_format'
                          => 'Log masuk - %s',
'login_userid_placeholder_staff'
                          => 'no staf',
'login_userid_placeholder_student'
                          => 'no matrik',
'login_userid_placeholder_public'
                          => 'emel',
'login_userid_placeholder_joiner'
                          => ', ',
'login_userid_placeholder_joiner_last'
                          => ' atau ',
'login_userid_placeholder_unavailable'
                          => 'Log masuk manual tidak tersedia di bawah polisi semasa',
'login_password'           => 'Katalaluan',
'login_language'           => 'Bahasa',

// Nota & tindakan
'login_note'               => 'Untuk log masuk kali pertama, gunakan ID Staf anda sebagai katalaluan.',
'login_forgot'             => 'Lupa katalaluan?',
'login_manual_password_change_hint'
                           => 'Jika akaun anda diwajibkan menukar kata laluan, sistem akan bawa anda terus ke halaman kemas kini selepas kata laluan semasa disahkan.',
'login_manual_access_status_title'
                           => 'Semakan akses manual',
'login_manual_access_status_msg'
                           => 'Akaun manual mesti sudah disahkan. Jika kata laluan tamat tempoh atau ditanda perlu ditukar, anda tidak akan masuk ke dashboard tetapi akan dialihkan terus ke flow tukar kata laluan.',
'login_btnLogin'           => 'Log Masuk',
'login_btnOneId'           => 'OneID Login',
'login_or_continue_with'   => 'Atau teruskan dengan',
'login_oneid_note'         => 'Gunakan OneID untuk akaun staf atau pelajar yang dikonfigurasi bagi Single Sign-On.',
'login_oneid_unavailable_note'
                          => 'Log masuk OneID tidak tersedia di bawah polisi log masuk semasa.',

// Status & mesej masa
'login_locked_msg'         => 'Akaun anda telah dikunci. Sila cuba lagi selepas',
'login_seconds'            => 'saat',

// Gagal / ralat log masuk
'login_fail_msg'           => 'Log masuk gagal. Cuba lagi:',
'login_fail_title'         => 'Log Masuk Gagal',

// Validasi borang
'login_form_validation_error'
                           => 'Sila masukkan ID dan kata laluan.',

// Akses & sekatan
'login_access_blocked_title'
                           => 'Akses Ditolak',
'login_access_blocked_msg'
                           => 'Akaun anda telah disekat. Sila hubungi pentadbir sistem untuk bantuan.',
'login_account_not_verified_title'
                           => 'Akaun Belum Disahkan',
'login_account_not_verified_msg'
                           => 'Akaun anda belum disahkan untuk log masuk manual. Sila hubungi pentadbir sistem.',
'login_password_change_required_title'
                           => 'Tukar Kata Laluan Diperlukan',
'login_password_change_required_msg'
                           => 'Akaun anda memerlukan pertukaran kata laluan sebelum log masuk diteruskan.',
'login_password_expired_title'
                           => 'Kata Laluan Tamat Tempoh',
'login_password_expired_msg'
                           => 'Kata laluan anda telah tamat tempoh dan perlu dikemas kini sebelum log masuk diteruskan.',
'password_change_page_title'
                           => 'Tukar Kata Laluan',
'password_change_kicker'
                           => 'Keselamatan Akaun',
'password_change_heading'
                           => 'Kemaskini kata laluan anda',
'password_change_reason_required'
                           => 'Sistem memerlukan anda menukar kata laluan sebelum akses manual boleh diteruskan.',
'password_change_reason_expired'
                           => 'Kata laluan anda telah tamat tempoh. Sila tetapkan kata laluan baharu untuk meneruskan log masuk manual.',
'password_change_login_id_label'
                           => 'Login ID',
'password_change_policy_summary'
                           => 'Gunakan sekurang-kurangnya 8 aksara serta gabungan huruf besar, huruf kecil, dan nombor. Elakkan menggunakan Login ID anda sebagai sebahagian kata laluan.',
'password_change_new_password_label'
                           => 'Kata laluan baharu',
'password_change_new_password_hint'
                           => 'Minimum 8 aksara, sekurang-kurangnya satu huruf besar, satu huruf kecil, dan satu nombor.',
'password_change_confirm_password_label'
                           => 'Sahkan kata laluan baharu',
'password_change_live_title'
                           => 'Semakan segera',
'password_change_rule_confirm_match'
                           => 'Pengesahan kata laluan sepadan',
'password_change_rule_no_login_id'
                           => 'Kata laluan tidak mengandungi Login ID',
'password_change_submit_btn'
                           => 'Simpan Kata Laluan',
'password_change_footer_note'
                           => 'Selepas berjaya, anda perlu log masuk semula menggunakan kata laluan baharu.',
'password_change_success_title'
                           => 'Kata Laluan Dikemas Kini',
'password_change_success_msg'
                           => 'Kata laluan anda berjaya dikemas kini. Sila log masuk semula menggunakan kata laluan baharu.',
'password_change_notification_subject'
                           => 'Kata laluan anda telah dikemas kini',
'password_change_session_invalid_title'
                           => 'Sesi Tukar Kata Laluan Tidak Sah',
'password_change_session_invalid_msg'
                           => 'Sesi pertukaran kata laluan tidak sah. Sila mulakan semula proses log masuk.',
'password_change_session_expired_msg'
                           => 'Sesi pertukaran kata laluan telah tamat. Sila log masuk semula untuk meneruskan.',
'password_change_error_required'
                           => 'Sila isi kata laluan baharu dan pengesahannya.',
'password_change_error_mismatch'
                           => 'Pengesahan kata laluan tidak sepadan.',
'password_change_error_min_length'
                           => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
'password_change_error_min_length_template'
                           => 'Kata laluan mesti sekurang-kurangnya %d aksara.',
'password_change_error_uppercase'
                           => 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf besar.',
'password_change_error_lowercase'
                           => 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf kecil.',
'password_change_error_number'
                           => 'Kata laluan mesti mengandungi sekurang-kurangnya satu nombor.',
'password_change_error_symbol'
                           => 'Kata laluan mesti mengandungi sekurang-kurangnya satu simbol.',
'password_change_error_contains_login'
                           => 'Kata laluan tidak boleh mengandungi Login ID anda.',
'password_change_error_csrf'
                           => 'Token keselamatan tidak sah. Sila muat semula halaman dan cuba lagi.',
'password_change_error_user_not_found'
                           => 'Akaun anda tidak lagi ditemui dalam sistem. Sila hubungi pentadbir sistem.',
'password_change_error_reuse_current'
                           => 'Kata laluan baharu mesti berbeza daripada kata laluan semasa.',
'password_change_error_reuse_history'
                           => 'Kata laluan baharu tidak boleh sama dengan 5 kata laluan terdahulu.',
'password_change_error_update_failed'
                           => 'Gagal mengemas kini kata laluan anda. Sila cuba lagi atau hubungi pentadbir sistem.',
'forgot_password_page_title'
                           => 'Lupa Kata Laluan',
'forgot_password_kicker'
                           => 'Pemulihan Akaun',
'forgot_password_heading'
                           => 'Minta pautan reset kata laluan',
'forgot_password_intro'
                           => 'Masukkan Login ID atau emel berdaftar untuk akaun manual anda. Jika akaun itu layak, sistem akan menghantar pautan reset sekali guna ke alamat emel yang didaftarkan.',
'forgot_password_login_id_label'
                           => 'Login ID',
'forgot_password_login_id_placeholder'
                           => 'Masukkan Login ID anda',
'forgot_password_login_id_hint'
                           => 'Anda boleh gunakan Login ID sebenar atau emel berdaftar. Untuk pengguna umum, Login ID biasanya memang alamat emel.',
'forgot_password_submit_btn'
                           => 'Hantar Pautan Reset',
'forgot_password_back_to_login'
                           => 'Kembali ke log masuk',
'forgot_password_footer_note'
                           => 'Atas faktor keselamatan, sistem akan memaparkan mesej yang sama walaupun akaun tidak ditemui atau tidak layak.',
'forgot_password_success_title'
                           => 'Semakan Telah Dihantar',
'forgot_password_success_msg'
                           => 'Jika akaun anda layak untuk reset kata laluan, pautan reset telah dihantar ke emel yang didaftarkan.',
'forgot_password_success_reference'
                           => 'Permintaan reset telah direkodkan untuk',
'forgot_password_mail_subject'
                           => 'Reset kata laluan akaun anda',
'forgot_password_error_required'
                           => 'Sila masukkan Login ID anda.',
'forgot_password_error_csrf'
                           => 'Token keselamatan tidak sah. Sila muat semula halaman dan cuba lagi.',
'forgot_password_error_rate_limited'
                           => 'Terlalu banyak permintaan reset kata laluan. Sila cuba semula selepas beberapa minit.',
'forgot_password_feature_unavailable'
                           => 'Fungsi reset kata laluan belum tersedia sepenuhnya pada persekitaran ini. Sila hubungi pentadbir sistem.',
'reset_password_page_title'
                           => 'Reset Kata Laluan',
'reset_password_kicker'
                           => 'Pautan Keselamatan',
'reset_password_heading'
                           => 'Tetapkan kata laluan baharu',
'reset_password_intro'
                           => 'Gunakan pautan sekali guna ini untuk menetapkan kata laluan baharu bagi akaun manual anda.',
'reset_password_token_invalid'
                           => 'Pautan reset ini tidak sah, telah digunakan, atau telah tamat tempoh. Sila minta pautan baharu.',
'reset_password_request_new_link'
                           => 'Minta pautan baharu',
'reset_password_submit_btn'
                           => 'Tetapkan Kata Laluan',
'reset_password_success_title'
                           => 'Reset Kata Laluan Berjaya',
'reset_password_success_msg'
                           => 'Kata laluan anda berjaya ditetapkan semula. Sila log masuk menggunakan kata laluan baharu.',
'login_maintenance_mode_title'
                           => 'Penyelenggaraan Aktif',
'login_maintenance_mode_msg'
                           => 'Sistem sedang dalam mod penyelenggaraan. Sila cuba semula sebentar lagi.',
'login_category_disabled_title'
                           => 'Log Masuk Tidak Dibenarkan',
'login_category_disabled_msg'
                           => 'Akses log masuk untuk akaun anda tidak tersedia pada masa ini. Sila hubungi pentadbir jika perlu.',
'login_manual_not_allowed_title'
                           => 'Log Masuk Manual Tidak Dibenarkan',
'login_manual_not_allowed_msg'
                           => 'Akaun anda tidak dibenarkan menggunakan log masuk manual. Sila gunakan Single Sign-On (SSO).',
'login_sso_first_login_required_title'
                           => 'Gunakan SSO Dahulu',
'login_sso_first_login_required_msg'
                           => 'Akses pertama bagi akaun ini mesti dibuat melalui Single Sign-On (SSO). Selepas rekod aplikasi diwujudkan, log masuk manual boleh digunakan jika polisi membenarkannya.',
'login_manual_account_not_ready_title'
                           => 'Akaun Belum Diaktifkan',
'login_manual_account_not_ready_msg'
                           => 'Akaun anda belum diaktifkan untuk akses manual dalam aplikasi ini. Sila hubungi pentadbir sistem.',
'login_sso_not_allowed_title'
                           => 'Log Masuk SSO Tidak Dibenarkan',
'login_sso_not_allowed_msg'
                           => 'Akaun anda tidak dibenarkan menggunakan Single Sign-On (SSO) pada masa ini.',
'login_sso_payload_invalid_title'
                           => 'Maklumat SSO Tidak Sah',
'login_sso_payload_invalid_msg'
                           => 'Maklumat pengesahan daripada Single Sign-On (SSO) tidak lengkap atau tidak sah. Sila cuba semula.',
'login_sso_session_expired_title'
                           => 'Sesi SSO Tamat',
'login_sso_session_expired_msg'
                           => 'Sesi pengesahan Single Sign-On (SSO) telah tamat. Sila mulakan semula log masuk melalui OneID.',
'login_sso_user_not_found_title'
                           => 'Akaun Tidak Ditemui',
'login_sso_user_not_found_msg'
                           => 'Akaun anda tidak ditemui dalam sistem ini. Sila hubungi pentadbir sistem.',
'login_sso_account_not_provisioned_title'
                           => 'Akses Belum Sedia',
'login_sso_account_not_provisioned_msg'
                           => 'Akaun anda belum diaktifkan untuk aplikasi ini. Sila hubungi pentadbir sistem.',
'login_sso_default_group_invalid_title'
                           => 'Tetapan Akses Belum Lengkap',
'login_sso_default_group_invalid_msg'
                           => 'Penyediaan akaun automatik tidak dapat dilengkapkan kerana kumpulan akses lalai belum dikonfigurasi dengan betul. Sila hubungi pentadbir sistem.',
'login_sso_source_unavailable_title'
                           => 'Sumber Identiti Tidak Tersedia',
'login_sso_source_unavailable_msg'
                           => 'Sistem tidak dapat membaca maklumat akaun anda dari sistem sumber buat masa ini. Sila cuba lagi kemudian atau hubungi pentadbir sistem.',
'login_sso_auto_provision_failed_title'
                           => 'Log Masuk Tidak Dapat Diselesaikan',
'login_sso_auto_provision_failed_msg'
                           => 'Identiti SSO anda telah diterima, tetapi rekod aplikasi tidak dapat disediakan buat masa ini. Sila hubungi pentadbir sistem.',
'login_sso_service_unreachable_title'
                           => 'Perkhidmatan SSO Tidak Dapat Dicapai',
'login_sso_service_unreachable_msg'
                           => 'Sistem tidak dapat berhubung dengan perkhidmatan Single Sign-On (SSO) buat masa ini. Sila cuba semula kemudian.',

// Akaun dikunci / dibuka
'login_locked_title'       => 'Akaun Dikunci',
'login_unlocked_title'     => 'Akaun Dibuka',
'login_unlocked_msg'
                           => 'Akaun anda telah dibuka semula. Sila log masuk semula.',

// Ralat sistem (controller / config)
'config_login_error_title'
                           => 'Ralat Log Masuk',
'config_login_error_message'
                           => 'Berlaku ralat semasa proses log masuk. Sila cuba lagi.',


/* =====================================================
 * PROFILE (profile_)
 * ===================================================== */

// =========================
// Tajuk & Breadcrumb
// =========================
'profile_title'                 => 'Profil Pengguna',
'profile_breadcrumb_dashboard'  => 'Papan Pemuka',
'profile_breadcrumb'            => 'Profil',

// =========================
// Status
// =========================
'profile_status_active'         => 'Aktif',
'profile_status_inactive'       => 'Tidak Aktif',

// =========================
// Aksesibiliti / Media
// =========================
'profile_avatar_alt'            => 'Avatar pengguna',

// =========================
// Maklumat Asas
// =========================
'profile_no_staf'               => 'No. Staf',
'profile_no_pekerja'            => 'No. Pekerja',
'profile_jabatan'               => 'Jabatan',
'profile_emel'                  => 'Emel',
'profile_lang_title'            => 'Pilihan Bahasa',
'profile_lang_card_title'       => 'Pilihan Bahasa',
'profile_lang_label'            => 'Bahasa Pilihan',
'profile_lang_help'             => 'Bahasa ini akan digunakan untuk akaun anda selagi bahasa tersebut masih aktif dalam sistem.',
'profile_lang_option_ms'        => 'Bahasa Melayu',
'profile_lang_option_en'        => 'English',
'profile_lang_save_btn'         => 'Simpan Bahasa',
'profile_lang_save_success'     => 'Pilihan bahasa anda berjaya disimpan.',
'profile_lang_session_note'     => 'Pilih bahasa paparan utama untuk akaun anda. Perubahan akan digunakan pada sesi seterusnya di seluruh modul yang menyokong pelbagai bahasa.',

// =========================
// Butang & Quick Actions
// =========================
'profile_btn_copy_no_staf'      => 'Salin No. Staf',
'profile_btn_copy_email'        => 'Salin Emel',
'profile_identity_summary'      => 'Ringkasan Identiti',
'profile_panel_subtitle'        => 'Maklumat akaun dan identiti pengguna yang aktif dalam sistem.',
'profile_no_job_info'           => 'Tiada maklumat jawatan',
'profile_no_department_info'    => 'Tiada maklumat jabatan',
'profile_jawatan'               => 'Jawatan',

// =========================
// Tabs
// =========================
'profile_tabs_label'            => 'Tab profil pengguna',
'profile_tab_profil_pengguna'   => 'Profil Pengguna',
'profile_tab_login_aktiviti'    => 'Login Aktiviti',
'profile_tab_jejak_audit'       => 'Jejak Audit',

// =========================
// Login Activity
// =========================
'profile_login_date'            => 'Tarikh & Masa',
'profile_login_ip'              => 'Alamat IP',
'profile_login_device'          => 'Peranti',
'profile_login_duration'        => 'Tempoh',
'profile_login_status'          => 'Status',
'profile_login_actions'         => 'Tindakan',

'profile_login_active'          => 'Aktif',
'profile_login_ended'           => 'Tamat',
'profile_login_current'         => 'Semasa',
'profile_login_kill_session'    => 'Tamatkan sesi',

'profile_login_aktiviti_empty'  => 'Tiada rekod login aktiviti ditemui.',

// SweetAlert – tamatkan sesi
'profile_login_kill_confirm_title'
                                => 'Tamatkan Sesi?',
'profile_login_kill_confirm_text'
                                => 'Anda pasti mahu tamatkan sesi ini? Pengguna akan dipaksa log keluar.',
'profile_login_kill_confirm_yes'
                                => 'Ya, Tamatkan',
'profile_login_kill_confirm_no' => 'Batal',

'profile_login_kill_force_title'
                                => 'Sesi anda akan ditamatkan',
'profile_login_kill_force_text'
                                => 'Anda akan dilog keluar dalam',

'profile_login_kill_success'
                                => 'Sesi berjaya ditamatkan',
'profile_login_kill_success_text'
                                => 'Sesi telah ditamatkan',

'profile_login_kill_error'
                                => 'Gagal tamatkan sesi',
'profile_login_kill_error_network'
                                => 'Ralat rangkaian. Sila cuba lagi.',
'profile_login_kill_error_no_session'
                                => 'ID sesi tidak sah',

/* =====================================================
 * SETTINGS (config result titles)
 * These keys used by TetapanSistemController for save results
 * ===================================================== */
'emel_title'        => 'Tetapan Emel',
'emel_title_save'   => 'Tetapan emel berjaya disimpan',
'bahasa_title'      => 'Tetapan Bahasa',
'bahasa_title_save' => 'Tetapan bahasa berjaya disimpan',
'tema_title'        => 'Tetapan Tema',
'tema_title_save'   => 'Tetapan tema berjaya dikemaskini',
'config_js_btn_tutup' => 'Tutup',

// =========================
// Audit Trail
// =========================

'profile_audit_date'            => 'Tarikh & Masa',
'profile_audit_ip'              => 'Alamat IP',
'profile_audit_outcome'         => 'Hasil',
'profile_audit_severity'        => 'Keparahan',
'profile_audit_actions'         => 'Tindakan',


'profile_audit_view_meta'       => 'Lihat metadata',


'profile_audit_field'           => 'Medan',

'profile_audit_no_field_changes'
                                => 'Tiada perubahan medan direkodkan.',


'profile_audit_modal_close'     => 'Tutup',

// =========================
// DataTables (Profile)
 // =========================
'profile_dt_show'               => 'Papar',
'profile_dt_records'            => 'rekod',
'profile_dt_search'             => 'Cari',
'profile_dt_no_records'         => 'Tiada rekod ditemui',
'profile_dt_info'
                                => 'Paparan _START_ hingga _END_ daripada _TOTAL_ rekod',
'profile_dt_info_empty'
                                => 'Paparan 0 hingga 0 daripada 0 rekod',
'profile_dt_filtered'
                                => 'ditapis daripada _MAX_ jumlah rekod',
'profile_dt_previous'           => 'Sebelum',
'profile_dt_next'               => 'Seterusnya',
'profile_dt_error'              => 'Ralat memuat data',
'profile_dt_error_msg'          => 'Gagal dapatkan data.',

// =========================
// Lain-lain
// =========================
'profile_loading'               => 'Memuatkan…',
'profile_loading_label'         => 'Loading...',
'profile_loading_aria'          => 'Memuatkan',
'profile_js_copied'             => 'Disalin',
'profile_copy_empty'            => 'Tiada teks untuk disalin',
'profile_copy_wait'             => 'Sila tunggu sebentar sebelum menyalin lagi',
'profile_copy_failed'           => 'Gagal menyalin teks',
'profile_error_load'            => 'Ralat memuat data profil. Sila cuba lagi atau hubungi pentadbir sistem.',
'profile_refresh_failed'        => 'Ralat memuat semula data',
'profile_dt_load_failed'        => 'Ralat memuat jadual data',
'profile_datatables_timeout'    => 'DataTables gagal dimuat dalam tempoh menunggu',
'profile_empty_notice'
                                => 'Profil tidak dijumpai. Sesi login mungkin tamat atau rekod tiada.',
'profile_duration_seconds_short'=> 's',
'profile_duration_minutes_short'=> 'm',
'profile_duration_hours_short'  => 'j',
'profile_duration_days_short'   => 'h',
'profile_device_unknown'        => 'Tidak diketahui',
'profile_device_ipad'           => 'iPad',
'profile_device_iphone'         => 'iPhone',
'profile_device_android'        => 'Android',
'profile_device_mobile'         => 'Mudah Alih',
'profile_device_windows'        => 'Windows',
'profile_device_macos'          => 'macOS',
'profile_device_linux'          => 'Linux',
'profile_device_chromeos'       => 'Chrome OS',
'profile_audit_user'            => 'Pengguna',
'profile_audit_activity'        => 'Aktiviti',
'profile_audit_title'           => 'Jejak Audit',
'profile_audit_event_id'        => 'Event ID',
'profile_audit_summary_short'   => 'Maklumat Ringkas',
'profile_audit_no_info'         => 'Tiada maklumat',
'profile_data_label'            => 'Data',
'profile_audit_tab_summary'     => 'Ringkasan',
'profile_audit_tab_changes'     => 'Perubahan',
'profile_audit_tab_extra'       => 'Maklumat Tambahan',
'profile_audit_tab_raw'         => 'Data Mentah',
'profile_audit_primary_changes' => 'Perubahan Utama',
'profile_audit_search_changes'  => 'Cari perubahan...',
'profile_audit_extra_info'      => 'Maklumat Tambahan',
'profile_audit_raw_data'        => 'Data Mentah',
'profile_audit_no_changes'      => 'Tiada perubahan',
'profile_before_label'          => 'Sebelum',
'profile_after_label'           => 'Selepas',
'profile_before_raw_label'      => 'Sebelum',
'profile_after_raw_label'       => 'Selepas',
'profile_close_label'           => 'Tutup',
'profile_load_metadata_failed'  => 'Gagal muat metadata acara',
'profile_metadata_forbidden_title' => 'Paparan Terhad',
'profile_metadata_forbidden_text'  => 'Metadata jejak audit hanya tersedia untuk semakan Super Admin.',
'profile_swal_ok'               => 'OK',
'profile_audit_changes_separator' => '-- Perubahan --',
'profile_audit_download_failed' => 'Gagal memuat turun fail JSON',

/* =====================================================
 * SENARAI PENGGUNA (userList_)
 * ===================================================== */

// =========================
// Tajuk & Breadcrumb
// =========================
'userList_page_heading_main'     => 'Senarai Pengguna',

// =========================
// Kolum Jadual
// =========================
'userList_col_no'                => 'No.',
'userList_col_name_staffid'      => 'Nama (ID Staf)',
'userList_col_department'        => 'Nama Jabatan',
'userList_col_university'        => 'Nama Universiti',
'userList_col_position'          => 'Nama Jawatan',
'userList_col_group'             => 'Nama Kumpulan',
'userList_col_access'            => 'Akses',
'userList_col_actions'           => 'Tindakan',

// =========================
// Status & Paparan
// =========================
'userList_avatar_alt'            => 'Avatar pengguna',
'userList_empty_value'           => '—',
'userList_http_status_prefix'    => 'HTTP',
'userList_no_records'            => 'Tiada rekod',
'userList_loading_staff'         => 'Memuatkan senarai staf',
'userList_loading_user_list'     => 'Memuatkan senarai pengguna',
'userList_no_staff_data'         => 'Tiada data staf',
'userList_processing'            => 'Memproses...',
'userList_loading'               => 'Memuat...',
'userList_dt_length_menu'        => 'Papar _MENU_ rekod',
'userList_access_granted'        => 'Dibenarkan',
'userList_access_blocked'        => 'Disekat',
'userList_dt_search_label'       => 'Carian:',
'userList_dt_info'               => 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ rekod',
'userList_dt_info_empty'         => 'Tiada rekod',
'userList_dt_paginate_prev'      => 'Sebelum',
'userList_dt_paginate_next'      => 'Seterusnya',
'userList_dt_zero_records'       => 'Tiada rekod dijumpai',
'userList_btn_ok'                => 'OK',

// =========================
// Tindakan
// =========================
'userList_action_change_group'   => 'Tukar kumpulan',
'userList_action_delete_user'    => 'Padam pengguna',

// =========================
// Modal — Umum
// =========================
'userList_modal_title'           => 'Tukar Kumpulan Pengguna',
'userList_modal_title_public'    => 'Kemaskini Pengguna Umum',
'userList_modal_add_title'       => 'Tambah Staf',
'userList_modal_add_student_title' => 'Tambah Pelajar',
'userList_modal_add_public_title' => 'Tambah Umum',
'userList_modal_label_reset_password' => 'Reset Kata Laluan',

'userList_modal_btn_save'        => 'Simpan',
'userList_modal_btn_add'         => 'Tambah',
'userList_modal_btn_close'       => 'Tutup',
'userList_modal_btn_cancel'      => 'Batal',

// =========================
// Modal — Label Maklumat
// =========================
'userList_modal_label_name'      => 'Nama',
'userList_modal_label_department'=> 'Nama Jabatan',
'userList_modal_label_public_university'
                                => 'Nama Universiti',
'userList_modal_label_position' => 'Jawatan',
'userList_modal_label_group'    => 'Kumpulan',
'userList_modal_label_access'   => 'Akses',
'userList_modal_label_staff'    => 'Staf',
'userList_modal_label_student'  => 'Pelajar',
'userList_modal_label_public_name' => 'Nama Penuh',
'userList_modal_label_public_nickname' => 'Nama Ringkas',
'userList_modal_label_public_email' => 'Alamat Emel',
'userList_modal_label_public_phone' => 'No. Telefon',
'userList_modal_label_public_idno' => 'No. Pengenalan',
'userList_modal_label_public_password' => 'Kata Laluan',
'userList_modal_label_public_password_confirm' => 'Sahkan Kata Laluan',
'userList_modal_label_faculty'  => 'Fakulti',
'userList_modal_label_program'  => 'Program',
'userList_modal_label_level'    => 'Tahap Pengajian',
'userList_modal_label_status_category' => 'Status Kategori',
'userList_primary_role_label'   => 'Peranan Utama',

// =========================
// Modal — Seksyen
// =========================
'userList_modal_section_user_info'
                                => 'Maklumat Pengguna',
'userList_modal_section_staff_info'
                                => 'Maklumat Staf',
'userList_modal_section_student_info'
                                => 'Maklumat Pelajar',
'userList_modal_section_public_info'
                                => 'Maklumat Pengguna Umum',
'userList_modal_section_settings'
                                => 'Tetapan Pengguna',
'userList_public_password_hint'  => 'Biarkan kosong jika tidak mahu menukar kata laluan.',
'userList_public_password_reset_forces_change'
                                => 'Menetapkan atau reset kata laluan di sini akan memaksa pengguna menukar kata laluan semasa login seterusnya.',
'userList_password_reset_forces_change'
                                => 'Reset password pengguna akan paksa tukar kata laluan semasa login seterusnya.',

// =========================
// Placeholder
// =========================
'userList_modal_placeholder_select_staff'
                                => 'Pilih staf...',
'userList_modal_placeholder_select_student'
                                => 'Cari pelajar aktif...',
'userList_modal_placeholder_select_public'
                                => 'Pilih pengguna umum...',
'userList_modal_placeholder_select_group'
                                => 'Pilih kumpulan...',
'userList_group_filter_placeholder'
                                => '-- Pilih kumpulan --',
'userList_modal_add_role'       => '+ Peranan',
'userList_modal_extra_role_title' => 'Peranan Tambahan',
'userList_role_none'            => 'Tiada peranan tambahan.',

// =========================
// Validasi / Keadaan
// =========================
'userList_staff_already_exists'  => 'Sudah Wujud',
'userList_student_already_exists' => 'Sudah Wujud',
'userList_user_default'          => 'Pengguna',

// =========================
// Pengesahan Padam
// =========================
'userList_delete_confirm_title'  => 'Padam Pengguna?',
'userList_delete_confirm_message'
                                => 'Adakah anda pasti mahu memadam pengguna ini?',
'userList_delete_confirm_warning'
                                => 'Tindakan ini tidak boleh dipulihkan.',
'userList_delete_confirm_yes'    => 'Ya, Padam',

// =========================
// Kejayaan
// =========================
'userList_success_add'           => 'Pengguna berjaya ditambah.',
'userList_success_add_student'   => 'Pelajar berjaya ditambah.',
'userList_success_delete'        => 'Pengguna berjaya dipadam.',
'userList_success_update_roles'  => 'Peranan tambahan berjaya dikemas kini.',
'userList_success_title'         => 'Berjaya',
'userList_success_update_group'  => 'Kumpulan dan akses pengguna berjaya dikemas kini.',
'userList_success_update_public' => 'Maklumat pengguna umum berjaya dikemas kini.',

// =========================
// Ralat
// =========================
'userList_error_title'           => 'Ralat',
'userList_loading_student'       => 'Memuatkan pelajar',
'userList_err_load_student'      => 'Gagal memuatkan data pelajar.',
'userList_btn_saving'            => 'Menyimpan...',
'userList_err_update_public'     => 'Sila semak semula maklumat pengguna umum dan cuba lagi.',

// Buttons
'userList_sync_button'           => 'Sync Data',
'userList_sync_staff_button'     => 'Sync Staf',
'userList_sync_student_button'   => 'Sync Pelajar',
'userList_tab_staff'             => 'Akses Staf',
'userList_tab_student'           => 'Akses Pelajar',
'userList_tab_public'            => 'Akses Umum',
'userList_col_name_matric'       => 'Nama / Matrik',
'userList_col_name_login'        => 'Nama / Login ID',
'userList_col_faculty'           => 'Fakulti',
'userList_sync_processing'       => 'Memproses…',
'userList_add_button'            => 'Tambah Staf',
'userList_add_student_button'    => 'Tambah Pelajar',
'userList_add_public_button'     => 'Tambah Umum',
'userList_search_placeholder'    => 'Carian',
'userList_debug_group_ui_title'  => 'DEBUG GROUP UI',
'userList_debug_group_ui_stats'  => 'groups: %d, by_id: %d, by_code: %d, css_rules: %d',
'userList_debug_group_ui_hint'   => 'Append {query} to view this panel.',
'userList_sync_student_pending_title' => 'Sync Pelajar',
'userList_sync_student_pending_text'  => 'Sync data pelajar akan diaktifkan dalam fasa seterusnya.',
'userList_public_pending_title'  => 'Tambah Umum',
'userList_public_pending_text'   => 'Flow Add Public akan diaktifkan dalam fasa seterusnya.',

'userList_err_add_failed'        => 'Gagal menambah pengguna.',
'userList_err_delete_failed'     => 'Gagal memadam pengguna.',
'userList_err_load_data'         => 'Gagal memuatkan data.',
'userList_err_load_staff'        => 'Ralat memuatkan staf',
'userList_err_param'             => 'Parameter tidak lengkap.',
'userList_err_update_group'      => 'Gagal kemas kini kumpulan.',

'userList_err_invalid_response'
                                => 'Ralat: Respons pelayan tidak sah',
'userList_err_invalid_json'
                                => 'Ralat: Respons pelayan tidak sah (bukan JSON).',
'userList_err_non_json'
                                => 'Ralat: Respons pelayan bukan JSON.',
'userList_err_server'            => 'Ralat pelayan',
'userList_err_unknown'           => 'Ralat tidak diketahui.',
'userList_err_no_permission'     => 'Anda tidak mempunyai kebenaran untuk melakukan tindakan ini.',
'userList_rate_limit_title'      => 'Terlalu Cepat',
'userList_rate_limit_text'       => 'Sila tunggu sebentar sebelum cuba lagi.',
'userList_protected_badge'       => 'Akaun Dilindungi',
'userList_protected_tooltip'     => 'Akaun ini dilindungi oleh sistem. Ia tidak boleh dipadam, dan perubahan akses hanya dibenarkan oleh pemilik akaun.',
'userList_auto_provisioned_badge' => 'Auto Provisioned',
'userList_auto_provisioned_tooltip' => 'Rekod ini diwujudkan secara automatik melalui log masuk %s.',
'userList_protected_delete_denied' => 'Akaun pengguna ini dilindungi oleh sistem dan tidak boleh dipadam.',
'userList_protected_self_manage_only' => 'Akaun pengguna ini dilindungi oleh sistem dan hanya boleh diurus oleh pemilik akaun tersebut.',
'userList_ajax_invalid_data'     => 'Data tidak sah.',
'userList_ajax_rate_limited'     => 'Terlalu banyak permintaan. Sila cuba lagi selepas beberapa saat.',
'userList_ajax_system_error'     => 'Ralat sistem. Sila hubungi pentadbir sistem.',
'userList_ajax_invalid_user_param' => 'Parameter pengguna tidak sah.',
'userList_ajax_name_required'    => 'Nama tidak boleh kosong.',
'userList_ajax_invalid_email'    => 'Alamat emel tidak sah.',
'userList_ajax_password_min'     => 'Kata laluan mesti sekurang-kurangnya 6 aksara.',
'userList_ajax_password_confirm_mismatch' => 'Pengesahan kata laluan tidak sepadan.',
'userList_ajax_user_not_found'   => 'Pengguna tidak ditemui.',
'userList_ajax_public_only'      => 'Hanya pengguna umum boleh dikemas kini melalui flow ini.',
'userList_ajax_invalid_group'    => 'Kumpulan pengguna tidak sah atau tidak wujud dalam sistem.',
'userList_ajax_invalid_public_group' => 'Kumpulan yang dipilih tidak sah untuk akses umum.',
'userList_ajax_email_exists'     => 'Pengguna dengan alamat emel ini sudah wujud dalam sistem.',
'userList_ajax_delete_permission_superadmin' => 'Anda tidak mempunyai kebenaran untuk memadam pengguna. Hanya Super Admin dibenarkan.',
'userList_ajax_invalid_user_id'  => 'ID pengguna tidak sah.',
'userList_ajax_user_not_found_in_system' => 'Pengguna tidak dijumpai dalam sistem.',
'userList_ajax_delete_self_denied' => 'Anda tidak dibenarkan memadam akaun anda sendiri.',
'userList_ajax_delete_failed_backend' => 'Gagal memadam data pengguna.',
'userList_ajax_invalid_action'   => 'Aksi tidak sah.',
'userList_ajax_incomplete_params_userid' => 'Parameter tidak lengkap (userID diperlukan).',
'userList_ajax_incomplete_params_group_or_flag' => 'Parameter tidak lengkap (groupID atau flag diperlukan).',
'userList_ajax_user_schema_invalid' => 'Skema pengguna tiada f_groupID atau f_groupKod.',
'userList_ajax_group_not_found'  => 'Kumpulan tidak ditemui.',
'userList_ajax_no_fields_update' => 'Tiada field untuk dikemas kini.',
'userList_ajax_update_record_failed' => 'Gagal kemas kini rekod pengguna.',
'userList_ajax_user_not_found_db' => 'Pengguna tidak ditemui dalam database.',
'userList_ajax_no_change_group'  => 'Tiada perubahan. Kumpulan kekal sama.',
'userList_ajax_no_change_access' => 'Tiada perubahan. Akses kekal sama.',
'userList_ajax_roles_update_failed' => 'Ralat sistem semasa mengemas kini peranan.',

// =========================
// Sync
// =========================
'userList_sync_success_title'    => 'Sync Berjaya',
'userList_sync_success_message'  => 'Data berjaya disegerakkan.',
'userList_sync_result_message'   => 'Sync berjaya. %d rekod dikemas kini, %d rekod dilangkau, %d ralat.',
'userList_sync_summary_title'    => 'Ringkasan Sync',
'userList_sync_updated'          => 'Dikemas kini',
'userList_sync_skipped'          => 'Dilangkau',
'userList_sync_errors'           => 'Ralat',
'userList_sync_total'            => 'Jumlah',
'userList_sync_error_title'      => 'Sync Gagal',
'userList_sync_error'            => 'Ralat semasa sync data.',


/* =====================================================
 * KUMPULAN PENGGUNA (userGroup_)
 * ===================================================== */

// =========================
// Tajuk & Breadcrumb
// =========================
'userGroup_page_title'              => 'Kumpulan Pengguna',
'userGroup_intro'                   => 'Senarai kumpulan pengguna.',

// =========================
// Jadual Utama
// =========================
'userGroup_col_code'                => 'Kod Kumpulan',
'userGroup_col_name'                => 'Nama Kumpulan',
'userGroup_col_module_access'       => 'Akses Modul',
'userGroup_col_menu_access'         => 'Akses Menu',
'userGroup_col_group_access'        => 'Akses Kumpulan',
'userGroup_col_menu'                => 'Menu',
'userGroup_col_reorder' => 'Susun semula',
'userGroup_col_status'              => 'Status',
'userGroup_col_actions'             => 'Tindakan',

'userGroup_no_records'              => 'Tiada rekod',
'userGroup_loading' => 'Memuatkan data',

// =========================
// Butang & Aksi
// =========================
'userGroup_btn_add_menu'            => 'Tambah Menu',
'modul_tambah'                      => 'Tambah Modul',
'modul_tambah_title'                => 'Tambah Modul',
'modul_edit_label'                  => 'Kemaskini',
'modul_kemaskini_title'             => 'Kemaskini Modul',
'modul_nama_ms'                     => 'Nama Modul (BM)',
'modul_nama_en'                     => 'Nama Modul (EN)',
'modul_icon'                        => 'Icon',
'modul_icon_help'                   => 'Pilih ikon modul daripada senarai cadangan.',
'modul_icon_group_general'          => 'Umum',
'modul_icon_group_users'            => 'Pengguna',
'modul_icon_group_system'           => 'Sistem',
'modul_icon_group_files'            => 'Fail & Dokumen',
'modul_icon_group_data'             => 'Data & Laporan',
'modul_icon_group_security'         => 'Keselamatan',
'modul_icon_group_communication'    => 'Komunikasi & Organisasi',
'modul_icon_group_more'             => 'Lain-lain',
'modul_susunan'                     => 'Susunan',
'modul_order_auto_help'             => 'Susunan dijana automatik oleh sistem dan tidak boleh diubah secara manual.',
'modul_simpan'                      => 'Simpan',
'modul_batal'                       => 'Batal',
'modul_berjaya_title'               => 'Berjaya',
'modul_berjaya_msg'                 => 'Modul berjaya ditambah.',
'modul_kemaskini_msg'               => 'Modul berjaya dikemaskini.',
'modul_ralat_title'                 => 'Ralat',
'modul_ralat_duplikat'              => 'Nama modul telah wujud. Sila gunakan nama lain.',
'modul_ralat_wajib'                 => 'Nama Modul (BM) wajib diisi.',
'modul_ralat_tidak_sah'             => 'ID modul tidak sah.',
'modul_ralat_tidak_jumpa'           => 'Modul tidak dijumpai.',
'userGroup_btn_close'               => 'Tutup',
'userGroup_btn_save'                => 'Simpan',

// =========================
// Label Kecil
// =========================
'userGroup_label_module'            => 'modul',
'userGroup_label_menu'              => 'menu',
'userGroup_label_modul_fallback'    => 'Modul',

// =========================
// Susun Menu
// =========================
'userGroup_move_up' => 'Gerakkan ke atas',
'userGroup_move_down' => 'Gerakkan ke bawah',

// =========================
// Status
// =========================
'userGroup_status_on' => 'ON',
'userGroup_status_off' => 'OFF',

// =========================
// Modal — Tambah / Sunting Menu
// =========================
'userGroup_modal_add_menu_title'    => 'Tambah Menu',
'userGroup_modal_edit_menu_title' => 'Sunting Menu',
'userGroup_field_menu_domain' => 'Domain Menu',
'userGroup_field_menu_domain_help' => 'Tentukan domain fungsi menu ini.',
'userGroup_menu_domain_staff' => 'STAF',
'userGroup_menu_domain_student' => 'PELAJAR',
'userGroup_menu_domain_public' => 'UMUM',
'userGroup_menu_domain_shared' => 'SHARED',
'userGroup_field_menu_staff_only_visibility' => 'Paparan Semasa Staff Only',
'userGroup_field_menu_staff_only_visibility_help' => 'Kawal sama ada menu ini masih dipaparkan apabila mode Staff Only diaktifkan.',
'userGroup_menu_staff_only_show' => 'Papar',
'userGroup_menu_staff_only_hide' => 'Sembunyi',
'userGroup_menu_staff_only_show_full' => 'Papar di Staff Only',
'userGroup_menu_staff_only_hide_full' => 'Sembunyi di Staff Only',
'userGroup_col_visibility' => 'Visibiliti',
'userGroup_menu_path_info' => 'Lihat path menu',

// =========================
// Modal — Akses
// =========================
'userGroup_modal_group_access_title'=> 'Akses Kumpulan',
'userGroup_modal_summary_title'     => 'Ringkasan Akses',
'userGroup_modal_pick_menu_title'   => 'Pilih Menu',
'userGroup_modal_group_create_title'=> 'Tambah Kumpulan',
'userGroup_modal_group_edit_title'  => 'Edit Kumpulan',

// =========================
// Medan Borang
// =========================
'userGroup_field_group'             => 'Kumpulan',
'userGroup_field_group_code'        => 'Kod Kumpulan',
'userGroup_field_group_name'        => 'Nama Kumpulan',
'userGroup_field_group_category'    => 'Kategori Pengguna',
'userGroup_field_group_category_help' => 'Tentukan kumpulan ini untuk akses Staf, Pelajar, atau Umum.',
'userGroup_field_group_identity'    => 'Identiti Kumpulan',
'userGroup_field_group_presentation'=> 'Paparan & Gaya',
'userGroup_field_group_access_setup'=> 'Tetapan Akses',
'userGroup_field_group_preview'     => 'Preview Kumpulan',
'userGroup_col_category'            => 'Kategori',
'userGroup_field_modul'             => 'Modul',
'userGroup_field_color'             => 'Warna',
'userGroup_field_color_help'        => 'Pilih warna secara visual.',
'userGroup_field_pick_module'       => 'Pilih Modul',
'userGroup_field_pick_module_help'  => 'Pilih satu atau lebih modul untuk kumpulan ini.',
'userGroup_field_pick_menu'         => 'Pilih Menu (bergantung pada Modul)',
'userGroup_field_pick_menu_help'    => 'Menu akan dipaparkan mengikut modul yang dipilih.',
'userGroup_field_path'              => 'Path',
'userGroup_field_path_placeholder'  => 'contoh: laporan.php',

'userGroup_field_name_ms'           => 'Nama (MS)',
'userGroup_field_name_en'           => 'Nama (EN)',

'userGroup_field_status'            => 'Status',
'userGroup_field_position_label' => 'Letak di modul sasaran',
'userGroup_position_top' => 'Di atas sekali',
'userGroup_position_bottom' => 'Di bawah sekali',

'userGroup_loading_modules'         => 'Memuatkan modul…',

// =========================
// Ralat & Validasi
// =========================
'userGroup_error_unknown'           => 'Ralat tidak diketahui.',
'userGroup_error_network'           => 'Ralat rangkaian.',
'userGroup_error_reorder' => 'Gagal tukar susunan.',
'userGroup_error_load_access' => 'Gagal memuat akses.',
'userGroup_error_load_menu' => 'Gagal memuat menu.',
'userGroup_error_get_menu' => 'Gagal dapatkan butiran menu.',

'userGroup_err_path_required'       => 'Path tidak boleh kosong.',
'userGroup_err_group_code_name_required' => 'Sila isi Kod, Nama dan Kategori Kumpulan.',
'userGroup_err_modul_required'      => 'Sila pilih Modul.',
'userGroup_err_add_menu' => 'Gagal tambah menu.',
'userGroup_err_save_menu' => 'Gagal simpan menu.',

// =========================
// SweetAlert — Padam
// =========================
'userGroup_confirm_title'           => 'Pengesahan',
'userGroup_confirm_delete_group_text' => 'Padam kumpulan "{name}"?',
'userGroup_confirm_yes_delete'      => 'Ya, Padam',
'userGroup_confirm_yes'             => 'Ya, padam',
'userGroup_confirm_cancel'          => 'Batal',
'userGroup_confirm_delete_menu_title' => 'Padam menu "{name}"?',
'userGroup_confirm_delete_menu_intro' => 'Menu <strong>{name}</strong> akan <u>dipadam</u>.',
'userGroup_confirm_delete_menu_cleanup' => 'Menu ini juga akan dibersihkan daripada <em>semua kumpulan</em> yang rujuk ID ini.',
'userGroup_confirm_delete_menu_irreversible' => 'Tindakan ini tidak boleh diundur.',
'userGroup_confirm_delete_menu_fallback' => 'Padam menu "{name}"? Menu ini juga akan dibersihkan daripada semua kumpulan.',
'userGroup_delete_module_confirm_title' => 'Padam modul ini?',
'userGroup_delete_module_confirm_text' => 'Modul "{name}" akan dipadam. Tindakan ini tidak boleh diundur.',
'userGroup_delete_module_confirm_fallback' => 'Padam modul "{name}"?',

'userGroup_error'                   => 'Ralat',
'userGroup_not_allowed_title'       => 'Tidak Dibenarkan',
'userGroup_delete_failed_title'     => 'Gagal',
'userGroup_deleted_title'           => 'Dipadam',
'userGroup_delete_fail'             => 'Gagal memadam menu.',
'userGroup_delete_group_success'    => 'Kumpulan berjaya dipadam.',
'userGroup_delete_group_fail'       => 'Gagal memadam kumpulan.',
'userGroup_delete_group_network_fail' => 'Ralat rangkaian semasa memadam kumpulan.',
'userGroup_delete_menu_cleanup_success' => 'Menu "{name}" dibersihkan dari semua kumpulan.',
'userGroup_delete_module_not_allowed' => 'Anda tidak mempunyai kebenaran untuk memadam modul.',
'userGroup_delete_module_invalid_id' => 'ID modul tidak sah.',
'userGroup_delete_module_not_found' => 'Modul tidak ditemui.',
'userGroup_delete_module_has_menus' => 'Modul tidak boleh dipadam kerana masih mempunyai {count} menu.',
'userGroup_delete_module_fail' => 'Gagal memadam modul.',
'userGroup_delete_module_success' => 'Modul berjaya dipadam.',
'userGroup_delete_module_network_fail' => 'Ralat rangkaian semasa memadam modul.',
'userGroup_module_delete_label' => 'Padam',
'userGroup_module_reorder_note' => 'Seret modul untuk ubah susunan paparan. Susunan akan dikemas kini terus.',
'userGroup_module_drag_label' => 'Seret untuk susun semula',
'userGroup_module_reorder_not_allowed' => 'Anda tidak mempunyai kebenaran untuk mengubah susunan modul.',
'userGroup_module_reorder_invalid_payload' => 'Payload susunan modul tidak sah.',
'userGroup_module_reorder_minimum' => 'Sekurang-kurangnya dua modul diperlukan untuk susun semula.',
'userGroup_module_reorder_incomplete' => 'Susunan modul tidak lengkap.',
'userGroup_method_not_allowed' => 'Method tidak dibenarkan.',
'userGroup_csrf_invalid' => 'CSRF token tidak sah.',
'userGroup_menu_save_success_create' => 'Menu berjaya ditambah',
'userGroup_menu_save_success_update' => 'Menu berjaya dikemaskini',
'userGroup_err_group_modul_path_required' => 'Sila pilih Kumpulan, Modul dan isi Path.',

// =========================
// Undo (Opsyenal)
 // =========================
'userGroup_undo_btn'                => 'Batal',
'userGroup_undo_title'              => 'Batal',
'userGroup_undo_message'            => 'Menu "%s" telah dipadam.',
'userGroup_undo_info'
                                      => 'Fungsi Undo memerlukan endpoint sisi server. Sila hubungi pentadbir.',

// =========================
// Carian & DataTables
// =========================
'userGroup_search_group_placeholder'=> 'Cari kumpulan...',
'userGroup_search_menu_placeholder' => 'Cari...',
'userGroup_dt_length_menu' => 'Papar _MENU_ rekod',
'userGroup_dt_info'
                                      => 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ entri',
'userGroup_dt_info_empty' => 'Tiada rekod',
'userGroup_dt_info_filtered'
                                      => '(ditapis daripada _MAX_ jumlah entri)',
'userGroup_dt_paginate_first'       => 'Pertama',
'userGroup_dt_paginate_last' => 'Akhir',
'userGroup_dt_paginate_next'        => 'Seterusnya',
'userGroup_dt_paginate_previous'    => 'Sebelumnya',
'userGroup_edit_group'              => 'Edit Kumpulan',
'userGroup_delete_group'            => 'Padam Kumpulan',
'userGroup_info_title'              => 'Makluman',
'userGroup_info_select_group_first' => 'Sila pilih kumpulan dahulu melalui butang Akses Menu.',
'userGroup_btn_menu_label'          => 'Menu',
'userGroup_btn_module_label'        => 'Modul',
'userGroup_btn_group_label'         => 'Kumpulan',
'userGroup_loading_short'           => 'Memuat…',
'userGroup_load_modules_fail'       => 'Gagal memuat modul dari: {url} — {error}',
'userGroup_no_modules_found'        => 'Tiada modul ditemui.',


/* =====================================================
 * MATRIKS AKSES (access_)
 * ===================================================== */

// =========================
// Tajuk & Pengenalan
// =========================
'access_title'        => 'Matriks Akses',
'access_intro'        => 'Matriks akses baca sahaja untuk menu sistem.',

// =========================
// Jadual
// =========================
'access_col_no'       => '#',
'access_menu'         => 'Menu',
'access_path'         => 'Laluan',
'access_modul'        => 'Modul',
'access_user_level'   => 'Tahap Pengguna',

// =========================
// Tahap Pengguna
// =========================
'access_ada'          => 'Ada Akses',
'access_tiada'        => 'Tiada Akses',

// =========================
// Paparan
// =========================
'access_no'           => 'Tiada rekod',


/* =====================================================
 * TETAPAN SISTEM (config_, config_js_, config_db_)
 * ===================================================== */

/* =========================
 * Tajuk
 * ========================= */
'config_system' => 'Konfigurasi Sistem',

/* =========================
 * Tab Navigasi
 * ========================= */
'config_tab_general' => 'Umum',
'config_tab_auth'    => 'Polisi Login',
'config_tab_emel'   => 'Emel',
'config_tab_db'     => 'Pangkalan Data',
'config_tab_tema'   => 'Tema',
'config_tab_bahasa' => 'Bahasa',

'config_tab_auth_intro' => 'Kawal siapa yang boleh log masuk dan kaedah pengesahan yang dibenarkan bagi setiap kategori pengguna.',
'config_auth_intro_title' => 'Turutan Penilaian Polisi',
'config_auth_subtab_overview' => 'Gambaran Polisi',
'config_auth_subtab_global' => 'Akses Global',
'config_auth_subtab_category' => 'Kawalan Kategori Log Masuk',
'config_auth_subtab_password' => 'Polisi Kata Laluan',
'config_auth_subtab_sso' => 'Kawalan SSO',
'config_auth_overview_title' => 'Gambaran Polisi',
'config_auth_overview_sub' => 'Gunakan paparan ini untuk menyemak precedence polisi dan snapshot runtime yang dinilai sebelum menyimpan perubahan.',
'config_auth_intro_point_maintenance' => 'Maintenance mode mengatasi akses log masuk biasa untuk semua pengguna bukan Super Admin.',
'config_auth_intro_point_category' => 'Kawalan kategori menentukan sama ada pengguna Staf, Pelajar, dan Umum dibenarkan log masuk.',
'config_auth_intro_point_sso' => 'Tetapan SSO hanya menentukan kaedah log masuk selepas akses dibenarkan.',
'config_auth_section_global' => 'Akses Global',
'config_auth_section_global_sub' => 'Tetapan ini memberi kesan operasi paling tinggi terhadap ketersediaan log masuk.',
'config_auth_section_category' => 'Kawalan Kategori Log Masuk',
'config_auth_section_category_sub' => 'Tentukan kategori pengguna yang dibenarkan melepasi pintu log masuk.',
'config_auth_section_sso' => 'Kawalan SSO',
'config_auth_section_sso_sub' => 'Tentukan sama ada log masuk menggunakan SSO, manual, atau routing hybrid mengikut kategori.',
'config_auth_section_summary' => 'Ringkasan Polisi',
'config_auth_section_summary_sub' => 'Semak status polisi yang dinilai sebelum menyimpan perubahan.',
'config_auth_maintenance_mode' => 'Maintenance Mode',
'config_auth_maintenance_mode_help' => 'Apabila dihidupkan, hanya Super Admin boleh log masuk.',
'config_auth_login_enable_staf' => 'Benarkan Log Masuk Staf',
'config_auth_login_enable_staf_help' => 'Benarkan pengguna dalam kategori Staf log masuk.',
'config_auth_login_enable_pelajar' => 'Benarkan Log Masuk Pelajar',
'config_auth_login_enable_pelajar_help' => 'Benarkan pengguna dalam kategori Pelajar log masuk.',
'config_auth_login_enable_umum' => 'Benarkan Log Masuk Umum',
'config_auth_login_enable_umum_help' => 'Benarkan pengguna dalam kategori Umum log masuk.',
'config_auth_auto_provision_title' => 'Auto Provisioning SSO',
'config_auth_auto_provision_sub' => 'Benarkan pengguna Staf dan Pelajar kali pertama diwujudkan automatik melalui SSO menggunakan group default yang dikonfigurasikan.',
'config_auth_auto_provision_staff_panel' => 'Auto Provision Staf',
'config_auth_auto_provision_staff_panel_sub' => 'Hanya terpakai pada log masuk pertama melalui SSO. Log masuk manual staf masih memerlukan rekod pengguna aplikasi yang sedia ada.',
'config_auth_auto_provision_student_panel' => 'Auto Provision Pelajar',
'config_auth_auto_provision_student_panel_sub' => 'Hanya terpakai pada log masuk pertama melalui SSO. Log masuk manual pelajar masih memerlukan rekod pengguna aplikasi yang sedia ada.',
'config_auth_auto_provision_staf_sso' => 'Aktifkan Auto Provision SSO Staf',
'config_auth_auto_provision_staf_sso_help' => 'Cipta rekod aplikasi Staf secara automatik apabila pengguna SSO yang sah belum mempunyai rekod dalam tbl_m_user.',
'config_auth_auto_provision_pelajar_sso' => 'Aktifkan Auto Provision SSO Pelajar',
'config_auth_auto_provision_pelajar_sso_help' => 'Cipta rekod aplikasi Pelajar secara automatik apabila pengguna SSO yang sah belum mempunyai rekod dalam tbl_m_user.',
'config_auth_default_group_staff_code' => 'Kod Group Default Staf',
'config_auth_default_group_staff_code_help' => 'Kod group yang akan diberikan kepada pengguna Staf yang diwujudkan automatik selepas log masuk SSO kali pertama berjaya.',
'config_auth_default_group_student_code' => 'Kod Group Default Pelajar',
'config_auth_default_group_student_code_help' => 'Kod group yang akan diberikan kepada pengguna Pelajar yang diwujudkan automatik selepas log masuk SSO kali pertama berjaya.',
'config_auth_auto_provision_note' => 'Auto provisioning hanya terpakai melalui SSO. Log masuk manual Staf dan Pelajar masih memerlukan akaun aplikasi yang sedia ada.',
'config_auth_sso_enabled' => 'Aktifkan SSO',
'config_auth_sso_enabled_help' => 'Aktifkan Single Sign-On sebagai mekanisme pengesahan yang tersedia.',
'config_auth_sso_site_id' => 'OneID Site ID',
'config_auth_sso_site_id_help' => 'Digunakan untuk pendaftaran aplikasi OneID bagi sistem ini.',
'config_auth_sso_idp_domain' => 'OneID IdP Domain',
'config_auth_sso_idp_domain_help' => 'URL asas Identity Provider OneID yang digunakan untuk pengalihan SSO dan pengesahan token.',
'config_auth_sso_mode' => 'Mode SSO',
'config_auth_sso_mode_help' => 'Pilih bagaimana kaedah log masuk dikenakan kepada setiap kategori pengguna yang dibenarkan.',
'config_auth_sso_mode_effective' => 'Ringkasan Mode',
'config_auth_sso_mode_all' => 'ALL',
'config_auth_sso_mode_manual' => 'MANUAL',
'config_auth_sso_mode_hybrid' => 'HYBRID',
'config_auth_sso_mode_all_note' => 'Dalam mode ALL, pengguna Staf dan Pelajar mesti menggunakan SSO. Pengguna Umum masih boleh log masuk secara manual.',
'config_auth_sso_mode_manual_note' => 'Dalam mode MANUAL, semua kategori yang dibenarkan menggunakan log masuk manual.',
'config_auth_sso_mode_hybrid_note' => 'Dalam mode HYBRID, setiap kategori mengikut kaedah log masuk yang dikonfigurasikan sendiri.',
'config_auth_hybrid_header' => 'Pemetaan Kategori HYBRID',
'config_auth_hybrid_sub' => 'Tentukan kaedah log masuk bagi setiap kategori apabila Mode SSO ditetapkan kepada HYBRID.',
'config_auth_sso_hybrid_staf' => 'Kaedah Log Masuk Staf',
'config_auth_sso_hybrid_staf_help' => 'Pilih kaedah log masuk untuk pengguna Staf.',
'config_auth_sso_hybrid_pelajar' => 'Kaedah Log Masuk Pelajar',
'config_auth_sso_hybrid_pelajar_help' => 'Pilih kaedah log masuk untuk pengguna Pelajar.',
'config_auth_sso_hybrid_umum' => 'Kaedah Log Masuk Umum',
'config_auth_sso_hybrid_umum_help' => 'Pilih kaedah log masuk untuk pengguna Umum.',
'config_auth_hybrid_option_sso' => 'SSO',
'config_auth_hybrid_option_manual' => 'Manual',
'config_auth_enabled' => 'Aktif',
'config_auth_disabled' => 'Tidak Aktif',
'config_auth_allowed' => 'Dibenarkan',
'config_auth_blocked' => 'Disekat',
'config_auth_category_note' => 'Jika semua kategori dimatikan, hanya Super Admin akan kekal boleh log masuk.',
'config_auth_summary_status' => 'Status Konfigurasi',
'config_auth_summary_status_ok' => 'Snapshot polisi sedia untuk digunakan pada runtime.',
'config_auth_summary_status_invalid_note' => 'Konfigurasi perlu dibetulkan sebelum enforcement runtime diaktifkan.',
'config_auth_summary_effective' => 'Ringkasan Berkesan',
'config_auth_summary_not_configured' => 'Belum dikonfigurasi',
'config_auth_summary_warnings' => 'Amaran',
'config_auth_summary_errors' => 'Ralat',
'config_auth_status_valid' => 'Sah',
'config_auth_status_warning' => 'Sah dengan Amaran',
'config_auth_status_invalid' => 'Tidak Sah',
'config_auth_summary_maintenance_on' => 'Maintenance mode dihidupkan. Hanya Super Admin boleh log masuk.',
'config_auth_summary_maintenance_off' => 'Maintenance mode dimatikan. Penilaian polisi biasa digunakan.',
'config_auth_summary_staff_enabled' => 'Log masuk Staf dibenarkan.',
'config_auth_summary_staff_disabled' => 'Log masuk Staf dimatikan.',
'config_auth_summary_student_enabled' => 'Log masuk Pelajar dibenarkan.',
'config_auth_summary_student_disabled' => 'Log masuk Pelajar dimatikan.',
'config_auth_summary_public_enabled' => 'Log masuk Umum dibenarkan.',
'config_auth_summary_public_disabled' => 'Log masuk Umum dimatikan.',
'config_auth_summary_sso_enabled' => 'SSO diaktifkan dalam mode %s.',
'config_auth_summary_sso_disabled' => 'SSO dimatikan. Semua kategori yang dibenarkan menggunakan log masuk manual.',
'config_auth_summary_staff_auto_provision_enabled' => 'Auto provision SSO Staf diaktifkan dengan group default %s.',
'config_auth_summary_staff_auto_provision_disabled' => 'Auto provision SSO Staf dimatikan.',
'config_auth_summary_student_auto_provision_enabled' => 'Auto provision SSO Pelajar diaktifkan dengan group default %s.',
'config_auth_summary_student_auto_provision_disabled' => 'Auto provision SSO Pelajar dimatikan.',
'config_auth_warning_sso_disabled_mode' => 'Mode SSO telah ditetapkan tetapi SSO sedang dimatikan.',
'config_auth_warning_all_categories_blocked' => 'Semua kategori log masuk disekat. Hanya Super Admin akan kekal boleh log masuk.',
'config_auth_warning_staff_auto_provision_group_missing' => 'Auto provision SSO Staf diaktifkan tetapi kod group default staf masih kosong.',
'config_auth_warning_student_auto_provision_group_missing' => 'Auto provision SSO Pelajar diaktifkan tetapi kod group default pelajar masih kosong.',
'config_auth_warning_staff_auto_provision_category_disabled' => 'Auto provision SSO Staf diaktifkan tetapi log masuk staf sedang dimatikan.',
'config_auth_warning_student_auto_provision_category_disabled' => 'Auto provision SSO Pelajar diaktifkan tetapi log masuk pelajar sedang dimatikan.',
'config_auth_warning_staff_auto_provision_route_manual' => 'Auto provision SSO Staf diaktifkan tetapi laluan log masuk staf semasa bukan SSO.',
'config_auth_warning_student_auto_provision_route_manual' => 'Auto provision SSO Pelajar diaktifkan tetapi laluan log masuk pelajar semasa bukan SSO.',
'config_auth_actions_note' => 'Perubahan di sini akan terus mempengaruhi polisi log masuk semasa dan kaedah pengesahan yang dibenarkan untuk setiap kategori pengguna.',
'config_auth_save' => 'Simpan Polisi Login',
'config_auth_success_title' => 'Berjaya',
'config_auth_success_text' => 'Tetapan polisi login berjaya disimpan.',
'config_auth_success_text_summary' => 'Tetapan polisi login berjaya disimpan. Perubahan: %s.',
'config_auth_validation_title' => 'Ralat Validasi',
'config_auth_validation_bool' => '%s mesti nilai hidup atau mati yang sah.',
'config_auth_validation_enum' => '%s mesti salah satu nilai yang dibenarkan: %s.',
'config_auth_validation_int_range' => '%s mesti nombor antara %d hingga %d.',
'config_auth_password_policy_core' => 'Polisi Teras Kata Laluan',
'config_auth_password_policy_core_help' => 'Tetapan ini mengawal rule asas yang digunakan oleh flow reset password dan pertukaran kata laluan.',
'config_auth_password_min_length' => 'Panjang Minimum Kata Laluan',
'config_auth_password_min_length_help' => 'Bilangan minimum aksara yang wajib dipenuhi untuk kata laluan baharu.',
'config_auth_password_expiry_days' => 'Tempoh Luput Kata Laluan (Hari)',
'config_auth_password_expiry_days_help' => 'Bilangan hari sebelum kata laluan tamat tempoh dan perlu dikemas kini.',
'config_auth_password_history_count' => 'Bilangan Sejarah Kata Laluan',
'config_auth_password_history_count_help' => 'Bilangan kata laluan terdahulu yang tidak dibenarkan untuk diguna semula.',
'config_auth_password_reset_token_minutes' => 'Tempoh Sah Pautan Reset (Minit)',
'config_auth_password_reset_token_minutes_help' => 'Tempoh maksimum pautan reset kata laluan kekal sah sebelum menjadi tidak valid.',
'config_auth_password_complexity' => 'Rule Kompleksiti Kata Laluan',
'config_auth_password_complexity_help' => 'Pilih semakan komposisi kata laluan yang wajib dikuatkuasakan pada flow tukar dan reset kata laluan.',
'config_auth_password_require_uppercase' => 'Wajib Huruf Besar',
'config_auth_password_require_uppercase_help' => 'Wajib ada sekurang-kurangnya satu huruf besar pada setiap kata laluan baharu.',
'config_auth_password_require_lowercase' => 'Wajib Huruf Kecil',
'config_auth_password_require_lowercase_help' => 'Wajib ada sekurang-kurangnya satu huruf kecil pada setiap kata laluan baharu.',
'config_auth_password_require_number' => 'Wajib Nombor',
'config_auth_password_require_number_help' => 'Wajib ada sekurang-kurangnya satu digit nombor pada setiap kata laluan baharu.',
'config_auth_password_require_symbol' => 'Wajib Simbol',
'config_auth_password_require_symbol_help' => 'Wajib ada sekurang-kurangnya satu simbol seperti ! @ # atau % pada setiap kata laluan baharu.',
'config_auth_password_block_loginid_variants' => 'Sekat Variasi Login ID',
'config_auth_password_block_loginid_variants_help' => 'Tolak kata laluan yang mengandungi Login ID atau variasi normalize yang hampir sama.',
'config_auth_login_security' => 'Kawalan Keselamatan Login',
'config_auth_login_security_help' => 'Konfigurasi had cubaan gagal dan tempoh lockout yang digunakan bila login manual berulang kali gagal.',
'config_auth_login_max_attempts' => 'Had Cubaan Gagal',
'config_auth_login_max_attempts_help' => 'Bilangan cubaan login manual yang gagal sebelum ID itu dikunci.',
'config_auth_login_lock_seconds' => 'Tempoh Lockout (Saat)',
'config_auth_login_lock_seconds_help' => 'Berapa lama lockout login manual kekal aktif selepas had cubaan gagal dicapai.',
'config_auth_login_identifier_ip_max_attempts' => 'Had Cubaan Gagal Login ID + IP',
'config_auth_login_identifier_ip_max_attempts_help' => 'Had cubaan gagal untuk Login ID yang sama dari IP yang sama sebelum pasangan itu dikenakan throttle.',
'config_auth_login_identifier_ip_lock_seconds' => 'Tempoh Lockout Login ID + IP (Saat)',
'config_auth_login_identifier_ip_lock_seconds_help' => 'Berapa lama pasangan Login ID dan IP kekal dikenakan throttle selepas had cubaan gagal dicapai.',
'config_auth_login_ip_max_attempts' => 'Had Cubaan Gagal IP',
'config_auth_login_ip_max_attempts_help' => 'Had cubaan gagal dari IP yang sama merentas akaun sebelum IP itu dikenakan throttle.',
'config_auth_login_ip_lock_seconds' => 'Tempoh Lockout IP (Saat)',
'config_auth_login_ip_lock_seconds_help' => 'Berapa lama IP kekal dikenakan throttle selepas had cubaan gagal dicapai.',
'config_auth_password_policy_future_note' => 'Subtab ini memang disediakan untuk berkembang dengan tetapan kata laluan lain seperti rule complexity, semakan Login ID, dan behavior paksa tukar kata laluan.',
'config_auth_save_error_title' => 'Ralat Menyimpan',
'config_auth_save_error_text' => 'Gagal menyimpan tetapan polisi login. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_auth_system_error_title' => 'Ralat Sistem',
'config_auth_system_error_text' => 'Ralat berlaku semasa menyimpan tetapan polisi login. Sila semak log sistem untuk maklumat lanjut.',
'config_auth_audit_message' => 'Tetapan polisi login dikemas kini (%d medan): %s',
'config_auth_audit_no_changes' => 'tiada perubahan medan',

'config_general_brand_header' => 'Identiti Sistem',
'config_general_brand_sub' => 'Paparan utama dan metadata sistem',
'config_general_mail_header' => 'Identiti Emel',
'config_general_mail_sub' => 'Nama sistem dan pautan umum untuk template emel',
'config_general_site_title' => 'Site Title',
'config_general_site_favicon' => 'Path Favicon',
'config_general_site_default_home' => 'Laluan Default Home',
'config_general_system_name' => 'Nama Sistem',
'config_general_meta_author' => 'Meta Author',
'config_general_support_email' => 'Emel Sokongan',
'config_general_branding_login_header_logo' => 'Logo Header Login',
'config_general_branding_login_panel_logo' => 'Logo Panel Login',
'config_general_branding_topbar_logo_light' => 'Logo Topbar Light',
'config_general_branding_topbar_logo_dark' => 'Logo Topbar Dark',
'config_general_branding_topbar_logo_sm' => 'Logo Topbar Kecil',
'config_general_branding_sidebar_logo' => 'Logo Sidebar',
'config_general_session_idle_timeout_minutes' => 'Idle Timeout (Minit)',
'config_general_upload_manual_max_mb' => 'Had Maksimum Manual (MB)',
'config_general_footer_text' => 'Teks Footer',
'config_general_footer_text_ms' => 'Teks Footer (BM)',
'config_general_footer_text_en' => 'Teks Footer (EN)',
'config_general_org_name' => 'Nama Organisasi',
'config_general_org_short' => 'Kod Ringkas Organisasi',
'config_general_org_website' => 'Laman Web Organisasi',
'config_general_mail_system_name' => 'Nama Sistem Emel',
'config_general_mail_action_url' => 'Default Action URL',
'config_general_mail_footer_note' => 'Nota Footer Emel',
'config_general_mail_footer_note_ms' => 'Nota Footer Emel (BM)',
'config_general_mail_footer_note_en' => 'Nota Footer Emel (EN)',
'config_general_note' => 'Tetapan ini hanya menyimpan override ke pangkalan data. Fail settings.php kekal sebagai konfigurasi asas sistem.',
'config_general_subtab_site' => 'Site',
'config_general_subtab_branding' => 'Branding',
'config_general_subtab_identity' => 'Sistem & Organisasi',
'config_general_subtab_mail' => 'Emel',
'config_general_subtab_limits' => 'Had',
'config_general_subtab_site_note' => 'Tetapan site mengawal title, favicon, dan laluan masuk utama sistem.',
'config_general_subtab_branding_note' => 'Semua path branding merujuk kepada aset visual seperti logo login, topbar, dan sidebar.',
'config_general_subtab_identity_note' => 'Maklumat ini mengawal identiti sistem, organisasi, metadata, dan footer umum.',
'config_general_subtab_mail_note' => 'Gunakan subtab ini untuk identiti umum yang digunakan oleh template emel sistem.',
'config_general_subtab_limits_note' => 'Had tingkah laku ini mempengaruhi tempoh sesi aktif dan saiz maksimum muat naik manual.',
'config_general_site_title_help' => 'Nama utama sistem untuk title browser dan paparan umum.',
'config_general_site_favicon_help' => 'Path ikon kecil yang dipaparkan pada tab browser.',
'config_general_site_default_home_help' => 'Laluan halaman utama selepas login dan untuk pautan logo sistem.',
'config_general_branding_login_header_logo_help' => 'Logo bahagian atas halaman login.',
'config_general_branding_login_panel_logo_help' => 'Logo utama dalam panel login.',
'config_general_branding_topbar_logo_light_help' => 'Logo untuk topbar mod terang.',
'config_general_branding_topbar_logo_dark_help' => 'Logo untuk topbar mod gelap.',
'config_general_branding_topbar_logo_sm_help' => 'Versi kecil logo untuk topbar ringkas.',
'config_general_branding_sidebar_logo_help' => 'Logo yang dipaparkan dalam sidebar sistem.',
'config_general_system_name_help' => 'Nama rasmi sistem untuk paparan utama aplikasi.',
'config_general_meta_author_help' => 'Nilai meta author dalam head untuk tujuan metadata sistem.',
'config_general_support_email_help' => 'Alamat emel sokongan utama untuk rujukan pentadbir atau pengguna.',
'config_general_org_name_help' => 'Nama penuh organisasi pemilik sistem.',
'config_general_org_short_help' => 'Kod ringkas atau singkatan organisasi.',
'config_general_org_website_help' => 'URL laman web rasmi organisasi.',
'config_general_footer_text_help' => 'Teks footer global yang dipaparkan pada bahagian bawah sistem.',
'config_general_mail_system_name_help' => 'Nama sistem yang dipaparkan dalam template emel.',
'config_general_mail_action_url_help' => 'Pautan tindakan lalai yang digunakan oleh emel sistem bila berkaitan.',
'config_general_mail_footer_note_help' => 'Nota footer standard yang dipaparkan di hujung emel sistem.',
'config_general_session_idle_timeout_minutes_help' => 'Tempoh tiada aktiviti sebelum sistem memaparkan amaran sesi tamat.',
'config_general_upload_manual_max_mb_help' => 'Had maksimum saiz fail PDF untuk modul manual pengguna.',
'config_general_actions_note' => 'Perubahan di sini akan override nilai asas settings.php tanpa menulis semula fail asal.',
'config_general_save' => 'Simpan Tetapan Umum',
'config_general_success_title' => 'Tetapan Umum',
'config_general_success_text' => 'Tetapan umum berjaya disimpan.',
'config_general_success_text_summary' => 'Tetapan umum berjaya disimpan. Perubahan: %s.',
'config_general_validation_title' => 'Ralat Validasi',
'config_general_validation_max' => '%s terlalu panjang (maksimum %d aksara).',
'config_general_validation_email' => '%s mesti alamat emel yang sah.',
'config_general_validation_url' => '%s mesti URL yang sah atau #.',
'config_general_validation_int' => '%s mesti nombor bulat yang sah.',
'config_general_validation_int_range' => '%s mesti antara %d hingga %d.',
'config_general_save_error_title' => 'Ralat Menyimpan',
'config_general_save_error_text' => 'Gagal menyimpan tetapan umum. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_general_system_error_title' => 'Ralat Sistem',
'config_general_system_error_text' => 'Ralat berlaku semasa menyimpan tetapan umum. Sila semak log sistem untuk maklumat lanjut.',
'config_general_audit_message' => 'Tetapan umum dikemas kini (%d medan): %s',
'config_general_audit_no_changes' => 'tiada perubahan medan',

/* =========================
 * TAB EMEL
 * ========================= */
'config_tab_emel_header_setting'        => 'Konfigurasi Pelayan Emel',
'config_tab_emel_header_setting_sub'    => 'Konfigurasi pelayan',
'config_tab_emel_driver'                => 'Pemacu Emel',
'config_tab_emel_host'                  => 'Hos Emel',
'config_tab_emel_port'                  => 'Port',
'config_tab_emel_encryption'            => 'Penyulitan',
'config_tab_emel_sel_tiada'             => 'Tiada',

'config_tab_emel_header_emel'            => 'Butiran Akaun Emel',
'config_tab_emel_header_emel_sub'        => 'Identiti penghantar dan kelayakan akaun',
'config_tab_emel_account_emel'           => 'Akaun Emel (Username)',
'config_tab_emel_katalaluan_emel'        => 'Kata Laluan Emel',
'config_tab_emel_password_hint'          => 'Biarkan kosong untuk mengekalkan kata laluan semasa',
'config_tab_emel_from'                   => 'Emel daripada?',
'config_tab_emel_from_name'              => 'Nama Pemilik Emel',
'config_tab_emel_note_server'            => 'Gunakan konfigurasi SMTP sebenar yang dibenarkan oleh pelayan.',
'config_tab_emel_note_sender'            => 'Pastikan alamat From dan akaun SMTP sepadan dengan polisi pelayan untuk elak mesej ditolak.',
'config_tab_emel_actions_note'           => 'Simpan hanya selepas maklumat SMTP dan akaun emel disahkan betul.',

'config_tab_emel_uji_emel'               => 'Uji Sambungan Emel',
'config_tab_emel_simpan_tetapan_emel'    => 'Simpan Tetapan Emel',
'config_email_validation_title'          => 'Ralat Validasi',
'config_email_validation_max'            => '%s terlalu panjang (maksimum %d aksara).',
'config_email_validation_host'           => '%s tidak sah. Sila masukkan domain atau alamat IP yang sah.',
'config_email_validation_port_numeric'   => '%s mesti nombor.',
'config_email_validation_port_range'     => '%s mesti antara %d hingga %d.',
'config_email_validation_email'          => '%s tidak sah. Sila masukkan alamat emel yang sah.',
'config_email_validation_encryption'     => '%s tidak sah. Hanya %s dibenarkan.',
'config_email_validation_driver'         => '%s tidak sah. Hanya %s dibenarkan.',
'config_email_success_text_summary'      => 'Tetapan emel berjaya disimpan. Perubahan: %s.',
'config_email_save_error_title'          => 'Ralat Menyimpan',
'config_email_save_error_text'           => 'Gagal menyimpan tetapan emel. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_email_system_error_title'        => 'Ralat Sistem',
'config_email_system_error_text'         => 'Ralat berlaku semasa menyimpan tetapan emel. Sila semak log sistem untuk maklumat lanjut.',
'config_email_audit_message'             => 'Tetapan emel dikemas kini (%d medan): %s',
'config_email_audit_no_changes'          => 'tiada perubahan medan',

/* =========================
 * TAB DATABASE
 * ========================= */
'config_tab_db_container_sub'            => 'Urus pemilihan runtime Sybase dan lihat butiran sambungan MySQL utama.',
'config_tab_db_header'                   => 'Sybase (Pilih Satu Sahaja)',
'config_tab_db_header_sub'               => 'Pilih satu sambungan Sybase aktif',
'config_tab_db_sybase_header'            => 'Hanya satu sambungan Sybase dibenarkan aktif dalam satu masa.',
'config_tab_db_sybase_sambungan'         => 'Nama Sambungan',
'config_tab_db_sybase_keterangan'        => 'Keterangan',
'config_tab_db_environment_production'   => 'Production',
'config_tab_db_environment_production_desc'
                                        => 'Guna sambungan Sybase staf production untuk operasi sistem sebenar.',
'config_tab_db_environment_development'  => 'Development',
'config_tab_db_environment_development_desc'
                                        => 'Guna sambungan Sybase staf development untuk ujian dan kerja staging.',
'config_tab_db_mode_header'              => 'Mode Operasi',
'config_tab_db_mode_header_sub'          => 'Pilih domain Sybase yang dibenarkan untuk sistem',
'config_tab_db_mode_note'                => 'Mode ini menentukan sama ada sistem hanya menggunakan domain staf atau membenarkan domain staf dan pelajar.',
'config_tab_db_mode_column'              => 'Mode',
'config_tab_db_mode_desc_column'         => 'Keterangan',
'config_tab_db_mode_staff_only'          => 'Staf Sahaja',
'config_tab_db_mode_staff_only_desc'     => 'Hanya domain staf digunakan. Sambungan pelajar kekal dimatikan.',
'config_tab_db_mode_staff_student'       => 'Staf + Pelajar',
'config_tab_db_mode_staff_student_desc'  => 'Kedua-dua domain staf dan pelajar dibenarkan.',
'config_tab_db_runtime_header'           => 'Ringkasan Runtime Semasa',
'config_tab_db_runtime_header_sub'       => 'Ringkasan ini menunjukkan bagaimana runtime semasa akan berfungsi selepas tetapan disimpan.',
'config_tab_db_runtime_field'            => 'Komponen',
'config_tab_db_runtime_value'            => 'Nilai Runtime',
'config_tab_db_runtime_staff'            => 'Sybase Staf',
'config_tab_db_runtime_student'          => 'Sybase Pelajar',
'config_tab_db_runtime_environment'      => 'Persekitaran',
'config_tab_db_runtime_mode'             => 'Mode Operasi',
'config_tab_db_runtime_disabled'         => 'Dimatikan',
'config_tab_db_subtab_sybase'            => 'Sybase',
'config_tab_db_subtab_mysql'             => 'MySQL',
'config_tab_db_sybase_subtab_note'       => 'Urus pemilihan runtime Sybase, mode operasi, dan ringkasan sambungan aktif dalam satu paparan.',
'config_tab_db_mysql_subtab_note'        => 'Paparan ini menunjukkan sambungan MySQL utama yang sentiasa aktif untuk sistem.',

'config_tab_db_sybase_nama_production'   => 'e-HRMDB (Production)',
'config_tab_db_sybase_nama_production_penerangan'
                                        => 'Pangkalan data utama sistem e-Prestasi',

'config_tab_db_sybase_nama_development'  => 'e-HRMDB (Development)',
'config_tab_db_sybase_nama_development_penerangan'
                                        => 'Pangkalan data pembangunan',

'config_tab_db_mysql'                    => 'MySQL (Sentiasa Aktif)',
'config_tab_db_mysql_sub'                => 'Sambungan sentiasa aktif',
'config_tab_db_mysql_header'             => 'Sambungan ini sentiasa aktif untuk sistem utama.',
'config_tab_db_mysql_sambungan'          => 'Medan',
'config_tab_db_mysql_keterangan'         => 'Maklumat',
'config_tab_db_mysql_host'               => 'Hos',
'config_tab_db_mysql_driver'             => 'Driver',
'config_tab_db_mysql_database'           => 'Pangkalan Data',
'config_tab_db_mysql_user'               => 'Pengguna',
'config_tab_db_mysql_status'             => 'Status',

'config_tab_db_simpan_tetapan_db'        => 'Simpan Tetapan Pangkalan Data',
'config_tab_db_actions_note'             => 'Pastikan pilihan environment dan mode operasi diuji serta disahkan sebelum disimpan.',
'config_db_validation_title'             => 'Ralat Validasi',
'config_db_validation_required'          => 'Sila lengkapkan pilihan konfigurasi pangkalan data.',
'config_db_validation_invalid'           => 'Pilihan konfigurasi pangkalan data tidak sah.',
'config_db_success_title'                => 'Berjaya',
'config_db_success_text_summary'         => 'Tetapan pangkalan data berjaya disimpan. Environment: %s. Mode: %s.',
'config_db_connection_error_title'       => 'Ralat Sambungan Database',
'config_db_connection_error_text'        => 'Sambungan ke pangkalan data gagal. Sila semak konfigurasi sambungan database atau hubungi pentadbir sistem.',
'config_db_runtime_error_text'           => 'Ralat berlaku semasa mengaktifkan pangkalan data.',
'config_db_system_error_title'           => 'Ralat Sistem',
'config_db_system_error_text'            => 'Ralat berlaku semasa mengaktifkan pangkalan data. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_db_audit_message'                => 'Tetapan pangkalan data dikemas kini: %s',
'config_db_audit_summary'                => 'Staff DB: %s -> %s | Environment: %s -> %s | Mode: %s -> %s',

/* =========================
 * TAB TEMA
 * ========================= */
'config_tab_tema_komponen'               => 'Komponen',
'config_tab_tema_pilihan'                => 'Pilihan Tema',

// Layout
'config_tab_tema_komponen_layout'        => 'Mod Susun Atur (Layout)',
'config_tab_tema_komponen_layout_sub'    => 'Mod susun atur',
'config_tab_tema_pilihan_layout_terang'  => 'Warna Terang',
'config_tab_tema_pilihan_layout_gelap'   => 'Warna Gelap',
'config_tab_tema_note_layout'            => 'Pilih mod susun atur utama untuk pengalaman penggunaan sistem.',
'config_tab_tema_desc_layout_light'      => 'Mod terang yang lebih bersih dan neutral',
'config_tab_tema_desc_layout_dark'       => 'Sesuai untuk malam',
'config_tab_tema_penerangan_layout_terang_penerangan'
                                        => 'Rekaan cerah sepenuhnya — standard mod terang.',
'config_tab_tema_penerangan_layout_gelap_penerangan'
                                        => 'Susun atur gelap — sesuai untuk malam.',

// Topbar
'config_tab_tema_komponen_topbar'        => 'Warna Topbar',
'config_tab_tema_komponen_topbar_sub'    => 'Warna topbar',
'config_tab_tema_pilihan_topbar_terang'  => 'Warna Terang',
'config_tab_tema_pilihan_topbar_gelap'   => 'Warna Gelap',
'config_tab_tema_pilihan_layout_brand'   => 'Warna Brand',
'config_tab_tema_pilihan_layout_emerald' => 'Emerald',
'config_tab_tema_pilihan_layout_navy'    => 'Navy',
'config_tab_tema_pilihan_layout_sunset'  => 'Sunset',
'config_tab_tema_pilihan_layout_mist'    => 'Mist',
'config_tab_tema_pilihan_layout_strawberry' => 'Strawberry Pink',
'config_tab_tema_pilihan_layout_matcha' => 'Matcha',
'config_tab_tema_note_topbar'            => 'Padankan warna topbar dengan mod dan identiti visual sistem.',
'config_tab_tema_desc_topbar_light'      => 'Sesuai mod terang',
'config_tab_tema_desc_topbar_dark'       => 'Sesuai mod gelap',
'config_tab_tema_desc_topbar_brand'      => 'Warna rasmi sistem',
'config_tab_tema_desc_topbar_emerald'    => 'Hijau moden yang segar dan profesional',
'config_tab_tema_desc_topbar_navy'       => 'Biru korporat gelap yang formal dan stabil',
'config_tab_tema_desc_topbar_sunset'     => 'Jingga hangat dengan karakter yang lebih menonjol',
'config_tab_tema_desc_topbar_mist'       => 'Gradient lembut berais untuk paparan terang yang lebih kemas dan premium',
'config_tab_tema_desc_topbar_strawberry' => 'Pink strawberi lembut dengan karakter mesra dan moden',
'config_tab_tema_desc_topbar_matcha' => 'Hijau matcha lembut yang tenang, segar, dan premium',
'config_tab_tema_penerangan_topbar_terang_penerangan'
                                        => 'Topbar cerah, sesuai untuk mod terang.',
'config_tab_tema_penerangan_topbar_gelap_penerangan'
                                        => 'Topbar gelap, sesuai untuk waktu malam atau mod gelap.',
'config_tab_tema_penerangan_topbar_brand_penerangan'
                                        => 'Topbar ikut warna tema rasmi sistem.',

// Sidebar
'config_tab_tema_komponen_sidebar'       => 'Warna Sidebar',
'config_tab_tema_komponen_sidebar_sub'   => 'Warna sidebar',
'config_tab_tema_pilihan_sidebar_terang' => 'Warna Terang',
'config_tab_tema_pilihan_sidebar_gelap'  => 'Warna Gelap',
'config_tab_tema_pilihan_sidebar_brand'  => 'Warna Brand',
'config_tab_tema_pilihan_sidebar_emerald' => 'Emerald',
'config_tab_tema_pilihan_sidebar_navy'    => 'Navy',
'config_tab_tema_pilihan_sidebar_sunset'  => 'Sunset',
'config_tab_tema_pilihan_sidebar_mist'    => 'Mist',
'config_tab_tema_pilihan_sidebar_strawberry' => 'Strawberry Pink',
'config_tab_tema_pilihan_sidebar_matcha' => 'Matcha',
'config_tab_tema_note_sidebar'           => 'Pilih warna sidebar yang paling jelas dan selesa untuk navigasi harian.',
'config_tab_tema_desc_sidebar_light'     => 'Latar putih bersih',
'config_tab_tema_desc_sidebar_dark'      => 'Selesa untuk mata',
'config_tab_tema_desc_sidebar_brand'     => 'Warna jenama utama',
'config_tab_tema_desc_sidebar_emerald'   => 'Hijau moden yang kemas dan profesional',
'config_tab_tema_desc_sidebar_navy'      => 'Biru gelap korporat untuk navigasi formal',
'config_tab_tema_desc_sidebar_sunset'    => 'Tone hangat yang lebih berani dan menonjol',
'config_tab_tema_desc_sidebar_mist'      => 'Gradient mist lembut dengan rasa navigasi yang bersih dan premium',
'config_tab_tema_desc_sidebar_strawberry' => 'Pink ros strawberi yang lebih kaya untuk navigasi yang lembut tetapi menyerlah',
'config_tab_tema_desc_sidebar_matcha' => 'Hijau matcha yang lembut dan matang untuk navigasi yang tenang',
'theme_emerald' => 'Emerald',
'theme_navy' => 'Navy',
'theme_sunset' => 'Sunset',
'theme_mist' => 'Mist',
'theme_strawberry' => 'Strawberry Pink',
'theme_matcha' => 'Matcha',
'config_tab_tema_penerangan_sidebar_terang_penerangan'
                                        => 'Sidebar cerah dengan latar putih bersih.',
'config_tab_tema_penerangan_sidebar_gelap_penerangan'
                                        => 'Sidebar gelap, selesa untuk mata dalam mod malam.',
'config_tab_tema_penerangan_sidebar_brand_penerangan'
                                        => 'Sidebar guna warna jenama utama sistem.',

'config_tab_db_simpan_tetapan_tema'      => 'Simpan Tetapan Tema',
'config_tab_tema_actions_note'           => 'Simpan hanya selepas kombinasi layout, topbar, dan sidebar benar-benar sesuai.',
'config_theme_validation_title'          => 'Ralat Validasi',
'config_theme_validation_invalid'        => '%s tidak sah. Hanya %s dibenarkan.',

// =========================
// Email Template
// =========================
'emailTemplate_page_title' => 'Template Emel',
'emailTemplate_list_title' => 'Senarai Template Emel',
'emailTemplate_list_subtitle' => 'Urus template emel yang boleh digunakan semula mengikut peranan, kategori, dan flow sistem.',
'emailTemplate_error_load_records' => 'Gagal memuat senarai template emel.',
'emailTemplate_status_active' => 'Aktif',
'emailTemplate_status_draft' => 'Draf',
'emailTemplate_status_archived' => 'Arkib',
'emailTemplate_role_staff' => 'Staf',
'emailTemplate_role_student' => 'Pelajar',
'emailTemplate_role_public' => 'Umum',
'emailTemplate_role_admin' => 'Pentadbir',
'emailTemplate_category_welcome' => 'Aluan',
'emailTemplate_category_notification' => 'Notifikasi',
'emailTemplate_category_reminder' => 'Peringatan',
'emailTemplate_category_approval' => 'Kelulusan',
'emailTemplate_category_rejection' => 'Penolakan',
'emailTemplate_category_security' => 'Keselamatan',
'emailTemplate_category_custom' => 'Custom',
'emailTemplate_placeholder_group_recipient' => 'Penerima',
'emailTemplate_placeholder_group_organization' => 'Organisasi',
'emailTemplate_placeholder_group_system' => 'Sistem',
'emailTemplate_placeholder_group_sender' => 'Penghantar',
'emailTemplate_placeholder_group_date' => 'Tarikh',
'emailTemplate_summary_total' => 'Jumlah Template',
'emailTemplate_summary_active' => 'Aktif',
'emailTemplate_summary_draft' => 'Draf',
'emailTemplate_summary_archived' => 'Arkib',
'emailTemplate_hero_kicker' => 'Email Template Workspace',
'emailTemplate_hero_title' => 'Satu modul umum untuk urus template emel, preview render, dan serah terus kepada developer.',
'emailTemplate_hero_subtitle' => 'Gunakan seed template berasaskan use case sebenar sistem, kemudian laras placeholder dan kandungan ikut flow modul anda.',
'emailTemplate_action_create' => 'Tambah Template',
'emailTemplate_action_filter' => 'Tapis',
'emailTemplate_btn_seed_templates' => 'Import Seed Templates',
'emailTemplate_filter_role' => 'Peranan',
'emailTemplate_filter_category' => 'Kategori',
'emailTemplate_filter_status' => 'Status',
'emailTemplate_filter_search' => 'Carian',
'emailTemplate_filter_all_roles' => 'Semua Peranan',
'emailTemplate_filter_all_categories' => 'Semua Kategori',
'emailTemplate_filter_all_statuses' => 'Semua Status',
'emailTemplate_filter_search_placeholder' => 'Cari nama, kod, atau subjek',
'emailTemplate_col_template' => 'Template',
'emailTemplate_col_role' => 'Peranan',
'emailTemplate_col_category' => 'Kategori',
'emailTemplate_col_subject' => 'Subjek',
'emailTemplate_col_status' => 'Status',
'emailTemplate_col_updated' => 'Kemaskini',
'emailTemplate_col_actions' => 'Tindakan',
'emailTemplate_badge_default' => 'Default',
'emailTemplate_badge_default_active' => 'Default Aktif',
'emailTemplate_default_note' => 'Tetapkan default lain dahulu sebelum arkib.',
'emailTemplate_usage_label' => 'Usage',
'emailTemplate_inline_general_placeholders' => 'General placeholders',
'emailTemplate_inline_seed_templates' => 'Seed templates',
'emailTemplate_btn_edit' => 'Edit',
'emailTemplate_btn_duplicate' => 'Duplicate',
'emailTemplate_btn_archive' => 'Arkib',
'emailTemplate_btn_close' => 'Tutup',
'emailTemplate_btn_save' => 'Simpan Template',
'emailTemplate_btn_update' => 'Kemaskini Template',
'emailTemplate_modal_create_title' => 'Tambah Template Emel',
'emailTemplate_modal_edit_title' => 'Kemaskini Template Emel',
'emailTemplate_modal_subtitle' => 'Sediakan maklumat utama template, kandungan emel, placeholder umum, serta preview render sebelum digunakan oleh developer.',
'emailTemplate_field_template_name' => 'Nama Template',
'emailTemplate_field_template_code' => 'Kod Template',
'emailTemplate_field_role' => 'Peranan',
'emailTemplate_field_category' => 'Kategori',
'emailTemplate_field_status' => 'Status',
'emailTemplate_field_description' => 'Penerangan Ringkas',
'emailTemplate_field_description_placeholder' => 'Ringkaskan tujuan template ini',
'emailTemplate_field_subject' => 'Subjek Emel',
'emailTemplate_field_body_html' => 'Kandungan HTML',
'emailTemplate_field_body_text' => 'Kandungan Text',
'emailTemplate_field_notes' => 'Nota Dalaman',
'emailTemplate_field_is_default' => 'Template default bagi role dan kategori ini',
'emailTemplate_select_role' => 'Pilih peranan',
'emailTemplate_select_category' => 'Pilih kategori',
'emailTemplate_hint_body_html' => 'Gunakan HTML biasa di sini. Semak hasil akhir melalui tab Preview & Test.',
'emailTemplate_error_archive_default_blocked' => 'Template default tidak boleh diarkibkan selagi belum ada template lain dijadikan default bagi peranan dan kategori yang sama.',
'emailTemplate_error_duplicate_failed' => 'Salinan template tidak berjaya dijana. Sila cuba semula.',
'emailTemplate_archive_default_tooltip' => 'Tetapkan template lain sebagai default sebelum arkib template ini.',
'emailTemplate_placeholder_title' => 'Placeholder Umum',
'emailTemplate_placeholder_subtitle' => 'Klik placeholder untuk masukkan terus ke field yang sedang aktif.',
'emailTemplate_guideline_title' => 'Panduan Sistem',
'emailTemplate_guideline_1' => 'Kod template digunakan sebagai rujukan tetap oleh modul dan integrasi sistem.',
'emailTemplate_guideline_2' => 'Template default menyokong flow penghantaran emel yang memilih role dan kategori sahaja.',
'emailTemplate_guideline_3' => 'Placeholder khusus page disediakan melalui integrasi pada modul atau page yang berkaitan.',
'emailTemplate_preview_title' => 'Preview & Test Send',
'emailTemplate_preview_subtitle' => 'Gunakan sample JSON untuk menguji placeholder, semak output akhir, dan hantar emel ujian sebelum publish.',
'emailTemplate_field_sample_variables' => 'Sample Variables JSON',
'emailTemplate_field_test_email' => 'Emel Ujian',
'emailTemplate_btn_preview' => 'Preview Render',
'emailTemplate_btn_test_send' => 'Hantar Emel Ujian',
'emailTemplate_preview_subject_title' => 'Hasil Preview',
'emailTemplate_preview_subject_subtitle' => 'Subjek, status placeholder, dan text output akan dipaparkan di sini.',
'emailTemplate_preview_used_placeholders' => 'Placeholder Digunakan',
'emailTemplate_preview_missing_placeholders' => 'Placeholder Tiada Nilai',
'emailTemplate_preview_invalid_placeholders' => 'Placeholder Tidak Sah',
'emailTemplate_preview_text_output' => 'Text Output',
'emailTemplate_preview_html_title' => 'HTML Preview',
'emailTemplate_preview_html_subtitle' => 'Paparan akhir email selepas dibungkus dengan layout standard sistem.',
'emailTemplate_preview_empty_subject' => 'Belum dijana',
'emailTemplate_preview_empty_text' => 'Klik Preview Render untuk melihat output text template.',
'emailTemplate_preview_success' => 'Preview template berjaya dijana.',
'emailTemplate_preview_success_title' => 'Preview Berjaya',
'emailTemplate_preview_failed_title' => 'Preview Gagal',
'emailTemplate_error_preview_required' => 'Subjek dan kandungan HTML diperlukan untuk preview.',
'emailTemplate_error_preview_failed' => 'Ralat sistem semasa menjana preview template emel.',
'emailTemplate_error_sample_json_invalid' => 'Sample variables mesti dalam format JSON yang sah.',
'emailTemplate_test_send_success' => 'Emel ujian berjaya dihantar.',
'emailTemplate_test_send_success_title' => 'Emel Ujian Berjaya',
'emailTemplate_test_send_failed_title' => 'Emel Ujian Gagal',
'emailTemplate_error_test_email_invalid' => 'Alamat emel ujian tidak sah.',
'emailTemplate_error_test_send_failed' => 'Emel ujian tidak berjaya dihantar.',
'emailTemplate_network_error' => 'Ralat rangkaian semasa memproses permintaan.',
'emailTemplate_error_invalid_csrf' => 'Sesi anda telah tamat. Sila muat semula halaman dan cuba lagi.',
'emailTemplate_error_validation' => 'Sila semak semula maklumat template emel yang diisi.',
'emailTemplate_error_template_code_required' => 'Kod template adalah wajib.',
'emailTemplate_error_template_code_format' => 'Kod template hanya boleh mengandungi huruf besar, nombor, dash, dan underscore.',
'emailTemplate_error_template_code_exists' => 'Kod template sudah digunakan.',
'emailTemplate_error_template_name_required' => 'Nama template adalah wajib.',
'emailTemplate_error_role_required' => 'Peranan penerima adalah wajib.',
'emailTemplate_error_category_required' => 'Kategori emel adalah wajib.',
'emailTemplate_error_subject_required' => 'Subjek template adalah wajib.',
'emailTemplate_error_body_html_required' => 'Kandungan emel adalah wajib.',
'emailTemplate_error_status_required' => 'Status template tidak sah.',
'emailTemplate_save_success_create' => 'Template emel berjaya dicipta.',
'emailTemplate_save_success_update' => 'Template emel berjaya dikemaskini.',
'emailTemplate_duplicate_success' => 'Salinan template emel berjaya dicipta.',
'emailTemplate_archive_success' => 'Template emel berjaya diarkibkan.',
'emailTemplate_seed_success' => 'Seed template berjaya diimport.',
'emailTemplate_archive_confirm' => 'Arkibkan template ini?',
'emailTemplate_error_template_not_found' => 'Template emel tidak ditemui.',
'emailTemplate_save_fail' => 'Template emel tidak berjaya disimpan.',
'emailTemplate_empty_title' => 'Tiada template emel lagi',
'emailTemplate_empty_subtitle' => 'Mulakan dengan import seed template atau cipta template baharu secara manual.',
'config_theme_success_text_summary'      => 'Tetapan tema berjaya dikemas kini. Perubahan: %s.',
'config_theme_save_error_title'          => 'Ralat Menyimpan',
'config_theme_save_error_text'           => 'Gagal menyimpan tetapan tema. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_theme_system_error_title'        => 'Ralat Sistem',
'config_theme_system_error_text'         => 'Ralat berlaku semasa menyimpan tetapan tema. Sila semak log sistem untuk maklumat lanjut.',
'config_theme_audit_message'             => 'Tetapan tema dikemas kini (%d medan): %s',
'config_theme_audit_no_changes'          => 'tiada perubahan medan',

/* =========================
 * TAB BAHASA
 * ========================= */
'config_tab_bahasa_header'               => 'Bahasa yang Tersedia',
'config_tab_bahasa_header_sub'           => 'Bahasa tersedia',
'config_tab_bahasa_header_details'       => 'Tandakan bahasa yang ingin diaktifkan untuk digunakan dalam sistem.',
'config_tab_bahasa_default'              => 'Bahasa Lalai',
'config_tab_bahasa_kodBahasa'            => 'Kod Bahasa',
'config_tab_bahasa_peneranganBahasa'     => 'Penerangan Bahasa',
'config_tab_bahasa_status_aktif'         => 'Aktif',
'config_tab_bahasa_simpan_tetapan_bahasa'=> 'Simpan Tetapan Bahasa',
'config_tab_bahasa_actions_note'         => 'Pastikan sekurang-kurangnya satu bahasa kekal aktif dan satu bahasa lalai dipilih.',
'config_language_validation_title'       => 'Ralat Validasi',
'config_language_validation_required'    => 'Sila pilih sekurang-kurangnya satu bahasa untuk diaktifkan.',
'config_language_validation_invalid'     => 'Bahasa "%s" tidak sah. Hanya %s dibenarkan.',
'config_language_validation_default_required' => 'Sila pilih satu bahasa lalai untuk sistem.',
'config_language_validation_default_invalid' => 'Bahasa lalai "%s" tidak sah.',
'config_language_validation_default_not_active' => 'Bahasa lalai mesti berada dalam senarai bahasa aktif.',
'config_language_success_text_summary'   => 'Tetapan bahasa berjaya disimpan. Aktif: %s. Lalai: %s.',
'config_language_save_error_title'       => 'Ralat Menyimpan',
'config_language_save_error_text'        => 'Gagal menyimpan tetapan bahasa. Sila cuba lagi atau hubungi pentadbir sistem.',
'config_language_system_error_title'     => 'Ralat Sistem',
'config_language_system_error_text'      => 'Ralat berlaku semasa menyimpan tetapan bahasa. Sila semak log sistem untuk maklumat lanjut.',
'config_language_audit_message'          => 'Tetapan bahasa dikemas kini: %s',
'config_language_audit_message_summary'  => 'Aktif: %s | Lalai: %s',

/* =========================
 * JS / SWEETALERT
 * ========================= */
'config_js_loading'              => 'Memuat…',
'config_js_memproses'            => 'Memproses…',

'config_js_confirm_emel'         => 'Anda pasti mahu simpan tetapan emel?',
'config_js_confirm_general'      => 'Anda pasti mahu simpan tetapan umum?',
'config_js_confirm_auth'         => 'Anda pasti mahu simpan polisi login ini?',
'config_js_confirm_db'           => 'Anda pasti mahu simpan tetapan pangkalan data?',
'config_js_confirm_tema'         => 'Anda pasti mahu simpan tetapan tema lalai?',
'config_js_confirm_bahasa'       => 'Anda pasti mahu simpan senarai bahasa aktif?',

'config_js_btn_ya_simpan'        => 'Ya, simpan',
'config_js_btn_ya_teruskan'      => 'Ya, teruskan',
'config_js_btn_ok'               => 'OK',
'config_js_btn_cancel'           => 'Batal',
'config_js_btn_loading_save'     => 'Menyimpan...',

// Uji Emel
'config_js_confirm_uji_emel'     => 'Anda pasti mahu uji sambungan emel ini?',
'config_js_input_uji_emel'       => 'Masukkan Emel Ujian',
'config_js_label_uji_emel'       => 'Alamat emel untuk uji penghantaran',
'config_js_placeholder_uji_emel' => 'cth: apps_email@upnm.edu.my',
'config_js_valid_emel_kosong'    => 'Alamat emel tidak boleh kosong',
'config_js_valid_email_format'   => 'Format emel tidak sah',
'config_js_valid_email_full'     => 'Format emel tidak sah. Sila masukkan emel yang betul.',
'config_js_valid_host_format'    => 'Format host tidak sah (domain atau IP)',
'config_js_valid_port_range'     => 'Port mesti antara 1 hingga 65535',
'config_js_system_error_title'   => 'Ralat Sistem',
'config_js_module_not_ready'     => 'Modul tetapan sistem belum siap dimuatkan. Sila cuba semula.',
'config_js_invalid_server_response' => 'Respons pelayan tidak sah.',
'config_js_save_failed'          => 'Gagal menyimpan tetapan.',
'config_js_save_success_default' => 'Tetapan berjaya disimpan.',
'config_js_save_system_error'    => 'Ralat sistem semasa menyimpan tetapan.',
'config_js_invalid_input'        => 'Input tidak sah.',
'config_js_field_fallback_label' => 'Ruangan',
'config_js_uji_emel_btn'         => 'Uji Sekarang',
'config_js_uji_emel_btn_loading' => 'Menguji…',
'config_js_uji_emel_btn_default' => 'Uji Sambungan Emel',

// Status JS
'config_js_berjaya'              => 'Berjaya',
'config_js_ralat'                => 'Ralat',
'config_js_emel_berjaya'         => '✅ Emel berjaya dihantar.',
'config_js_emel_uji_berjaya'     => 'Emel ujian berjaya dihantar ke :email.',
'config_js_emel_gagal'           => '❌ Gagal hantar emel.',
'config_js_emel_uji_gagal'       => '❌ Gagal hantar emel: :error',
'config_js_ralat_sistem'         => '❌ Ralat sistem semasa menguji sambungan.',
'config_js_tiada_bahasa'         => 'Tiada Bahasa Dipilih',
'config_js_pilih_bahasa'         => 'Sila pilih sekurang-kurangnya satu bahasa.',
'config_js_tiada_bahasa_default' => 'Tiada Bahasa Lalai',
'config_js_pilih_bahasa_default' => 'Sila pilih satu bahasa lalai daripada senarai bahasa aktif.',

/* =========================
 * ALERT DB (Controller)
 * ========================= */
'config_db_sambungan_tidak_sah'   => 'Sambungan Tidak Sah',
'config_db_pilihan_tidak_wujud'   => 'Pilihan sambungan tidak wujud.',
'config_db_sambungan_gagal'       => 'Sambungan Gagal',
'config_db_sambungan_gagal_msg'   => 'Tidak dapat menyambung ke pangkalan data ":db".',
'config_db_sambungan_ok'          => 'Sambungan Berjaya',
'config_db_sambungan_ok_msg'      => 'Sambungan berjaya dikemaskini ":db".',
'config_db_ralat_simpan'          => 'Ralat Simpanan',
'config_db_ralat_simpan_msg'      => 'Gagal menyimpan tetapan ke dalam fail.',

'config_alert_title'              => 'Anda pasti?',
'config_alert_no'                 => 'Batal',


/* =====================================================
 * UI GLOBAL (theme_, topbar_, sidebar_, footer_, logout_)
 * ===================================================== */

/* =========================
 * TEMA (Offcanvas / Global)
 * ========================= */
'theme_title'                 => 'Tetapan Tema',
'theme_close'                 => 'Tutup',
'theme_customize'             => 'Sesuaikan',
'theme_customize_sub'         => 'Tetapan warna, menu, dan lain-lain',

'theme_color_scheme'          => 'Skema Warna',
'theme_topbar_color'          => 'Warna Topbar',
'theme_menu_color'            => 'Warna Sidebar/Menu',
'theme_light'                 => 'Warna Terang',
'theme_dark'                  => 'Warna Gelap',
'theme_brand'                 => 'Warna Brand',

'theme_note_preview'          => 'Perubahan di sini adalah pratayang. Untuk simpan kekal, guna halaman Tetapan Sistem.',
'theme_note_preview_fallback' => 'Perubahan di sini adalah pratayang. Untuk simpan kekal, guna halaman Tetapan Sistem.',
'theme_applied'               => 'Tema Diterapkan',
'theme_'                      => 'Tema',

/* =========================
 * TOPBAR
 * ========================= */
'topbar_welcome'              => 'Selamat Datang!',
'topbar_keluar'               => 'Log Keluar',

// Profil & menu
'topbar_switch_role'          => 'Tukar Peranan',
'topbar_switch_role_title'    => 'Tukar Peranan',
'topbar_switch_role_select'   => 'Pilih Peranan',
'topbar_switch_role_primary_label' => 'Peranan utama',
'topbar_switch_role_primary_tag'   => 'Peranan Utama',
'topbar_switch_role_none'     => 'Tiada peranan lain yang dibenarkan.',
'topbar_switch_role_err_select' => 'Sila pilih peranan.',
'topbar_switch_role_err_invalid' => 'Sila pilih peranan yang sah.',
'topbar_switch_role_saving'   => 'Menyimpan...',
'topbar_switch_role_success_title' => 'Peranan {role}',
'topbar_switch_role_success_text'  => 'Paparan dan akses sistem telah dikemas kini mengikut pilihan peranan baru iaitu <strong>{role}</strong>.',

/* =========================
 * SIDEBAR
 * ========================= */
'sidebar_main'                => 'Utama',
'sidebar_dashboard'           => 'Papan Pemuka',
'sidebar_dashboard_stats'     => 'Statistik',
'sidebar_user_manual'         => 'Manual Pengguna',
'sidebar_modul'               => 'Modul Sistem',
'sidebar_kawalan'             => 'Kawalan Sistem',
'sidebar_keluar'              => 'Log Keluar',

'sidebar_profile_empty'       => 'Profil tidak ditemui',
'sidebar_loading'             => 'Memuatkan...',

/* =========================
 * FOOTER
 * ========================= */
'footer_it'                   => 'BTMK | Seksyen Aplikasi Digital',
'footer_about'                => 'Tentang Kami',
'footer_help'                 => 'Bantuan',
'footer_contact'              => 'Hubungi Kami',

'footer_content_updating_title'
                              => 'Maklumat',
'footer_content_updating'     => 'Kandungan sedang dikemaskini.',
'footer_content_updating_ok'  => 'OK',

/* =========================
 * LOGOUT (SweetAlert)
 * ========================= */
'logout_alert_title'          => 'Pengesahan',
'logout_alert_text'           => 'Anda pasti mahu log keluar?',
'logout_alert_yes'            => 'Ya, log keluar',
'logout_alert_no'             => 'Batal',

'logout_title'                => 'Log Keluar Berjaya',
'logout_msg'                  => 'Anda telah log keluar daripada sistem.',


/* =====================================================
 * KUMPULAN PENGGUNA (userGroup_)
 * ===================================================== */

/* =========================
 * Butang & Aksi
 * ========================= */
'userGroup_edit' => 'Sunting',
'userGroup_delete'                  => 'Padam',


/* =========================
 * Label Kecil
 * ========================= */

/* =========================
 * Status
 * ========================= */

/* =========================
 * Modal — Menu
 * ========================= */





/* =========================
 * Modal — Akses Kumpulan
 * ========================= */

/* =========================
 * Undo (Padam Menu)
 * ========================= */
'userGroup_undo_info'
                                    => 'Fungsi batal memerlukan endpoint server-side. Sila hubungi admin.',

/* =========================
 * SweetAlert — Padam
 * ========================= */


/* =========================
 * Ralat
 * ========================= */


'userGroup_bootstrap_missing'
                                    => 'Bootstrap JS tidak dimuat. Pastikan bootstrap.bundle.min.js dimasukkan.',

/* =========================
 * DataTables
 * ========================= */
'userGroup_dt_info'
                                    => 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ rekod',
'userGroup_dt_info_filtered'
                                    => '(ditapis daripada _MAX_ jumlah rekod)',



/* =====================================================
 * KUNCI PEMANTAUAN & SISTEM
 * ===================================================== */

/* =====================================================
 * KUNCI UMUM / PELBAGAI
 * ===================================================== */
'actions' => 'Tindakan',
'btn_save' => 'Simpan',
'type' => 'Jenis',
'ujian_db' => 'Ujian Pangkalan Data',

/* =====================================================
 * KUNCI PENGURUSAN PROJEK
 * ===================================================== */

/* =====================================================
 * KUNCI BUTANG/TINDAKAN UMUM
 * ===================================================== */
'btn_update' => 'Kemaskini',
'btn_close' => 'Tutup',
'updating' => 'Mengemas kini',

/* =====================================================
 * DASHBOARD (ASAS)
 * ===================================================== */
'dashboard_title' => 'Papan Pemuka',
'dashboard_breadcrumb' => 'Papan Pemuka',
'dashboard_welcome' => 'Selamat datang',
'dashboard_last_login' => 'Log masuk terakhir',
'dashboard_resources_title' => 'Sumber Sistem',
'dashboard_refresh' => 'Muat semula',
'dashboard_resources_col_resource' => 'Sumber',
'dashboard_resources_col_usage' => 'Penggunaan',
'dashboard_resources_col_status' => 'Status',
'dashboard_status_ok' => 'OK',
'dashboard_status_warning' => 'Amaran',
'dashboard_status_critical' => 'Kritikal',
'dashboard_status_unknown' => 'Tidak diketahui',
'dashboard_status_degraded' => 'Menurun',
'dashboard_resource_cpu' => 'CPU',
'dashboard_resource_memory' => 'Memori',
'dashboard_resource_disk' => 'Cakera',
'dashboard_health_db' => 'Pangkalan Data',
'dashboard_health_connected' => 'Bersambung',
'dashboard_health_conn_failed' => 'Sambungan gagal',
'dashboard_health_app' => 'Aplikasi',
'dashboard_health_bootstrap_ok' => 'Bootstrap dimuat',
'dashboard_health_config_incomplete' => 'Konfigurasi tidak lengkap',
'dashboard_health_storage' => 'Storan',
'dashboard_health_storage_free' => '%s%% ruang kosong',
'dashboard_health_unavailable' => 'Tidak tersedia',
'dashboard_health_cache' => 'Cache',
'dashboard_health_enabled' => 'Diaktifkan',
'dashboard_health_readonly' => 'Baca sahaja',
'dashboard_health_disabled' => 'Dinonaktifkan',
'dashboard_env_production' => 'produksi',
'dashboard_env_development' => 'pembangunan',
'dashboard_env_debug_on' => 'debug ON',
'dashboard_env_debug_off' => 'debug OFF',
'dashboard_health_audit' => 'Audit/Log',
'dashboard_health_writable' => 'Boleh ditulis',
'dashboard_health_not_writable' => 'Tidak boleh ditulis',
'dashboard_health_cron' => 'Kerja Berjadual',
'dashboard_health_unknown' => 'Tidak diketahui',
'dashboard_health_tz' => 'Masa & Zon Masa',

/* =====================================================
 * FAQ (SOALAN LAZIM)
 * ===================================================== */
'faq_title' => 'Soalan Lazim (FAQ)',
'faq_heading' => 'Soalan Lazim Sistem',
'faq_intro' => 'Rujuk panduan umum penggunaan sistem untuk semua pengguna. Pilih kategori atau gunakan carian untuk mendapatkan jawapan yang lebih sesuai dengan perkara yang anda ingin tahu.',
'faq_label_category' => 'Kategori',
'faq_placeholder_cari' => 'Cari dalam kategori terpilih…',
'faq_tiada_padamu' => 'Tiada padanan ditemui. Cuba kata kunci lain.',
'faq_count_display' => 'daripada',
'faq_count_soalan' => 'soalan dipaparkan',
'faq_cat_semua' => 'Semua',
'faq_cat_account_access' => 'Akaun & Akses',
'faq_cat_navigation' => 'Navigasi & Penggunaan',
'faq_cat_profile_settings' => 'Profil & Tetapan',
'faq_cat_user_management' => 'Data & Rekod',
'faq_cat_group_management' => 'Keselamatan & Privasi',
'faq_cat_support' => 'Sokongan & Bantuan',

'faq_item_01_q' => 'Bagaimana cara log masuk ke sistem dengan betul?',
'faq_item_01_a' => '<p>Gunakan ID pengguna yang diberikan kepada anda bersama kata laluan yang sah pada halaman log masuk. Pastikan anda menaip maklumat tersebut dengan tepat, termasuk huruf besar atau kecil jika sistem membezakannya.</p><p>Selepas berjaya log masuk, anda akan dibawa ke halaman utama sistem. Jika anda menggunakan komputer awam atau berkongsi peranti dengan orang lain, sentiasa pastikan anda log keluar selepas selesai menggunakan sistem untuk melindungi akaun anda.</p>',
'faq_item_02_q' => 'Kenapa saya tidak dapat log masuk walaupun maklumat saya betul?',
'faq_item_02_a' => '<p>Kegagalan log masuk boleh berlaku disebabkan beberapa faktor seperti kata laluan yang telah ditukar, sesi lama yang tamat, sambungan rangkaian yang tidak stabil, atau akaun yang dikunci sementara selepas terlalu banyak cubaan gagal.</p><p>Langkah pertama ialah semak semula ID pengguna dan kata laluan anda, kemudian cuba lagi selepas beberapa minit. Jika masalah berterusan, ambil tangkapan skrin mesej ralat jika ada dan hubungi pegawai atau pentadbir yang bertanggungjawab supaya semakan boleh dibuat dengan lebih cepat.</p>',
'faq_item_03_q' => 'Mengapa menu yang saya lihat mungkin berbeza antara pengguna?',
'faq_item_03_a' => '<p>Sistem biasanya memaparkan menu berdasarkan peranan, tugasan, atau akses yang telah diberikan kepada akaun pengguna. Oleh sebab itu, tidak semua pengguna akan melihat modul atau fungsi yang sama.</p><p>Perbezaan ini adalah perkara biasa dan bertujuan memastikan paparan sistem lebih fokus, teratur, dan selamat. Jika anda merasakan anda perlu melihat fungsi tertentu untuk menjalankan tugas harian, sila rujuk pihak yang mengurus akses sistem untuk pengesahan lanjut.</p>',
'faq_item_04_q' => 'Apakah fungsi halaman utama atau dashboard sistem?',
'faq_item_04_a' => '<p>Halaman utama atau dashboard biasanya menjadi ringkasan awal selepas anda log masuk. Di sini anda boleh melihat maklumat penting, pintasan ke modul yang kerap digunakan, dan gambaran umum keadaan semasa sistem.</p><p>Dashboard membantu anda memulakan kerja dengan lebih cepat tanpa perlu mencari semua fungsi secara manual. Jika terdapat widget, statistik, atau senarai ringkas, anggap ia sebagai panduan awal sebelum anda masuk ke modul yang lebih terperinci.</p>',
'faq_item_05_q' => 'Bagaimana cara terbaik menggunakan menu sidebar?',
'faq_item_05_a' => '<p>Menu sidebar berfungsi sebagai navigasi utama untuk berpindah antara modul dan halaman. Gunakan sidebar untuk mengenal pasti kategori fungsi, kemudian klik menu yang paling hampir dengan tugasan yang anda mahu lakukan.</p><p>Jika sesuatu menu mempunyai sub-menu, buka dahulu kategori tersebut dan semak pilihan yang tersedia. Cara terbaik menggunakan sidebar ialah dengan membiasakan diri dengan susunan menu utama supaya anda boleh bergerak dalam sistem dengan lebih cepat dan konsisten.</p>',
'faq_item_06_q' => 'Bagaimana saya boleh mencari maklumat dengan lebih cepat dalam sistem?',
'faq_item_06_a' => '<p>Kebanyakan halaman yang memaparkan senarai data menyediakan kemudahan carian, penapisan, atau susunan jadual. Gunakan ruang carian untuk mengecilkan senarai rekod dan gunakan penapis jika anda ingin fokus kepada kategori, tarikh, atau status tertentu.</p><p>Selain itu, perhatikan juga nama modul dan tajuk halaman supaya anda tahu konteks data yang sedang dipaparkan. Ini akan membantu anda mengelakkan kesilapan memilih halaman atau membuat carian pada tempat yang tidak berkaitan.</p>',
'faq_item_07_q' => 'Bagaimana saya menukar bahasa paparan sistem?',
'faq_item_07_a' => '<p>Bahasa paparan biasanya boleh ditukar melalui bahagian profil atau kawalan bahasa yang disediakan pada antaramuka sistem. Setelah pilihan dibuat, sistem akan menggunakan bahasa tersebut pada halaman yang menyokong pelbagai bahasa.</p><p>Jika anda tidak nampak perubahan serta-merta, cuba muat semula halaman atau log masuk semula. Ini bergantung kepada cara modul tertentu memuatkan kandungan dan sama ada bahasa baharu digunakan terus dalam sesi semasa.</p>',
'faq_item_08_q' => 'Bagaimana saya menukar tema atau rupa paparan sistem?',
'faq_item_08_a' => '<p>Tema paparan membolehkan anda menyesuaikan pengalaman penggunaan mengikut keselesaan, contohnya mod terang, mod gelap, atau pilihan warna tertentu pada bahagian antaramuka. Tetapan ini biasanya boleh diubah pada halaman profil atau tetapan sistem yang dibenarkan.</p><p>Menukar tema tidak mengubah data atau fungsi sistem. Ia hanya menukar rupa paparan. Jika anda bekerja dalam tempoh yang lama, pilih tema yang paling selesa untuk dibaca supaya penggunaan sistem menjadi lebih mudah dan kurang meletihkan mata.</p>',
'faq_item_09_q' => 'Apakah maklumat yang biasanya dipaparkan pada halaman profil pengguna?',
'faq_item_09_a' => '<p>Halaman profil lazimnya memaparkan maklumat asas pengguna seperti nama, identiti akaun, emel, jabatan, peranan, dan beberapa tetapan peribadi seperti bahasa atau tema. Ia bertujuan membantu anda menyemak sama ada maklumat akaun anda tepat dan terkini.</p><p>Dalam sesetengah sistem, halaman profil juga memaparkan sejarah log masuk, maklumat sesi aktif, atau rekod aktiviti tertentu. Gunakan halaman ini sebagai rujukan utama untuk memahami status akaun anda sendiri di dalam sistem.</p>',
'faq_item_10_q' => 'Bagaimana saya menyemak atau mengurus data yang dipaparkan dalam jadual?',
'faq_item_10_a' => '<p>Apabila anda berada pada halaman yang memaparkan jadual, mulakan dengan membaca tajuk kolum untuk memahami jenis maklumat yang sedang dipaparkan. Selepas itu, gunakan carian, penapisan, dan pagination untuk meneliti data secara lebih teratur.</p><p>Jika sistem menyediakan butang tindakan seperti lihat, kemas kini, atau muat turun, pastikan anda faham konteks rekod yang dipilih sebelum meneruskan. Amalan ini membantu mengurangkan kesilapan dan menjadikan semakan data lebih sistematik.</p>',
'faq_item_11_q' => 'Apakah maksud status atau label yang dipaparkan pada rekod?',
'faq_item_11_a' => '<p>Status, lencana, atau label pada rekod biasanya menunjukkan keadaan semasa sesuatu data, contohnya aktif, belum lengkap, sedang diproses, berjaya, atau memerlukan tindakan lanjut. Setiap modul mungkin menggunakan istilah yang sedikit berbeza, tetapi tujuannya tetap sama iaitu memberi gambaran cepat tentang keadaan rekod tersebut.</p><p>Apabila anda melihat sesuatu status, baca juga maklumat lain pada baris yang sama seperti tarikh, pemilik rekod, atau tindakan yang tersedia. Ini membantu anda mentafsir status dengan lebih tepat sebelum membuat keputusan seterusnya.</p>',
'faq_item_12_q' => 'Apakah yang perlu saya lakukan sebelum membuat perubahan pada data?',
'faq_item_12_a' => '<p>Sebelum membuat sebarang perubahan, semak dahulu butiran data yang terlibat dan pastikan anda berada pada rekod yang betul. Baca label medan, nilai semasa, dan sebarang nota panduan yang dipaparkan pada borang atau halaman tersebut.</p><p>Jika perubahan itu penting, elok juga anda pastikan maklumat sokongan seperti dokumen rujukan, nombor rekod, atau tarikh berkaitan telah disediakan lebih awal. Cara ini membantu mengurangkan kesilapan dan mempercepatkan proses kemas kini.</p>',
'faq_item_13_q' => 'Bagaimana saya menjaga keselamatan dan privasi semasa menggunakan sistem?',
'faq_item_13_a' => '<p>Jangan berkongsi kata laluan, jangan biarkan sesi anda terbuka tanpa pengawasan, dan elakkan menyimpan maklumat sensitif pada tempat yang tidak selamat. Jika anda menggunakan komputer bersama, pastikan anda log keluar apabila selesai.</p><p>Selain itu, sentiasa berhati-hati apabila memuat turun, memuat naik, atau berkongsi data dari sistem. Pastikan tindakan anda mematuhi garis panduan organisasi, terutama jika maklumat yang diakses melibatkan data dalaman atau sulit.</p>',
'faq_item_14_q' => 'Apa yang patut saya lakukan jika sistem memaparkan ralat atau tidak berfungsi seperti biasa?',
'faq_item_14_a' => '<p>Jika berlaku ralat, jangan terus mengulangi tindakan yang sama berkali-kali. Catat mesej ralat yang dipaparkan, masa kejadian, modul yang digunakan, dan tindakan yang anda lakukan sebelum masalah berlaku.</p><p>Maklumat ini sangat membantu semasa proses semakan. Jika boleh, ambil tangkapan skrin dan laporkan kepada pihak sokongan atau pentadbir sistem. Lebih lengkap maklumat yang diberikan, lebih mudah masalah tersebut dikenal pasti dan diselesaikan.</p>',
'faq_item_15_q' => 'Di mana saya boleh mendapatkan bantuan jika masih keliru menggunakan sistem?',
'faq_item_15_a' => '<p>Jika anda masih kurang pasti tentang fungsi tertentu, rujuk dahulu panduan yang tersedia dalam sistem seperti manual pengguna, nota bantuan, atau soalan lazim ini. Kebanyakan persoalan asas boleh dijawab melalui rujukan tersebut.</p><p>Jika anda masih memerlukan bantuan, hubungi pegawai sokongan, helpdesk, atau pentadbir sistem yang ditetapkan oleh organisasi anda. Nyatakan isu dengan jelas, sertakan tangkapan skrin jika perlu, dan terangkan langkah yang anda telah cuba supaya bantuan dapat diberikan dengan lebih tepat.</p>',

'session_idle_title' => 'Masih di sini?',
    'session_idle_text' => 'Tiada aktiviti %d minit. Kekal log masuk?',
'session_idle_stay_connected' => 'Kekal Log Masuk',
'session_idle_logout_now' => 'Log Keluar',
'session_idle_timeout_text' => 'Auto log keluar dalam 1 minit.',
'session_idle_timeout_title' => 'Sesi Tamat',
'session_idle_timeout_logout_now' => 'Tiada respons. Sistem akan log keluar sekarang.',
'session_idle_keepalive_failed' => 'Sesi tidak dapat diperbaharui. Anda akan dilog keluar.',
'manual_unauthorized_access' => 'Anda tidak dibenarkan mengakses halaman ini.',
'access_notice_title' => 'Makluman Sistem',
'access_notice_text' => 'Destinasi yang diminta tidak tersedia. Sila teruskan menggunakan navigasi yang disediakan dalam sistem.',
'manual_csrf_reload' => 'CSRF token tidak sah. Sila muat semula halaman dan cuba lagi.',
'manual_page_title' => 'Urus Manual Pengguna',
'manual_breadcrumb_home' => 'Utama',
'manual_col_no' => '#',
'manual_col_group' => 'Peranan (Kumpulan)',
'manual_col_status' => 'Status Manual',
'manual_col_updated_at' => 'Kemaskini Terakhir',
'manual_col_actions' => 'Tindakan',
'manual_none' => 'Tiada',
'manual_no_groups_found' => 'Tiada senarai kumpulan pengguna ditemui.',
'manual_status_saved' => 'Disimpan',
'manual_status_not_uploaded' => 'Belum dimuat naik',
'manual_action_upload' => 'Muat naik manual',
'manual_action_view' => 'Papar manual',
'manual_action_delete' => 'Padam manual',
'manual_upload_modal_title' => 'Muat Naik Manual Pengguna',
'manual_upload_modal_intro' => 'Sila muat naik panduan pengguna untuk peranan:',
'manual_upload_modal_subtext' => 'Fail manual akan terus dikemas kini untuk kumpulan ini selepas berjaya dimuat naik.',
'manual_upload_field_label' => 'Fail PDF (Maksimum %dMB)',
'manual_upload_help_text' => 'Hanya fail PDF dibenarkan. Sistem akan semak jenis fail sebelum simpan.',
'manual_upload_replace_notice' => 'Manual sedia ada akan digantikan dengan fail baharu ini.',
'manual_btn_cancel' => 'Batal',
'manual_btn_upload_save' => 'Muat Naik & Simpan',
'manual_upload_processing_btn' => 'Sedang Simpan...',
'manual_upload_loading_title' => 'Muat Naik Manual',
'manual_upload_loading_text' => 'Sedang memuat naik fail...',
'manual_upload_success_title' => 'Manual Dikemas Kini',
'manual_upload_error_title' => 'Muat Naik Gagal',
'manual_upload_select_file' => 'Sila pilih fail PDF terlebih dahulu.',
'manual_btn_sync_groups' => 'Semak Kumpulan',
'manual_btn_close' => 'Tutup',
'manual_btn_delete' => 'Padam',
'manual_dt_length_menu' => 'Show _MENU_ records',
'manual_dt_info' => 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ rekod',
'manual_dt_info_empty' => 'Menunjukkan 0 hingga 0 daripada 0 rekod',
'manual_dt_paginate_prev' => 'Previous',
'manual_dt_paginate_next' => 'Next',
'manual_dt_zero_records' => 'Tiada padanan ditemui',
'manual_dt_search_label' => 'Search',
'manual_sync_loading_title' => 'Semak Kumpulan',
'manual_sync_loading_text' => 'Sedang semak kumpulan...',
'manual_sync_success_title' => 'Semakan Kumpulan Selesai',
'manual_sync_success_fallback' => 'Semakan kumpulan selesai.',
'manual_sync_error_title' => 'Semakan Kumpulan Gagal',
'manual_unknown_error' => 'Ralat tidak diketahui.',
'manual_group_fallback' => 'kumpulan ini',
'manual_delete_confirm_title' => 'Padam Manual?',
'manual_delete_confirm_text' => 'Anda pasti ingin memadam manual bagi {group}?',
'manual_alert_success_title' => 'Berjaya',
'manual_alert_error_title' => 'Ralat',
'manual_sync_no_groups' => 'Tiada kumpulan untuk disemak.',
'manual_sync_result' => 'Semakan kumpulan selesai. Baru: %d, Dikemas kini: %d.',
'manual_sync_failed' => 'Gagal menyemak dan menyelaras kumpulan manual.',
'manual_group_invalid' => 'Kumpulan pengguna tidak sah.',
'manual_file_incomplete' => 'Maklumat fail tidak lengkap.',
'manual_upload_error' => 'Ralat semasa memuat naik fail.',
'manual_upload_invalid' => 'Fail muat naik tidak sah.',
'manual_upload_pdf_only' => 'Sila muat naik fail berformat PDF sahaja.',
'manual_upload_invalid_pdf' => 'Fail yang dimuat naik bukan PDF yang sah.',
'manual_upload_max_size' => 'Saiz fail melebihi had %dMB.',
'manual_record_update_failed' => 'Gagal mengemas kini rekod manual.',
'manual_upload_success' => 'Manual berjaya dimuat naik dan dikemas kini.',
'manual_upload_store_failed' => 'Gagal menyimpan fail yang dimuat naik.',
'manual_delete_record_failed' => 'Gagal memadam rekod manual.',
'manual_delete_success' => 'Manual berjaya dipadam.',
'manual_not_found' => 'Manual tidak dijumpai.',
'manual_method_not_allowed' => 'Kaedah permintaan tidak dibenarkan.',
'manual_csrf_invalid' => 'CSRF token tidak sah.',
'manual_action_forbidden' => 'Anda tidak dibenarkan melakukan tindakan ini.',
'manual_server_sync_error' => 'Ralat server semasa menyelaras kumpulan manual.',
'manual_upload_failed_generic' => 'Muat naik manual gagal.',
'manual_server_upload_error' => 'Ralat server semasa memuat naik manual.',
'studentSearch_mode_disabled' => 'Mode pelajar tidak aktif. Tukar Operational Mode kepada Staf + Pelajar terlebih dahulu.',
'studentSearch_system_error' => 'Ralat sistem semasa mencari pelajar.',
'studentLookup_page_title' => 'Carian Pelajar',
'studentLookup_header_title' => 'Carian Data Pelajar',
'studentLookup_header_subtitle' => 'Semak data pelajar aktif daripada view v210 melalui domain Sybase Pelajar.',
'studentLookup_environment' => 'Persekitaran',
'studentLookup_mode' => 'Mode Operasi',
'studentLookup_runtime_key' => 'Runtime Key Pelajar',
'studentLookup_query_info_default' => 'Halaman ini digunakan untuk menyemak data pelajar aktif melalui domain Sybase Pelajar apabila mode Staf + Pelajar diaktifkan.',
'studentLookup_query_info_search' => 'Gunakan halaman ini untuk mencari data pelajar aktif berdasarkan matrik, nama, atau fakulti melalui domain Sybase Pelajar.',
'studentLookup_mode_disabled' => 'Carian pelajar hanya tersedia apabila mode Staf + Pelajar diaktifkan.',
'studentLookup_error_prefix' => 'Ralat carian pelajar:',
'studentLookup_success_search' => 'Carian berjaya. %1$d rekod ditemui untuk kata kunci "%2$s".',
'studentLookup_col_matrik' => 'Matrik',
'studentLookup_col_nama' => 'Nama',
'studentLookup_col_fakulti' => 'Fakulti',
'studentLookup_search_label' => 'Carian Pelajar',
'studentLookup_search_placeholder' => 'Cari matrik, nama, atau fakulti',
'studentLookup_search_button' => 'Cari',
'studentLookup_no_search_results' => 'Tiada rekod pelajar dijumpai untuk carian semasa.',
'studentLookup_empty_table' => 'Tiada rekod pelajar untuk dipaparkan.',
'studentLookup_loading' => 'Sedang memuatkan data pelajar...',
'config_general_branding_sidebar_user_image' => 'Imej Pengguna Sidebar',
'config_general_branding_sidebar_user_image_help' => 'Pilih imej latar kecil yang dipaparkan di bawah logo dalam sidebar.',
'config_general_branding_sidebar_user_image_preview' => 'Preview',
'config_general_validation_sidebar_user_image' => '%s mesti dipilih daripada senarai imej yang dibenarkan.',
'pageTemplateGenerator_page_title' => 'Penjana Template Sistem',
'pageTemplateGenerator_header_title' => 'Penjana Template Sistem',
'pageTemplateGenerator_breadcrumb_active' => 'System Template Generator',
'pageTemplateGenerator_form_title' => 'Tetapan Generator',
'pageTemplateGenerator_form_subtitle' => 'Pilih template, tetapkan identiti halaman, dan semak fail yang akan dijana sebelum menciptanya.',
'pageTemplateGenerator_field_page_name' => 'Nama Halaman',
'pageTemplateGenerator_field_page_name_placeholder' => 'Contoh: senarai pelajar',
'pageTemplateGenerator_field_page_name_help' => 'Masukkan nama halaman sahaja, tanpa .php. Contoh: senarai-pelajar',
'pageTemplateGenerator_field_template' => 'Jenis Template',
'pageTemplateGenerator_field_template_placeholder' => 'Sila pilih jenis template',
'pageTemplateGenerator_field_title_ms' => 'Tajuk Halaman (MS)',
'pageTemplateGenerator_field_title_ms_placeholder' => 'Contoh: Senarai Pelajar',
'pageTemplateGenerator_field_title_en' => 'Tajuk Halaman (EN)',
'pageTemplateGenerator_field_title_en_placeholder' => 'Example: Student List',
'pageTemplateGenerator_field_icon' => 'Ikon Halaman',
'pageTemplateGenerator_field_access_mode' => 'Mod Akses',
'pageTemplateGenerator_field_access_mode_help' => 'Pilih Super Admin Sahaja untuk halaman yang sangat sensitif. Pilih Ikut Menu Kumpulan untuk halaman yang akan diberi melalui akses menu dan tetapan kumpulan.',
'pageTemplateGenerator_tab_form' => 'Borang Template',
'pageTemplateGenerator_tab_page_icon' => 'Ikon Halaman',
'pageTemplateGenerator_tab_page_icon_help' => 'Pilih ikon yang paling sesuai mewakili halaman yang dijana dalam sidebar dan senarai modul.',
'pageTemplateGenerator_tab_access_mode' => 'Mod Akses',
'pageTemplateGenerator_access_group_menu_title' => 'Ikut Menu Kumpulan',
'pageTemplateGenerator_access_group_menu_help' => 'Halaman ini ikut akses menu. Hanya kumpulan yang diberikan path menu tersebut boleh membukanya.',
'pageTemplateGenerator_access_super_admin_title' => 'Super Admin Sahaja',
'pageTemplateGenerator_access_super_admin_help' => 'Halaman ini dikunci pada peringkat polisi dan hanya boleh dibuka oleh Super Admin.',
'pageTemplateGenerator_access_mode_group_menu_based' => 'Ikut Menu Kumpulan',
'pageTemplateGenerator_access_mode_super_admin_only' => 'Super Admin Sahaja',
'pageTemplateGenerator_btn_preview' => 'Preview Output',
'pageTemplateGenerator_btn_generate' => 'Jana Fail',
'pageTemplateGenerator_preview_title' => 'Ringkasan Preview',
'pageTemplateGenerator_preview_subtitle' => 'Semak nama fail dan status collision sebelum generate.',
'pageTemplateGenerator_preview_empty' => 'Belum ada preview. Isi borang dan klik Preview Output.',
'pageTemplateGenerator_preview_template' => 'Template',
'pageTemplateGenerator_preview_slug' => 'Slug Halaman',
'pageTemplateGenerator_preview_controller' => 'Kelas Controller',
'pageTemplateGenerator_preview_icon' => 'Ikon Halaman',
'pageTemplateGenerator_preview_files' => 'Fail Output',
'pageTemplateGenerator_preview_page_file' => 'Fail Page',
'pageTemplateGenerator_preview_controller_file' => 'Fail Controller',
'pageTemplateGenerator_preview_css_file' => 'Fail CSS',
'pageTemplateGenerator_exists_yes' => 'Sudah wujud',
'pageTemplateGenerator_exists_no' => 'Tersedia',
'pageTemplateGenerator_generated_title' => 'Fail Dijana',
'pageTemplateGenerator_generation_blocked' => 'Janaan dinyahaktifkan kerana satu atau lebih fail sasaran telah wujud.',
'pageTemplateGenerator_generation_status' => 'Status Janaan',
'pageTemplateGenerator_generation_status_blocked' => 'Disekat',
'pageTemplateGenerator_generation_status_ready' => 'Sedia untuk dijana',
'pageTemplateGenerator_error_csrf' => 'CSRF token tidak sah.',
'pageTemplateGenerator_success_generate' => 'Fail berjaya dijana.',
'pageTemplateGenerator_success_title' => 'Fail Berjaya Dijana',
'pageTemplateGenerator_btn_ok' => 'OK',
'pageTemplateGenerator_list_title' => 'Template Dijana',
'pageTemplateGenerator_list_subtitle' => 'Urus template halaman yang telah dijana dan semak artifak output yang dicipta oleh sistem.',
'pageTemplateGenerator_action_create' => 'Cipta Template Baharu',
'pageTemplateGenerator_col_template_name' => 'Nama Template',
'pageTemplateGenerator_col_type' => 'Jenis',
'pageTemplateGenerator_col_page' => 'Halaman',
'pageTemplateGenerator_col_status' => 'Status',
'pageTemplateGenerator_col_last_updated' => 'Kemaskini Terakhir',
'pageTemplateGenerator_col_actions' => 'Tindakan',
'pageTemplateGenerator_modal_create_title' => 'Cipta Template Baharu',
'pageTemplateGenerator_modal_create_subtitle' => 'Tetapkan identiti template, semak output, dan jana fail dalam satu aliran.',
'pageTemplateGenerator_field_template_name' => 'Nama Template',
'pageTemplateGenerator_field_template_name_placeholder' => 'Contoh: Asas Senarai Pelajar',
'pageTemplateGenerator_btn_close' => 'Tutup',
'pageTemplateGenerator_btn_view' => 'Lihat',
'pageTemplateGenerator_btn_visit' => 'Buka Halaman',
'pageTemplateGenerator_detail_title' => 'Butiran Template',
'pageTemplateGenerator_detail_subtitle' => 'Semak metadata dan path output yang dijana untuk rekod template ini.',
'pageTemplateGenerator_preview_db_slug' => 'Slug Pangkalan Data',
'pageTemplateGenerator_preview_db_slug_help' => 'Semak sama ada slug halaman sudah wujud dalam rekod template.',
'pageTemplateGenerator_preview_db_controller' => 'Controller Pangkalan Data',
'pageTemplateGenerator_preview_db_controller_help' => 'Semak sama ada kelas controller sudah wujud dalam rekod template.',
'pageTemplateGenerator_preview_toggle_show' => 'Papar Ringkasan Preview',
'pageTemplateGenerator_preview_toggle_hide' => 'Sembunyi Ringkasan Preview',
'pageTemplateGenerator_status_generated' => 'Dijana',
'pageTemplateGenerator_status_archived' => 'Diarkib',
'pageTemplateGenerator_status_failed' => 'Gagal',
'pageTemplateGenerator_required_note' => 'Medan yang ditanda dengan * adalah wajib diisi.',
'pageTemplateGenerator_required_field' => 'Medan ini wajib diisi.',
'pageTemplateGenerator_validation_required' => 'Sila lengkapkan semua medan wajib dan pilih ikon halaman.',
'pageTemplateGenerator_error_create_failed' => 'Penciptaan template gagal. Sila cuba semula atau hubungi pentadbir sistem.',

// ===== BEGIN Generated Template: Jarak [bdr_distance] =====
'bdr_distance_page_title' => 'Jarak Lokasi Staf',
'bdr_distance_table_title' => 'Staf Location',
'bdr_distance_table_subtitle' => 'Memaparkan rekod lokasi staf dan maklumat jarak yang telah dikira.',
'bdr_distance_total_records' => 'rekod',
'bdr_distance_col_no' => 'No.',
'bdr_distance_col_name' => 'Nama',
'bdr_distance_col_staff_no' => 'No. Staf',
'bdr_distance_col_department' => 'Jabatan',
'bdr_distance_col_mailing_address' => 'Alamat (Map)',
'bdr_distance_col_distance' => 'Jarak (KM)',
'bdr_distance_col_group' => 'Kumpulan',
'bdr_distance_col_access' => 'Akses',
'bdr_distance_col_actions' => 'Tindakan',
'bdr_distance_access_allowed' => 'Dibenarkan',
'bdr_distance_access_blocked' => 'Disekat',
'bdr_distance_btn_cancel' => 'Batal',
'bdr_distance_btn_close' => 'Tutup',
'bdr_distance_btn_delete' => 'Padam',
'bdr_distance_btn_edit' => 'Edit',
'bdr_distance_btn_view' => 'Lihat',
'bdr_distance_btn_no' => 'Tidak',
'bdr_distance_btn_ok' => 'OK',
'bdr_distance_btn_sample' => 'Add New',
'bdr_distance_btn_save' => 'Simpan',
'bdr_distance_btn_yes' => 'Ya',
'bdr_distance_delete_success_text' => 'diproses sebagai tindakan sample tanpa interaksi backend.',
'bdr_distance_delete_success_title' => 'Sample Delete Complete',
'bdr_distance_delete_text' => 'Ini hanyalah sample frontend. Tiada tindakan backend akan dicetuskan.',
'bdr_distance_delete_title' => 'Padam Rekod Sample?',
'bdr_distance_edit_success_text' => 'Aliran edit sample selesai tanpa menghantar data ke backend.',
'bdr_distance_edit_success_title' => 'Sample Save Complete',
'bdr_distance_field_access' => 'Akses',
'bdr_distance_field_department' => 'Jabatan',
'bdr_distance_field_staff_no' => 'No. Staf',
'bdr_distance_field_distance' => 'Jarak ke UPNM',
'bdr_distance_field_mailing_address' => 'Alamat Surat Menyurat',
'bdr_distance_field_description' => 'Deskripsi',
'bdr_distance_field_group' => 'Kumpulan',
'bdr_distance_field_name' => 'Nama',
'bdr_distance_field_updated_at' => 'Kemaskini Terakhir',
'bdr_distance_modal_add_title' => 'Tambah Rekod',
'bdr_distance_modal_edit_title' => 'Edit Rekod',
'bdr_distance_modal_view_title' => 'Maklumat Staf',
'bdr_distance_col_status' => 'Isu',
'bdr_distance_department_filter_placeholder' => 'Semua Jabatan',
'bdr_distance_filter_distance_placeholder' => 'Semua Jarak',
'bdr_distance_filter_distance_lt8' => '< 8 KM',
'bdr_distance_filter_distance_eq8' => '= 8 KM',
'bdr_distance_filter_distance_gt8' => '> 8 KM',
'bdr_distance_filter_all' => 'Semua',
'bdr_distance_filter_distance_le8_bdr' => '≤ 8 KM',
'bdr_distance_filter_distance_gt8_bdr' => '> 8 KM',
'bdr_distance_filter_custom' => 'Custom Filter',
'bdr_distance_filter_modal_title' => 'Custom Distance Filter',
'bdr_distance_filter_field_label' => 'Field',
'bdr_distance_filter_operator_label' => 'Operator',
'bdr_distance_filter_operator_between' => 'between',
'bdr_distance_filter_value_label' => 'Value',
'bdr_distance_filter_min_label' => 'Min',
'bdr_distance_filter_max_label' => 'Max',
'bdr_distance_filter_apply_btn' => 'Apply',
'bdr_distance_filter_reset_btn' => 'Reset Filter',
'bdr_distance_filter_badge_default' => 'Filter: Semua',
'bdr_distance_filter_badge_le8' => 'Filter: ≤ 8 KM',
'bdr_distance_filter_badge_gt8' => 'Filter: > 8 KM',
'bdr_distance_filter_badge_custom' => 'Filter: jarak_km {operator} {value}',
'bdr_distance_filter_badge_between' => 'Filter: jarak_km BETWEEN {min} AND {max}',
'bdr_distance_filter_validation_between' => 'Sila isi nilai minimum dan maksimum yang sah.',
'bdr_distance_filter_validation_value' => 'Sila isi satu nilai nombor yang sah.',
'bdr_distance_filter_validation_range' => 'Nilai minimum tidak boleh melebihi maksimum.',
'bdr_distance_open_map' => 'Buka peta',
'bdr_distance_tab_info' => 'Maklumat',
'bdr_distance_tab_visual' => 'Route Visual',
'bdr_distance_field_matched_query' => 'Alamat Diguna',
'bdr_distance_field_provider_matched_address' => 'Alamat Padanan Provider',
'bdr_distance_field_staff_input_address' => 'Alamat Input Staf',
'bdr_distance_field_home_coords' => 'Koordinat Rumah',
'bdr_distance_field_match_quality' => 'Kualiti Match',
'bdr_distance_field_distance_source' => 'Sumber Jarak',
'bdr_distance_field_debug_reason' => 'Sebab Gagal',
'bdr_distance_field_route_visual' => 'Route Visual',
'bdr_distance_stats_modal_title' => 'Statistik Lokasi Staf',
'bdr_distance_stats_total' => 'Jumlah Rekod',
'bdr_distance_stats_calculated' => 'Berjaya Dikira',
'bdr_distance_stats_failed' => 'Gagal Kira',
'bdr_distance_stats_empty' => 'Tiada Alamat',
'bdr_distance_stats_invalid' => 'Alamat Tak Lengkap',
'bdr_distance_stats_pending' => 'Belum Dikira',
'bdr_distance_stats_lt8' => '< 8 KM',
'bdr_distance_stats_eq8' => '= 8 KM',
'bdr_distance_stats_gt8' => '> 8 KM',
'bdr_distance_dt_length_menu' => 'Papar _MENU_ rekod',
'bdr_distance_dt_info' => 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod',
'bdr_distance_dt_info_empty' => 'Memaparkan 0 hingga 0 daripada 0 rekod',
'bdr_distance_dt_empty' => 'Tiada rekod staf aktif ditemui',
'bdr_distance_dt_previous' => 'Sebelum',
'bdr_distance_dt_next' => 'Seterusnya',
'bdr_distance_dt_zero_records' => 'Tiada rekod sepadan ditemui',
'bdr_distance_dt_search_placeholder' => 'Cari staf',
'bdr_distance_loading_department' => 'Memuat data jabatan...',
'bdr_distance_not_generated' => 'Belum Dijana',
'bdr_distance_not_calculated' => 'Belum Dikira',
'bdr_distance_status_no_issue' => 'Tiada Isu',
'bdr_distance_status_incomplete' => 'Alamat tidak lengkap',
'bdr_distance_status_invalid' => 'Alamat tidak lengkap',
'bdr_distance_status_no_address' => 'Tiada Alamat',
'bdr_distance_status_calculating' => 'Mengira...',
'bdr_distance_status_review_location' => 'Semak Lokasi',
'bdr_distance_filter_issue_all' => 'Semua Isu',
'bdr_distance_status_failed' => 'Gagal Kira',
'bdr_distance_msg_invalid' => 'Jarak berdasarkan alamat yang mungkin tidak lengkap.',
'bdr_distance_msg_failed_cached' => 'Kiraan terdahulu gagal. Klik butang Kira untuk cuba semula.',
'bdr_distance_msg_no_address' => 'Jarak tidak dapat dikira kerana tiada alamat.',
'bdr_distance_msg_calculating' => 'Sedang mengira jarak ke UPNM.',
'bdr_distance_msg_checking_cache' => 'Sedang menyemak cache jarak.',
'bdr_distance_msg_low_match' => 'Lokasi geocode meragukan. Sila semak alamat dan koordinat.',
'bdr_distance_msg_unknown' => 'Jarak belum dapat ditentukan.',
'bdr_distance_msg_address_changed' => 'Alamat telah berubah. Sila kira semula.',
'bdr_distance_msg_route' => 'Jarak laluan pemanduan terdekat ke UPNM.',
'bdr_distance_msg_straight' => 'Anggaran jarak garis lurus ke UPNM.',
'bdr_distance_msg_lookup_failed' => 'Lookup jarak gagal.',
'bdr_distance_btn_calculate' => 'Kira Jarak',
'bdr_distance_btn_email_reminder' => 'Peringatan Emel',
'bdr_distance_btn_email_reminder_short' => 'Reminder',
'bdr_distance_btn_bulk_email' => 'Bulk Emel',
'bdr_distance_btn_export_xls' => 'Export XLS',
'bdr_distance_export_empty' => 'Tiada data untuk dieksport.',
'bdr_distance_export_failed' => 'Export XLS gagal. Sila cuba lagi.',
'bdr_distance_reminder_title' => 'Peringatan Emel',
'bdr_distance_reminder_text' => 'Fungsi email reminder belum diaktifkan lagi untuk rekod ini.',
'bdr_distance_source_route' => 'Laluan',
'bdr_distance_source_straight' => 'Garis Lurus',
'bdr_distance_source_deferred' => 'Belum Disemak',
'bdr_distance_source_unavailable' => 'Tidak Tersedia',
'bdr_distance_source_address_only' => 'Alamat Sahaja',
'bdr_distance_source_failed_cache' => 'Cache Gagal',
'bdr_distance_provider_google' => 'Google',
'bdr_distance_provider_geoapify' => 'Geoapify',
'bdr_distance_provider_tomtom' => 'TomTom',
'bdr_distance_provider_ors' => 'OpenRouteService',
'bdr_distance_provider_osrm' => 'OSRM',
'bdr_distance_debug_deferred' => 'Belum disemak lagi',
'bdr_distance_debug_address_empty' => 'Tiada alamat',
'bdr_distance_debug_address_invalid' => 'Alamat tidak lengkap',
'bdr_distance_debug_lookup_failed' => 'Permintaan lookup gagal',
'bdr_distance_debug_geocode_failed' => 'Geocode gagal',
'bdr_distance_debug_route_failed' => 'Laluan gagal dijana',
'bdr_distance_debug_low_match' => 'Padanan lokasi rendah',
'bdr_distance_debug_postcode_mismatch' => 'Poskod berbeza dengan provider',
'bdr_distance_debug_ok' => 'Berjaya',
'bdr_distance_debug_loaded_cache' => 'Dibaca dari cache',
'bdr_distance_debug_cached_failed' => 'Kiraan terdahulu gagal',
'bdr_distance_debug_address_changed' => 'Alamat telah berubah',
'bdr_distance_debug_manual_required' => 'Perlu kiraan manual',
'bdr_distance_route_label_home' => 'RUMAH',
'bdr_distance_route_label_office' => 'UPNM',
'bdr_distance_origin_db' => 'Pangkalan Data',
'bdr_distance_origin_google_live' => 'Google Langsung',
'bdr_distance_origin_recalculated' => 'Dikira Semula',
'bdr_distance_origin_none' => 'Menunggu',
'bdr_distance_route_preview_empty' => 'Tiada visual laluan',
'bdr_distance_route_preview_generating' => 'Sedang menjana visual laluan...',
'bdr_distance_route_preview_aria' => 'Visual laluan provider',
'bdr_distance_sample_add_success_text' => 'Aliran tambah sample selesai tanpa menghantar data ke backend.',
'bdr_distance_sample_add_success_title' => 'Sample Add Complete',
'bdr_distance_email_modal_title' => 'Peringatan Emel',
'bdr_distance_email_modal_kicker' => 'Komunikasi Staf',
'bdr_distance_email_modal_subtitle' => 'Hantar emel peringatan untuk semakan alamat Bekerja Dari Rumah (BDR).',
'bdr_distance_email_staff_info_title' => 'Maklumat Staf',
'bdr_distance_email_staff_info_subtitle' => 'Semak butiran penerima sebelum emel peringatan dihantar.',
'bdr_distance_email_recipient_label' => 'Emel Penerima',
'bdr_distance_email_test_recipient_note' => 'Emel peringatan akan dihantar terus kepada alamat emel staf yang dipaparkan dalam rekod ini.',
'bdr_distance_email_issue_label' => 'Isu Dikesan',
'bdr_distance_email_preview_btn' => 'Preview Render',
'bdr_distance_email_send_btn' => 'Hantar Emel',
'bdr_distance_email_preview_result_title' => 'Hasil Preview',
'bdr_distance_email_preview_success_title' => 'Preview Berjaya',
'bdr_distance_email_preview_success_text' => 'Preview emel reminder berjaya dijana.',
'bdr_distance_email_preview_failed_title' => 'Preview Gagal',
'bdr_distance_email_preview_failed' => 'Preview emel reminder gagal dijana.',
'bdr_distance_email_send_success_title' => 'Emel Berjaya Dihantar',
'bdr_distance_email_send_success' => 'Emel reminder berjaya dihantar.',
'bdr_distance_email_send_failed_title' => 'Emel Gagal Dihantar',
'bdr_distance_email_send_failed' => 'Emel reminder gagal dihantar.',
'bdr_distance_email_sending_label' => 'Menghantar...',
'bdr_distance_email_summary_kicker' => 'Ringkasan Tindakan',
'bdr_distance_email_summary_title' => 'Peringatan akan dihantar terus selepas pengesahan.',
'bdr_distance_email_summary_subtitle' => 'Gunakan panel ini untuk semak penerima, isu, dan alamat ujian sebelum menghantar.',
'bdr_distance_email_summary_recipient' => 'Penerima Peringatan',
'bdr_distance_email_dispatch_note_title' => 'Nota Penghantaran',
'bdr_distance_email_checklist_title' => 'Semakan Pantas',
'bdr_distance_email_checklist_1' => 'Butiran staf dan jabatan dipaparkan berdasarkan rekod semasa.',
'bdr_distance_email_checklist_2' => 'Isu yang dikesan akan disertakan dalam kandungan peringatan.',
'bdr_distance_email_checklist_3' => 'Tekan Hantar Emel untuk teruskan selepas pengesahan akhir.',
'bdr_distance_email_confirm_title' => 'Hantar Peringatan Emel?',
'bdr_distance_email_confirm_text' => 'Anda pasti mahu hantar reminder kepada',
'bdr_distance_email_confirm_yes' => 'Ya, Hantar',
'bdr_distance_email_confirm_no' => 'Batal',
'bdr_distance_email_invalid_email' => 'Alamat emel staf tidak sah.',
'bdr_distance_email_template_inline' => 'Reminder Semakan Alamat BDR',
'bdr_distance_email_subject' => 'Peringatan Pengesahan Alamat Bekerja Dari Rumah (BDR) untuk {{recipient_name}}',
'bdr_distance_email_body_html' => '<p>{{recipient_name}}</p><p>{{email_intro}}</p><div style="margin:1rem 0 1.15rem;padding:1rem 1.05rem;border:1px solid #dbe7f3;border-radius:16px;background:linear-gradient(180deg,#f8fbff 0%,#eef6ff 100%);"><div style="margin:0 0 0.8rem;font-size:0.8rem;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#2563eb;">Ringkasan Rekod</div><table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;"><tr><td style="width:170px;padding:0.42rem 0.7rem 0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.78rem;font-weight:700;color:#475569;vertical-align:top;">No. Staf</td><td style="padding:0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.92rem;color:#0f172a;font-weight:600;">{{staff_no}}</td></tr><tr><td style="width:170px;padding:0.42rem 0.7rem 0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.78rem;font-weight:700;color:#475569;vertical-align:top;">Jabatan</td><td style="padding:0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.92rem;color:#0f172a;font-weight:600;">{{department_name}}</td></tr><tr><td style="width:170px;padding:0.42rem 0.7rem 0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.78rem;font-weight:700;color:#475569;vertical-align:top;">Alamat Semasa</td><td style="padding:0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.92rem;color:#0f172a;font-weight:600;line-height:1.55;">{{current_address}}</td></tr><tr><td style="width:170px;padding:0.42rem 0.7rem 0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.78rem;font-weight:700;color:#475569;vertical-align:top;">Status Semakan</td><td style="padding:0.42rem 0;border-top:1px solid #dbe7f3;font-size:0.92rem;color:#0f172a;font-weight:700;">{{status_label}}</td></tr><tr><td style="width:170px;padding:0.42rem 0.7rem 0.1rem 0;border-top:1px solid #dbe7f3;font-size:0.78rem;font-weight:700;color:#475569;vertical-align:top;">Jarak Dipaparkan</td><td style="padding:0.42rem 0 0.1rem;border-top:1px solid #dbe7f3;font-size:0.92rem;color:#0f172a;font-weight:700;">{{distance_label}}</td></tr></table></div><div><strong>Punca Semakan Diperlukan:</strong>{{issue_reason_html}}</div>{{primary_action_html}}<p>{{guidance_text}}</p><p>Terima kasih.</p>',
'bdr_distance_email_body_text' => "{{recipient_name}}\n\n{{email_intro}}\n\nNo. Staf: {{staff_no}}\nJabatan: {{department_name}}\nAlamat Semasa: {{current_address}}\nStatus Semakan: {{status_label}}\nJarak Dipaparkan: {{distance_label}}\nPunca Semakan Diperlukan:\n{{issue_reason_text}}\n\n{{primary_action_text}}\n\n{{guidance_text}}\n\nTerima kasih.",
'bdr_distance_email_footer_note' => 'Emel ini dijana secara automatik. Sila jangan balas emel ini.',
'bdr_distance_email_default_recipient' => 'Tuan/Puan',
'bdr_distance_email_issue_default' => 'Semakan alamat BDR diperlukan',
'bdr_distance_email_issue_address_changed' => 'Alamat telah berubah',
'bdr_distance_email_intro_default' => 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda memerlukan semakan bagi tujuan rekod Bekerja Dari Rumah (BDR). Sila rujuk butiran isu yang disenaraikan di bawah dan ambil tindakan yang berkaitan.',
'bdr_distance_email_intro_multiple' => 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda mempunyai beberapa perkara yang memerlukan semakan bagi tujuan rekod Bekerja Dari Rumah (BDR). Sila rujuk butiran isu yang disenaraikan di bawah dan ambil tindakan yang berkaitan.',
'bdr_distance_email_intro_review_location' => 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda memerlukan semakan kerana sistem tidak dapat memadankan lokasi alamat tersebut dengan tahap keyakinan yang mencukupi bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_intro_address_changed' => 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda dikesan telah berubah berbanding rekod jarak yang pernah dikira sebelum ini. Sila semak dan pastikan alamat yang direkodkan adalah tepat bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_intro_state_mismatch' => 'Emel ini adalah bagi memaklumkan bahawa negeri yang dipilih dalam rekod alamat anda tidak sepadan dengan negeri yang dikesan berdasarkan alamat rumah semasa yang direkodkan. Sila semak dan kemas kini maklumat negeri yang betul bagi tujuan semakan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_intro_too_close' => 'Emel ini adalah bagi memaklumkan bahawa jarak alamat rumah semasa anda ke UPNM dikesan terlalu dekat dan memerlukan semakan lanjut bagi memastikan alamat tersebut adalah alamat kediaman semasa yang sah untuk tujuan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_intro_too_far' => 'Emel ini adalah bagi memaklumkan bahawa jarak alamat rumah semasa anda ke UPNM dikesan melebihi julat semakan biasa. Sila sahkan bahawa alamat tersebut adalah kediaman semasa anda dan digunakan untuk berulang-alik bekerja bagi tujuan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_intro_outside_region' => 'Emel ini adalah bagi memaklumkan bahawa alamat rumah semasa anda dikesan berada di luar kawasan semakan biasa iaitu Selangor, Wilayah Persekutuan Kuala Lumpur, atau Putrajaya. Sila sahkan bahawa alamat tersebut adalah kediaman semasa anda dan digunakan untuk berulang-alik bekerja bagi tujuan rekod Bekerja Dari Rumah (BDR).',
'bdr_distance_email_reason_default' => 'Maklumat alamat semasa memerlukan semakan semula bagi membolehkan sistem mengesahkan jarak rumah ke UPNM dengan tepat.',
'bdr_distance_email_imap_button' => 'Akses Sistem iMAP',
'bdr_distance_email_guidance_self_confirm' => 'Jika alamat di atas tidak tepat, sila kemas kini maklumat alamat anda di iMAP melalui portal OneID UPNM.',
'bdr_distance_email_guidance_update_imap' => 'Sila kemas kini atau semak semula maklumat alamat anda di iMAP bagi memastikan pengesahan jarak dapat dibuat dengan tepat.',
'bdr_distance_email_reason_provider_match' => 'Alamat yang dipadankan oleh Google Maps:',
'bdr_distance_email_reason_match_quality' => 'Kualiti padanan:',
'bdr_distance_email_reason_distance_label' => 'Jarak semasa yang dipaparkan:',
'bdr_distance_email_reason_detected_state' => 'Negeri dikesan:',
'bdr_distance_email_reason_selected_state' => 'Negeri dalam rekod:',
'bdr_distance_email_reason_address_state' => 'Negeri yang dikesan daripada alamat:',
'bdr_distance_email_reason_issue_category' => 'Kategori isu:',
'bdr_distance_email_reason_address_changed' => 'Alamat surat menyurat semasa didapati berbeza daripada rekod terdahulu, jadi pengesahan jarak perlu dibuat semula.',
'bdr_distance_email_reason_no_address' => 'Tiada alamat surat menyurat direkodkan dalam sistem, jadi jarak rumah ke UPNM tidak dapat dikira.',
'bdr_distance_email_reason_incomplete' => 'Alamat surat menyurat tidak lengkap atau tidak cukup jelas untuk sistem menentukan lokasi rumah dengan tepat.',
'bdr_distance_email_reason_review_location' => 'Padanan lokasi yang dijumpai adalah meragukan atau tidak cukup tepat, jadi jarak rumah ke UPNM tidak dapat disahkan dengan yakin.',
'bdr_distance_email_reason_state_mismatch' => 'Negeri yang dipilih dalam rekod tidak sepadan dengan alamat semasa yang direkodkan. Sila semak dan kemas kini maklumat negeri yang betul.',
'bdr_distance_email_reason_too_close' => 'Jarak alamat yang direkodkan terlalu dekat dengan pejabat, jadi semakan manual diperlukan untuk memastikan alamat tersebut benar-benar alamat kediaman semasa.',
'bdr_distance_email_reason_too_far' => 'Jarak alamat yang direkodkan adalah melebihi julat semakan biasa, jadi pengesahan manual diperlukan bagi memastikan alamat tersebut adalah betul.',
'bdr_distance_email_reason_outside_region' => 'Alamat yang direkodkan berada di luar kawasan kebiasaan semakan iaitu Selangor, Wilayah Persekutuan Kuala Lumpur, atau Putrajaya.',
'bdr_distance_issue_state_mismatch' => 'Negeri Tidak Sepadan',
'bdr_distance_issue_too_close' => 'Jarak Terlalu Dekat',
'bdr_distance_issue_too_far' => 'Jarak Terlalu Jauh',
'bdr_distance_issue_outside_region' => 'Luar Kawasan Semakan',
'bdr_distance_status_confirmed_tooltip' => 'Staf telah mengesahkan alamat ini.',
'bdr_distance_json_parse_html_error' => 'Server returned HTML instead of JSON. Semak log PHP/server untuk ralat sebenar.',
'bdr_distance_rows_data_failed_title' => 'Ralat Memuat Data',
'bdr_distance_rows_data_failed_text' => 'Data jabatan tidak dapat dimuatkan buat masa ini. Sila cuba semula.',
'bdr_distance_notification_exclusion_title' => 'Pengecualian Notifikasi',
'bdr_distance_notification_exclusion_hint' => 'Tandakan isu yang tidak perlu lagi menerima emel notifikasi untuk alamat semasa ini.',
'bdr_distance_notification_exclusion_reason_placeholder' => 'Sebab ringkas',
'bdr_distance_notification_exclusion_notes_placeholder' => 'Catatan tambahan (optional)',
'bdr_distance_notification_exclusion_save_btn' => 'Simpan Exclude',
'bdr_distance_notification_exclusion_none' => 'Tiada isu untuk dikecualikan bagi rekod ini.',
'bdr_distance_notification_exclusion_invalid' => 'Maklumat rekod tidak lengkap untuk simpan exclude.',
'bdr_distance_notification_exclusion_saving' => 'Menyimpan...',
'bdr_distance_notification_exclusion_saved' => 'Tetapan exclude berjaya disimpan.',
'bdr_distance_notification_exclusion_failed' => 'Tetapan exclude gagal disimpan.',
'bdr_distance_notification_exclusion_status_ready' => 'Pilih isu yang perlu dikecualikan untuk alamat semasa ini.',
'bdr_distance_self_confirm_email_button' => 'Sahkan Alamat Ini',
'bdr_distance_self_confirm_email_note' => 'Jika alamat ini sememangnya kediaman semasa anda dan digunakan untuk berulang-alik bekerja, sila buat pengesahan melalui pautan berikut.',
'bdr_distance_self_confirm_issue_summary' => 'Isu yang memerlukan pengesahan:',
'bdr_distance_self_confirm_page_title' => 'Pengesahan Alamat BDR',
'bdr_distance_self_confirm_header_sub' => 'Pengesahan alamat notifikasi BDR melalui OneID UPNM',
'bdr_distance_self_confirm_ready_title' => 'Pengesahan Alamat',
'bdr_distance_self_confirm_ready_text' => 'Sila semak maklumat berikut dan sahkan jika alamat ini benar-benar digunakan untuk berulang-alik bekerja.',
'bdr_distance_self_confirm_success_title' => 'Pengesahan Berjaya',
'bdr_distance_self_confirm_success_text' => 'Pengesahan alamat berjaya disimpan. Anda tidak lagi akan menerima notifikasi bagi isu ini untuk alamat semasa yang sama.',
'bdr_distance_self_confirm_already_title' => 'Pautan Telah Digunakan',
'bdr_distance_self_confirm_already_text' => 'Pautan ini telah tamat kerana pengesahan alamat telah dilaksanakan.',
'bdr_distance_self_confirm_invalid_title' => 'Pautan Tidak Sah',
'bdr_distance_self_confirm_invalid_text' => 'Pautan pengesahan ini tidak sah atau tidak lagi tersedia.',
'bdr_distance_self_confirm_expired_title' => 'Pautan Tamat Tempoh',
'bdr_distance_self_confirm_expired_text' => 'Pautan pengesahan ini telah tamat tempoh. Sila rujuk emel notifikasi terkini atau hubungi pentadbir.',
'bdr_distance_self_confirm_not_allowed_title' => 'Pengesahan Tidak Dibenarkan',
'bdr_distance_self_confirm_not_allowed_text' => 'Isu ini tidak dibenarkan untuk pengesahan sendiri.',
'bdr_distance_self_confirm_resolved_title' => 'Pengesahan Tidak Diperlukan',
'bdr_distance_self_confirm_resolved_text' => 'Rekod ini tidak lagi memerlukan pengesahan sendiri untuk isu tersebut.',
'bdr_distance_self_confirm_staff_mismatch_title' => 'Akses Tidak Dibenarkan',
'bdr_distance_self_confirm_staff_mismatch_text' => 'Akaun SSO yang sedang log masuk tidak sepadan dengan rekod pengesahan ini.',
'bdr_distance_self_confirm_declaration_required_title' => 'Pengesahan Belum Lengkap',
'bdr_distance_self_confirm_declaration_required_text' => 'Sila tandakan pengakuan sebelum menghantar pengesahan.',
'bdr_distance_self_confirm_csrf_title' => 'Sesi Tidak Sah',
'bdr_distance_self_confirm_csrf_text' => 'Sesi anda tidak sah. Sila buka semula pautan daripada emel notifikasi.',
'bdr_distance_self_confirm_submit' => 'Sahkan Alamat Ini',
'bdr_distance_self_confirm_login_oneid' => 'Login OneID',
'bdr_distance_self_confirm_login_required_text' => 'Sila semak maklumat berikut. Untuk meneruskan pengesahan alamat, sila log masuk menggunakan OneID UPNM terlebih dahulu.',
'bdr_distance_self_confirm_close_page' => 'Tutup Halaman',
'bdr_distance_self_confirm_confirmed_at' => 'Disahkan pada',
'bdr_distance_self_confirm_imap' => 'Buka iMAP',
'bdr_distance_self_confirm_expires_at' => 'Token sah sehingga',
'bdr_distance_self_confirm_issue_label' => 'Isu',
'bdr_distance_self_confirm_declaration' => 'Saya mengesahkan alamat ini ialah kediaman semasa saya dan digunakan untuk berulang-alik bekerja. Sebarang maklumat palsu boleh dikenakan tindakan.',
'bdr_distance_email_none_label' => 'Tiada',
'bdr_distance_email_complete_label' => 'Lengkap',
'bdr_distance_bulk_email_modal_title' => 'Bulk Peringatan Emel',
'bdr_distance_bulk_email_modal_subtitle' => 'Hanya rekod bermasalah dalam paparan semasa akan dimasukkan ke dalam penghantaran berperingkat.',
'bdr_distance_bulk_email_summary_filtered' => 'Rekod Dipaparkan',
'bdr_distance_bulk_email_summary_eligible' => 'Layak Dihantar',
'bdr_distance_bulk_email_summary_skipped' => 'Akan Diskip',
'bdr_distance_bulk_email_dispatch_mode' => 'Mod Penghantaran',
'bdr_distance_bulk_email_dispatch_note' => 'Emel akan dihantar secara berperingkat terus kepada alamat emel staf yang layak.',
'bdr_distance_bulk_email_dispatch_test_prefix' => 'Semua emel dalam bulk job ini akan dihantar ke alamat ujian',
'bdr_distance_bulk_email_dispatch_direct' => 'Bulk emel akan dihantar terus kepada alamat emel staf yang layak.',
'bdr_distance_bulk_email_issue_filter_title' => 'Pilih Kategori Isu',
'bdr_distance_bulk_email_issue_filter_help' => 'Hanya kategori isu yang wujud dalam paparan semasa akan dipaparkan di sini.',
'bdr_distance_bulk_email_issue_filter_empty' => 'Tiada kategori isu tersedia untuk dihantar.',
'bdr_distance_bulk_email_preview_title' => 'Ringkasan Pemilihan',
'bdr_distance_bulk_email_preview_empty' => 'Tiada rekod dipilih buat masa ini.',
'bdr_distance_bulk_email_preview_prefix' => 'Sistem akan masukkan rekod bermasalah yang sedang dipaparkan ke dalam queue bulk email berdasarkan isu yang dipilih.',
'bdr_distance_bulk_email_start_btn' => 'Mulakan Bulk Emel',
'bdr_distance_bulk_email_progress_title' => 'Progress Bulk Emel',
'bdr_distance_bulk_email_progress_subtitle' => 'Emel dihantar dalam batch kecil untuk mengurangkan risiko spam dan elak beban mendadak pada sistem.',
'bdr_distance_bulk_email_stat_total' => 'Jumlah',
'bdr_distance_bulk_email_stat_processed' => 'Diproses',
'bdr_distance_bulk_email_stat_success' => 'Berjaya',
'bdr_distance_bulk_email_stat_failed' => 'Gagal',
'bdr_distance_bulk_email_status_idle' => 'Menunggu bulk email job dimulakan.',
'bdr_distance_bulk_email_status_queued' => 'Bulk email job telah dimasukkan ke queue.',
'bdr_distance_bulk_email_status_running' => 'Bulk email sedang dihantar secara berperingkat.',
'bdr_distance_bulk_email_status_completed' => 'Bulk email job telah selesai.',
'bdr_distance_bulk_email_no_issue_title' => 'Tiada Rekod Bermasalah',
'bdr_distance_bulk_email_no_issue_text' => 'Tiada rekod bermasalah dalam paparan semasa untuk dimasukkan ke dalam bulk email.',
'bdr_distance_bulk_email_starting' => 'Memulakan...',
'bdr_distance_bulk_email_start_success' => 'Bulk email job berjaya diwujudkan.',
'bdr_distance_bulk_email_start_failed_title' => 'Bulk Emel Gagal Dimulakan',
'bdr_distance_bulk_email_start_failed' => 'Bulk email job gagal dimulakan.',
'bdr_distance_bulk_email_process_failed_title' => 'Bulk Emel Gagal Diproses',
'bdr_distance_bulk_email_process_failed' => 'Pemprosesan bulk email gagal.',
'bdr_distance_bulk_email_process_success' => 'Batch bulk email telah diproses.',
'bdr_distance_bulk_email_completed_title' => 'Bulk Emel Selesai',
'bdr_distance_bulk_email_empty' => 'Tiada rekod layak untuk dihantar.',
'bdr_distance_bulk_email_invalid_job' => 'Rujukan bulk email job tidak sah.',
'bdr_distance_bulk_email_job_not_found' => 'Bulk email job tidak ditemui.',
'bdr_distance_bulk_email_status_failed' => 'Status bulk email tidak dapat diperoleh.',
'bdr_distance_bulk_email_invalid_exclusion' => 'Maklumat pengecualian notifikasi tidak sah.',
'bdr_distance_bulk_email_exclusion_saved' => 'Pengecualian notifikasi berjaya dikemas kini.',
'bdr_distance_bulk_email_exclusion_failed' => 'Pengecualian notifikasi gagal disimpan.',
'bdr_distance_bulk_email_invalid_csrf' => 'Sesi anda telah tamat. Sila muat semula halaman dan cuba lagi.',
'bdr_distance_bulk_email_invalid_delivery_email' => 'Alamat emel penghantaran tidak sah.',
// ===== END Generated Template: Jarak [bdr_distance] =====

// ===== BEGIN Generated Template: Geolokasi [bdr_geolocation] =====
'bdr_geolocation_page_title' => 'Geolokasi',
'bdr_geolocation_module_kicker' => 'Workspace Bertab',
'bdr_geolocation_tab_map_staff' => 'Map Staf',
'bdr_geolocation_tab_heatmap_staff' => 'Heatmap Staf',
'bdr_geolocation_map_kicker' => 'BDR Geolocation',
'bdr_geolocation_map_title' => 'Peta Geolokasi Staf',
'bdr_geolocation_map_subtitle' => 'Paparan lokasi rumah staf berdasarkan koordinat yang telah disimpan dalam pangkalan data.',
'bdr_geolocation_map_site_pending' => 'Site aktif',
'bdr_geolocation_map_count_pending' => 'Belum dimuat',
'bdr_geolocation_map_source_note' => 'Data dipaparkan berdasarkan rekod geolokasi yang tersedia dalam sistem.',
'bdr_geolocation_map_reload' => 'Muat Semula',
'bdr_geolocation_map_canvas_label' => 'Peta geolokasi staf',
'bdr_geolocation_map_waiting' => 'Peta sedia untuk dimuatkan.',
'bdr_geolocation_map_record_suffix' => 'rekod',
'bdr_geolocation_map_lib_missing' => 'Library peta tidak dapat dimuatkan.',
'bdr_geolocation_heatmap_kicker' => 'BDR Geolocation',
'bdr_geolocation_heatmap_title' => 'Heatmap Geolokasi Staf',
'bdr_geolocation_heatmap_subtitle' => 'Paparan kepadatan lokasi rumah staf berdasarkan rekod geolokasi yang tersedia dalam sistem.',
'bdr_geolocation_heatmap_canvas_label' => 'Heatmap geolokasi staf',
'bdr_geolocation_heatmap_waiting' => 'Heatmap akan dimuatkan apabila fungsi diaktifkan.',
'bdr_geolocation_heatmap_empty' => 'Tiada data heatmap untuk dipaparkan.',
'bdr_geolocation_heatmap_loading' => 'Memuat data heatmap staf...',
'bdr_geolocation_heatmap_load_error' => 'Data heatmap tidak dapat dimuatkan.',
'bdr_geolocation_heatmap_lib_missing' => 'Library heatmap tidak dapat dimuatkan.',
'bdr_geolocation_heatmap_point_singular' => 'titik',
'bdr_geolocation_heatmap_point_plural' => 'titik',
'bdr_geolocation_map_empty' => 'Tiada data geolokasi untuk dipaparkan.',
'bdr_geolocation_map_empty_filtered' => 'Tiada rekod sepadan dengan filter semasa.',
'bdr_geolocation_map_loading' => 'Memuat data geolokasi staf...',
'bdr_geolocation_map_json_error' => 'Server tidak memulangkan JSON. Sila semak sesi login atau log server.',
'bdr_geolocation_map_load_error' => 'Data geolokasi tidak dapat dimuatkan.',
'bdr_geolocation_route_loading' => 'Memuat laluan staf...',
'bdr_geolocation_route_load_error' => 'Laluan staf tidak dapat dimuatkan.',
'bdr_geolocation_fullscreen_open' => 'Paparan penuh',
'bdr_geolocation_fullscreen_close' => 'Keluar paparan penuh',
'bdr_geolocation_popup_department' => 'Jabatan',
'bdr_geolocation_popup_position' => 'Jawatan',
'bdr_geolocation_popup_address' => 'Alamat',
'bdr_geolocation_popup_distance' => 'Jarak',
'bdr_geolocation_popup_coordinates' => 'Koordinat',
'bdr_geolocation_filter_site' => 'Site',
'bdr_geolocation_filter_toggle' => 'Filter',
'bdr_geolocation_filter_quality' => 'Kualiti Padanan',
'bdr_geolocation_filter_quality_all' => 'Semua',
'bdr_geolocation_filter_search' => 'Carian',
'bdr_geolocation_filter_search_placeholder' => 'Cari no. staf, nama, jabatan atau alamat',
// ===== END Generated Template: Geolokasi [bdr_geolocation] =====

// ===== BEGIN Generated Template: Laporan Harian [bdr_daily_report] =====
'bdr_daily_report_page_title' => 'Laporan Harian BDR',
'bdr_daily_report_table_title' => 'Senarai BDR Diluluskan',
'bdr_daily_report_table_subtitle' => 'Hanya rekod BDR yang diluluskan oleh ketua jabatan dipaparkan. Klik Lihat untuk melihat butiran penuh',
'bdr_daily_report_filter_month' => 'Bulan',
'bdr_daily_report_filter_year' => 'Tahun',
'bdr_daily_report_col_no' => 'No.',
'bdr_daily_report_col_date' => 'Tarikh BDR',
'bdr_daily_report_col_reference' => 'No. Rujukan',
'bdr_daily_report_col_category' => 'Kategori',
'bdr_daily_report_col_approval' => 'Status / Catatan Pengarah',
'bdr_daily_report_approval_status_passed' => 'Lulus',
'bdr_daily_report_approval_status_rejected' => 'Tidak Lulus',
'bdr_daily_report_approval_status_unknown' => '-',
'bdr_daily_report_approval_tooltip_label' => 'Maklumat pengarah',
'bdr_daily_report_col_clock_in' => 'Masa Masuk',
'bdr_daily_report_col_clock_out' => 'Masa Keluar',
'bdr_daily_report_col_status' => 'Status',
'bdr_daily_report_col_actions' => 'Tindakan',
'bdr_daily_report_btn_cancel' => 'Batal',
'bdr_daily_report_btn_close' => 'Tutup',
'bdr_daily_report_btn_view' => 'Lihat',
'bdr_daily_report_btn_download_report' => 'Download Laporan Harian',
'bdr_daily_report_btn_save' => 'Simpan',
'bdr_daily_report_btn_save_draft' => 'Simpan Draf',
'bdr_daily_report_btn_preview_form' => 'Preview Borang',
'bdr_daily_report_btn_reset_item' => 'Kosongkan',
'bdr_daily_report_btn_add_to_table' => 'Tambah ke Jadual',
'bdr_daily_report_btn_update_table' => 'Kemaskini Jadual',
'bdr_daily_report_btn_edit_task' => 'Edit tugasan',
'bdr_daily_report_btn_delete_task' => 'Padam tugasan',
'bdr_daily_report_modal_view_title' => 'Butiran Rekod BDR',
'bdr_daily_report_modal_view_subtitle' => 'Paparan terperinci rekod BDR yang telah diluluskan untuk semakan staf.',
'bdr_daily_report_modal_view_intro_title' => 'Ringkasan Rekod',
'bdr_daily_report_modal_view_intro_text' => 'Semak maklumat permohonan, pemohon, dan kelulusan dalam susun atur yang lebih jelas.',
'bdr_daily_report_modal_entry_title' => 'Laporan Harian BDR',
'bdr_daily_report_modal_entry_subtitle' => 'Kemaskini laporan harian mengikut tarikh BDR approved dengan susun atur kerja yang lebih teratur.',
'bdr_daily_report_tab_info' => 'Info',
'bdr_daily_report_tab_entry' => 'Data Entry',
'bdr_daily_report_section_record_info' => 'Maklumat Rekod BDR',
'bdr_daily_report_summary_date' => 'Tarikh BDR',
'bdr_daily_report_summary_staff_name' => 'Nama Staf',
'bdr_daily_report_summary_staff_id' => 'No. Staf / Login ID',
'bdr_daily_report_summary_reference' => 'No. Rujukan',
'bdr_daily_report_summary_category' => 'Kategori',
'bdr_daily_report_summary_approval' => 'Kelulusan',
'bdr_daily_report_section_task_editor' => 'Editor Tugasan Harian',
'bdr_daily_report_task_editor_title' => 'Data Tugasan Harian',
'bdr_daily_report_task_editor_subtitle' => 'Isi tugasan dan klik tambah untuk masukkan ke dalam jadual.',
'bdr_daily_report_field_task_title' => 'Ketetapan Tugas',
'bdr_daily_report_field_task_title_placeholder' => 'Nyatakan tugasan yang dilaksanakan',
'bdr_daily_report_field_task_comment' => 'Ulasan dinyatakan oleh staf',
'bdr_daily_report_field_task_comment_placeholder' => 'Ulasan atau hasil tugasan',
'bdr_daily_report_field_task_status' => 'Status',
'bdr_daily_report_status_selesai' => 'Selesai',
'bdr_daily_report_status_tidak_selesai' => 'Tidak Selesai',
'bdr_daily_report_status_dalam_proses' => 'Dalam Proses',
'bdr_daily_report_status_ditangguhkan' => 'Ditangguhkan',
'bdr_daily_report_status_menunggu_maklum_balas' => 'Menunggu Maklum Balas',
'bdr_daily_report_section_task_list' => 'Senarai Tugasan',
'bdr_daily_report_task_table_title' => 'Jadual Tugasan Harian',
'bdr_daily_report_task_table_subtitle' => 'Semak, edit, atau padam tugasan yang telah dimasukkan sebelum simpan laporan.',
'bdr_daily_report_task_table_col_no' => 'Bil.',
'bdr_daily_report_task_table_col_task' => 'Ketetapan Tugas',
'bdr_daily_report_task_table_col_comment' => 'Ulasan Staf',
'bdr_daily_report_task_table_col_execution' => 'Status',
'bdr_daily_report_task_table_col_action' => 'Tindakan',
'bdr_daily_report_empty_table' => 'Tiada rekod BDR diluluskan ditemui.',
'bdr_daily_report_datatable_length' => 'Papar _MENU_ rekod',
'bdr_daily_report_datatable_info' => 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ rekod',
'bdr_daily_report_datatable_info_empty' => 'Memaparkan 0 hingga 0 daripada 0 rekod',
'bdr_daily_report_datatable_previous' => 'Sebelum',
'bdr_daily_report_datatable_next' => 'Seterusnya',
'bdr_daily_report_datatable_zero_records' => 'Tiada rekod sepadan ditemui',
'bdr_daily_report_datatable_search_placeholder' => 'Cari rekod',
'bdr_daily_report_task_datatable_info' => 'Memaparkan _START_ hingga _END_ daripada _TOTAL_ tugasan',
'bdr_daily_report_task_datatable_info_empty' => 'Memaparkan 0 hingga 0 daripada 0 tugasan',
'bdr_daily_report_task_datatable_empty' => 'Belum ada tugasan ditambah.',
'bdr_daily_report_task_datatable_zero_records' => 'Tiada tugasan ditemui',
'bdr_daily_report_swal_success_title' => 'Berjaya',
'bdr_daily_report_swal_warning_title' => 'Perhatian',
'bdr_daily_report_swal_info_title' => 'Makluman',
'bdr_daily_report_swal_error_title' => 'Ralat',
'bdr_daily_report_swal_question_title' => 'Pengesahan',
'bdr_daily_report_request_failed' => 'Permintaan tidak berjaya diproses.',
'bdr_daily_report_entry_load_failed' => 'Laporan harian tidak dapat dimuatkan.',
'bdr_daily_report_task_title_required' => 'Ketetapan tugas wajib diisi sebelum ditambah ke jadual.',
'bdr_daily_report_invalid_report_date' => 'Tarikh laporan tidak sah.',
'bdr_daily_report_record_saved' => 'Rekod berjaya disimpan.',
'bdr_daily_report_record_save_failed' => 'Rekod tidak dapat disimpan.',
'bdr_daily_report_confirm_save_draft' => 'Simpan draf laporan harian ini sekarang?',
'bdr_daily_report_confirm_save_draft_yes' => 'Ya, simpan draf',
'bdr_daily_report_confirm_save' => 'Simpan laporan harian ini sekarang?',
'bdr_daily_report_confirm_save_yes' => 'Ya, simpan',
'bdr_daily_report_preview_requires_draft' => 'Simpan draf terlebih dahulu sebelum preview borang.',
'bdr_daily_report_download_requires_draft' => 'Simpan draf atau laporan terlebih dahulu sebelum muat turun laporan harian.',
'bdr_daily_report_entry_action_fill' => 'Isi laporan',
'bdr_daily_report_entry_action_update' => 'Kemaskini laporan',
'bdr_daily_report_badge_draft' => 'Draf',
'bdr_daily_report_badge_saved' => 'Disimpan',
'bdr_daily_report_storage_not_ready' => 'Storage laporan harian belum tersedia. Sila jalankan migration modul BDR Report terlebih dahulu.',
'bdr_daily_report_invalid_request_action' => 'Tindakan permintaan tidak sah.',
'bdr_daily_report_missing_login_message' => 'ID login semasa tidak ditemui. Rekod BDR tidak dapat dimuatkan.',
'bdr_daily_report_missing_login_table' => 'ID login semasa tidak tersedia.',
'bdr_daily_report_no_records_login_message' => 'Tiada rekod BDR diluluskan ditemui untuk ID login semasa.',
'bdr_daily_report_no_records_login_table' => 'Tiada rekod BDR diluluskan untuk ID login ini.',
'bdr_daily_report_load_failed_message' => 'Data BDR approved tidak dapat dimuatkan buat masa ini.',
'bdr_daily_report_load_failed_table' => 'Data BDR approved tidak dapat dimuatkan.',
'bdr_daily_report_session_expired' => 'Sesi anda telah tamat. Sila muat semula halaman dan cuba lagi.',
'bdr_daily_report_invalid_report_date_message' => 'Tarikh laporan harian tidak sah.',
'bdr_daily_report_approved_not_found' => 'Rekod BDR approved bagi tarikh tersebut tidak ditemui.',
'bdr_daily_report_invalid_task_list' => 'Senarai tugasan tidak sah.',
'bdr_daily_report_task_title_required_each' => 'Setiap tugasan mesti mempunyai ketetapan tugas.',
'bdr_daily_report_invalid_task_status' => 'Status tugas tidak sah.',
'bdr_daily_report_at_least_one_task' => 'Sekurang-kurangnya satu tugasan diperlukan sebelum laporan disimpan.',
'bdr_daily_report_submit_success' => 'Laporan harian berjaya disimpan.',
'bdr_daily_report_save_draft_success' => 'Draf laporan harian berjaya disimpan.',
'bdr_daily_report_modal_default_tab' => 'Maklumat',
'bdr_daily_report_modal_default_field' => 'Maklumat',
'bdr_daily_report_modal_tab_record' => 'Maklumat Rekod',
'bdr_daily_report_modal_tab_applicant' => 'Maklumat Pemohon',
'bdr_daily_report_modal_tab_approval' => 'Kelulusan',
'bdr_daily_report_modal_tab_location' => 'Lokasi dan Jarak',
'bdr_daily_report_modal_tab_support' => 'Sokongan dan Laporan',
'bdr_daily_report_modal_field_date' => 'Tarikh',
'bdr_daily_report_modal_field_reference' => 'No. Rujukan',
'bdr_daily_report_modal_field_created_at' => 'Created At',
'bdr_daily_report_modal_field_updated_at' => 'Updated At',
'bdr_daily_report_modal_field_staff_id' => 'Staf ID',
'bdr_daily_report_modal_field_name' => 'Nama',
'bdr_daily_report_modal_field_category' => 'Kategori',
'bdr_daily_report_modal_field_reason' => 'Sebab',
'bdr_daily_report_modal_field_start_date' => 'Tarikh Mula',
'bdr_daily_report_modal_field_end_date' => 'Tarikh Tamat',
'bdr_daily_report_modal_field_start_time' => 'Masa Mula',
'bdr_daily_report_modal_field_end_time' => 'Masa Tamat',
'bdr_daily_report_modal_field_head_department' => 'Ketua Jabatan',
'bdr_daily_report_modal_field_head_comment' => 'Komen Ketua Jabatan',
'bdr_daily_report_modal_field_head_date' => 'Tarikh Ketua Jabatan',
'bdr_daily_report_modal_field_approval_flag' => 'Flag Kelulusan KJ',
'bdr_daily_report_modal_field_work_location' => 'Lokasi Bekerja',
'bdr_daily_report_modal_field_distance' => 'Jarak',
'bdr_daily_report_modal_field_work_location_latitude' => 'Lokasi Bekerja Latitude',
'bdr_daily_report_modal_field_work_location_longitude' => 'Lokasi Bekerja Longitude',
'bdr_daily_report_modal_field_clock_in' => 'Masa Masuk',
'bdr_daily_report_modal_field_clock_out' => 'Masa Keluar',
'bdr_daily_report_modal_field_clock_in_latitude' => 'Clock In Latitude',
'bdr_daily_report_modal_field_clock_in_longitude' => 'Clock In Longitude',
'bdr_daily_report_modal_field_location_in' => 'Lokasi In',
'bdr_daily_report_modal_field_location_out' => 'Lokasi Out',
'bdr_daily_report_modal_field_clock_out_latitude' => 'Clock Out Latitude',
'bdr_daily_report_modal_field_clock_out_longitude' => 'Clock Out Longitude',
'bdr_daily_report_modal_field_equipment_required' => 'Peralatan Diperlukan',
'bdr_daily_report_modal_field_equipment_serial' => 'No. Siri Peralatan',
'bdr_daily_report_modal_field_supporting_document' => 'Dokumen Sokongan',
'bdr_daily_report_modal_field_daily_report' => 'Daily Report',
'bdr_daily_report_modal_approval_passed' => 'LULUS',
'bdr_daily_report_print_toolbar_title' => 'Laporan Harian BDR',
'bdr_daily_report_print_button' => 'Cetak / Simpan PDF',
'bdr_daily_report_print_not_found' => 'Rekod laporan harian tidak ditemui atau tidak boleh diakses.',
'bdr_daily_report_print_eyebrow' => 'Dokumen Rasmi',
'bdr_daily_report_print_intro' => 'Laporan ini merumuskan ketetapan tugasan, status tugas, dan ulasan staf bagi rekod Bekerja Dari Rumah yang telah diluluskan.',
'bdr_daily_report_print_note' => 'Pastikan semua maklumat tugasan, status tugas, dan rekod sumber BDR disemak sebelum dokumen ini dicetak atau disimpan sebagai PDF.',
'bdr_daily_report_print_section_report_info' => 'Maklumat Laporan',
'bdr_daily_report_print_section_report_info_subtitle' => 'Ringkasan staf dan rekod rujukan',
'bdr_daily_report_print_meta_name' => 'Nama',
'bdr_daily_report_print_meta_staff_id' => 'No. Staf',
'bdr_daily_report_print_meta_date' => 'Tarikh',
'bdr_daily_report_print_meta_status' => 'Status Laporan',
'bdr_daily_report_print_meta_reference' => 'No. Rujukan',
'bdr_daily_report_print_meta_category' => 'Kategori',
'bdr_daily_report_print_meta_approval' => 'Kelulusan',
'bdr_daily_report_print_section_tasks' => 'Butiran Tugasan',
'bdr_daily_report_print_section_tasks_subtitle' => 'Senarai tugasan harian yang direkodkan',
'bdr_daily_report_print_no_tasks' => 'Tiada tugasan direkodkan.',
'bdr_daily_report_print_section_sources' => 'Rekod Sumber BDR',
'bdr_daily_report_print_section_sources_subtitle' => 'Rujukan asal yang membentuk laporan ini',
'bdr_daily_report_print_source_date' => 'Tarikh',
'bdr_daily_report_print_source_category' => 'Kategori',
'bdr_daily_report_print_source_approval' => 'Kelulusan',
'bdr_daily_report_print_printed_at' => 'Tarikh Cetakan',
'bdr_daily_report_print_staff_confirmation' => 'Pengesahan Staf',
'bdr_daily_report_print_signature_hint' => 'Tandatangan / Cop',
'bdr_daily_report_month_01' => 'Januari',
'bdr_daily_report_month_02' => 'Februari',
'bdr_daily_report_month_03' => 'Mac',
'bdr_daily_report_month_04' => 'April',
'bdr_daily_report_month_05' => 'Mei',
'bdr_daily_report_month_06' => 'Jun',
'bdr_daily_report_month_07' => 'Julai',
'bdr_daily_report_month_08' => 'Ogos',
'bdr_daily_report_month_09' => 'September',
'bdr_daily_report_month_10' => 'Oktober',
'bdr_daily_report_month_11' => 'November',
'bdr_daily_report_month_12' => 'Disember',
// ===== END Generated Template: Laporan Harian [bdr_daily_report] =====
];
